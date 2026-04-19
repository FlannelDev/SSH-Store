<?php

namespace ShhStore\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ShhStore\Models\StoreOrder;

class PaymentController extends Controller
{
    public function success(Request $request, string $order)
    {
        $order = StoreOrder::where('order_number', $order)->firstOrFail();
        $this->backfillOrderUser($order);

        if ($order->payment_method === 'stripe' && $request->has('session_id')) {
            try {
                $stripe = new \Stripe\StripeClient(config('shh-store.stripe.secret'));
                $session = $stripe->checkout->sessions->retrieve($request->input('session_id'));

                if ($session->payment_status === 'paid') {
                    $order->update([
                        'status' => 'paid',
                        'transaction_id' => $session->payment_intent,
                        'paid_at' => now(),
                        'meta' => array_merge($order->meta ?? [], [
                            'stripe_session' => $session->id,
                            'stripe_customer' => $session->customer,
                        ]),
                    ]);

                    $order->releaseNonPaymentSuspension();
                }
            } catch (\Exception $e) {
                Log::error('SHH Store: Stripe session verification failed', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('shh-store::payment.success', ['order' => $order]);
    }

    public function cancel(string $order)
    {
        $order = StoreOrder::where('order_number', $order)->firstOrFail();
        $this->backfillOrderUser($order);

        if ($order->status === 'pending') {
            $order->cancel();
        }

        return view('shh-store::payment.cancel', ['order' => $order]);
    }

    public function paypalCapture(Request $request, string $order)
    {
        $order = StoreOrder::where('order_number', $order)->firstOrFail();
        $this->backfillOrderUser($order);

        $token = $request->input('token');

        if (! $token) {
            return redirect()->route('shh-store.payment.cancel', ['order' => $order->order_number]);
        }

        try {
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials([
                'mode' => config('shh-store.paypal.mode'),
                'sandbox' => [
                    'client_id' => config('shh-store.paypal.client_id'),
                    'client_secret' => config('shh-store.paypal.client_secret'),
                    'app_id' => '',
                ],
                'live' => [
                    'client_id' => config('shh-store.paypal.client_id'),
                    'client_secret' => config('shh-store.paypal.client_secret'),
                    'app_id' => '',
                ],
                'payment_action' => 'Sale',
                'currency' => 'USD',
                'notify_url' => '',
                'locale' => 'en_US',
                'validate_ssl' => true,
            ]);
            $provider->getAccessToken();

            $captureResult = $provider->capturePaymentOrder($token);

            if (isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {
                $captureId = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

                $order->update([
                    'status' => 'paid',
                    'transaction_id' => $captureId,
                    'paid_at' => now(),
                    'meta' => array_merge($order->meta ?? [], [
                        'paypal_order_id' => $captureResult['id'],
                        'paypal_payer' => $captureResult['payer'] ?? null,
                    ]),
                ]);

                $order->releaseNonPaymentSuspension();

                return view('shh-store::payment.success', ['order' => $order]);
            }

            $order->update([
                'status' => 'cancelled',
                'meta' => array_merge($order->meta ?? [], ['paypal_capture_result' => $captureResult]),
            ]);

            return redirect()->route('shh-store.payment.cancel', ['order' => $order->order_number]);
        } catch (\Exception $e) {
            Log::error('SHH Store: PayPal capture failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('shh-store.payment.cancel', ['order' => $order->order_number]);
        }
    }

    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('shh-store.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::warning('SHH Store: Stripe webhook secret not configured');
            return response('Webhook secret not configured', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('SHH Store: Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $order = StoreOrder::where('payment_id', $session->id)->first();

                if ($order && $order->status !== 'paid') {
                    $this->backfillOrderUser($order, $session->customer_details->email ?? $session->customer_email ?? null);

                    $order->update([
                        'status' => 'paid',
                        'transaction_id' => $session->payment_intent,
                        'paid_at' => now(),
                        'meta' => array_merge($order->meta ?? [], [
                            'stripe_session' => $session->id,
                            'stripe_customer' => $session->customer,
                            'webhook_processed' => true,
                        ]),
                    ]);

                    $order->releaseNonPaymentSuspension();
                }
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $order = StoreOrder::where('payment_id', $session->id)->first();

                if ($order && $order->status === 'pending') {
                    $order->cancel();
                }
                break;
        }

        return response('OK', 200);
    }

    private function backfillOrderUser(StoreOrder $order, ?string $email = null): void
    {
        if (filled($order->user_id)) {
            return;
        }

        $candidateEmail = $email ?: $order->customer_email;

        if (blank($candidateEmail)) {
            return;
        }

        $userId = User::query()
            ->where('email', $candidateEmail)
            ->value('id');

        if (!$userId) {
            return;
        }

        $order->forceFill(['user_id' => $userId])->save();
    }
}
