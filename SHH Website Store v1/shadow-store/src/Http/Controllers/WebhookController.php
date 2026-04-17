<?php

namespace App\Plugins\ShadowStore\Http\Controllers;

use App\Enums\SuspendAction;
use App\Http\Controllers\Controller;
use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Models\PaymentReceipt;
use App\Plugins\ShadowStore\Models\ServerBilling;
use App\Plugins\ShadowStore\Services\StoreWebhookNotifier;
use App\Plugins\ShadowStore\Services\StripeService;
use App\Services\Servers\SuspensionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Exception;
use Throwable;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $stripeService = new StripeService();
            $event = $stripeService->constructWebhookEvent($payload, $signature);

            if (!$this->storePaymentReceipt('stripe_event:' . $event->id, 'stripe', 'webhook_event', [
                'event_type' => $event->type,
            ])) {
                return response()->json(['status' => 'already_processed']);
            }

            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleStripeCheckoutCompleted($event->data->object);
                    break;

                case 'invoice.paid':
                    $this->handleStripeInvoicePaid($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handleStripePaymentFailed($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleStripeSubscriptionDeleted($event->data->object);
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            report($e);
            return response()->json(['error' => 'Invalid webhook payload.'], 400);
        }
    }

    protected function handleStripeCheckoutCompleted($session)
    {
        if (!$this->storePaymentReceipt('stripe_session:' . $session->id, 'stripe', 'checkout_session', [
            'session_id' => $session->id,
            'metadata' => (array) ($session->metadata ?? []),
        ])) {
            return;
        }

        $metadataUserId = (int) ($session->metadata->user_id ?? 0);
        if ($metadataUserId <= 0) {
            return;
        }

        $serverBillingIdsMeta = (string) ($session->metadata->server_billing_ids ?? '');
        if ($serverBillingIdsMeta !== '') {
            $billingIds = collect(explode(',', $serverBillingIdsMeta))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->values();

            if ($billingIds->isNotEmpty()) {
                $validBillingIds = ServerBilling::query()
                    ->whereIn('id', $billingIds->all())
                    ->where('user_id', $metadataUserId)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (!empty($validBillingIds)) {
                    $this->settleServerBillings($validBillingIds, 'stripe_session:' . $session->id, 'server_billing_checkout');
                }
                return;
            }
        }

        $orderIdsMeta = (string) ($session->metadata->order_ids ?? '');
        $orderIds = collect(explode(',', $orderIdsMeta))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($orderIds)) {
            $legacyOrderId = (int) ($session->metadata->order_id ?? 0);
            if ($legacyOrderId > 0) {
                $orderIds = [$legacyOrderId];
            }
        }

        if (!empty($orderIds)) {
            $orders = Order::query()
                ->whereIn('id', $orderIds)
                ->where('user_id', $metadataUserId)
                ->get();

            foreach ($orders as $order) {
                if ($order->status === 'pending') {
                    $order->activate(
                        $session->payment_intent ?? null,
                        $session->subscription ?? null
                    );
                    app(StoreWebhookNotifier::class)->sendOrderPaid($order->fresh(['user', 'product']), 'stripe_session:' . $session->id, 'order_checkout');
                }
            }
        }
    }

    protected function handleStripeInvoicePaid($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if ($subscriptionId) {
            $order = Order::where('subscription_id', $subscriptionId)->first();
            if ($order) {
                $order->update([
                    'status' => 'paid',
                    'expires_at' => now()->addMonth(),
                    'bill_due_at' => now()->addMonth(),
                ]);

                $order->releaseNonPaymentSuspension();
                app(StoreWebhookNotifier::class)->sendOrderPaid($order->fresh(['user', 'product']), 'stripe_invoice:' . $invoice->id, 'invoice_paid');
            }
        }
    }

    protected function handleStripePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription;
        
        if ($subscriptionId) {
            $order = Order::where('subscription_id', $subscriptionId)->first();
            if ($order) {
                $order->update([
                    'status' => 'payment_failed',
                    'notes' => 'Payment failed: ' . ($invoice->last_payment_error->message ?? 'Unknown error'),
                ]);
            }
        }
    }

    protected function handleStripeSubscriptionDeleted($subscription)
    {
        $order = Order::where('subscription_id', $subscription->id)->first();
        if ($order) {
            $order->cancel();
        }
    }

    public function paypal(Request $request)
    {
        // TODO: Implement PayPal webhook handling
        return response()->json(['status' => 'not_implemented'], 501);
    }

    protected function settleServerBillings(array $billingIds, ?string $paymentReference = null, string $eventLabel = 'server_billing_payment'): void
    {
        if (empty($billingIds) || !SchemaFacade::hasTable('store_server_billings')) {
            return;
        }

        $now = now();
        $webhookNotifier = app(StoreWebhookNotifier::class);
        $billings = ServerBilling::query()
            ->whereIn('id', $billingIds)
            ->with(['server', 'user'])
            ->get();

        foreach ($billings as $billing) {
            $baseDue = $billing->bill_due_at && $billing->bill_due_at->greaterThan($now)
                ? $billing->bill_due_at->copy()
                : $now->copy();

            $billing->bill_due_at = $baseDue->addMonth();
            $billing->due_notice_sent_at = null;
            $billing->past_due_notice_sent_at = null;
            $billing->suspended_for_nonpayment_at = null;
            $billing->suspended_notice_sent_at = null;
            $billing->save();

            if ($billing->server && $billing->server->isSuspended()) {
                try {
                    app(SuspensionService::class)->handle($billing->server, SuspendAction::Unsuspend);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }

            if ($paymentReference) {
                $webhookNotifier->sendServerBillingPaid($billing, $paymentReference, $eventLabel);
            }
        }
    }

    protected function storePaymentReceipt(string $externalId, string $provider, string $type, array $payload = []): bool
    {
        try {
            PaymentReceipt::create([
                'provider' => $provider,
                'external_id' => $externalId,
                'type' => $type,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return true;
        } catch (QueryException $exception) {
            return false;
        }
    }
}
