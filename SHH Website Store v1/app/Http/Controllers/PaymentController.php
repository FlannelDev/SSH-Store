<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function success(Request $request, string $order)
    {
        $order = Order::where('order_number', $order)->firstOrFail();

        // For Stripe, verify the session if session_id is present
        if ($order->payment_method === 'stripe' && $request->has('session_id')) {
            try {
                $stripe = new \Stripe\StripeClient(config('payment.stripe.secret'));
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
                }
            } catch (\Exception $e) {
                Log::error('Stripe session verification failed', [
                    'order' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('payment.success', ['order' => $order]);
    }

    public function cancel(string $order)
    {
        $order = Order::where('order_number', $order)->firstOrFail();

        if ($order->status === 'pending') {
            $order->update(['status' => 'cancelled']);
        }

        return view('payment.cancel', ['order' => $order]);
    }

    public function paypalCapture(Request $request, string $order)
    {
        $order = Order::where('order_number', $order)->firstOrFail();

        $token = $request->input('token');

        if (! $token) {
            return redirect()->route('checkout.cancel', ['order' => $order->order_number]);
        }

        try {
            $provider = new \Srmklive\PayPal\Services\PayPal;
            $provider->setApiCredentials([
                'mode' => config('payment.paypal.mode'),
                'sandbox' => [
                    'client_id' => config('payment.paypal.client_id'),
                    'client_secret' => config('payment.paypal.client_secret'),
                    'app_id' => '',
                ],
                'live' => [
                    'client_id' => config('payment.paypal.client_id'),
                    'client_secret' => config('payment.paypal.client_secret'),
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

                return view('payment.success', ['order' => $order]);
            }

            $order->update([
                'status' => 'cancelled',
                'meta' => array_merge($order->meta ?? [], ['paypal_capture_result' => $captureResult]),
            ]);

            return redirect()->route('checkout.cancel', ['order' => $order->order_number]);
        } catch (\Exception $e) {
            Log::error('PayPal capture failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('checkout.cancel', ['order' => $order->order_number]);
        }
    }

    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('payment.stripe.webhook_secret');

        if (! $webhookSecret) {
            Log::warning('Stripe webhook secret not configured');
            return response('Webhook secret not configured', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $order = Order::where('payment_id', $session->id)->first();

                if ($order && $order->status !== 'paid') {
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
                }
                break;

            case 'checkout.session.expired':
                $session = $event->data->object;
                $order = Order::where('payment_id', $session->id)->first();

                if ($order && $order->status === 'pending') {
                    $order->update(['status' => 'cancelled']);
                }
                break;
        }

        return response('OK', 200);
    }
}
