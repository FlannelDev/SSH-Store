<?php

namespace App\Plugins\ShadowStore\Services;

use App\Plugins\ShadowStore\Models\Order;
use Stripe\StripeClient;
use Stripe\Checkout\Session;

class StripeService
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('shadow-store.stripe.secret'));
    }

    public function createCheckoutSession(Order $order, array $cartItems, float $total): Session
    {
        $lineItems = [];
        
        foreach ($cartItems as $item) {
            $product = $item['product'];
            $slots = $item['slots'] ?? null;
            
            $name = $product->name;
            if ($slots) {
                $name .= " ({$slots} slots)";
            }
            
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower(config('shadow-store.currency', 'USD')),
                    'product_data' => [
                        'name' => $name,
                        'description' => $product->description ?? '',
                    ],
                    'unit_amount' => (int) ($item['price'] * 100), // Convert to cents
                    'recurring' => [
                        'interval' => 'month',
                    ],
                ],
                'quantity' => 1,
            ];
        }

        $session = $this->client->checkout->sessions->create([
            'customer_email' => auth()->user()->email,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'subscription',
            'success_url' => route('store.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('store.checkout.cancel'),
            'metadata' => [
                'order_id' => $order->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ],
        ]);

        return $session;
    }

    public function createOneTimeCheckoutSession(Order $order, array $cartItems, float $total, array $orderIds = [], ?int $userId = null): Session
    {
        $lineItems = [];
        $currency = strtolower(config('shadow-store.currency', 'USD'));
        $dueTodayCents = max(1, (int) round($total * 100));
        $effectiveOrderIds = !empty($orderIds) ? $orderIds : [$order->id];
        $effectiveUserId = $userId ?? (int) auth()->id();

        $lineItems[] = [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => 'Shadow Store Order - First Month',
                    'description' => 'Includes first-month charges after coupon/credit adjustments.',
                ],
                'unit_amount' => $dueTodayCents,
            ],
            'quantity' => 1,
        ];

        $session = $this->client->checkout->sessions->create([
            'customer_email' => auth()->user()->email,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('store.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('store.checkout.cancel'),
            'metadata' => [
                'order_id' => $order->id,
                'order_ids' => implode(',', $effectiveOrderIds),
                'user_id' => (string) $effectiveUserId,
                'coupon_code' => (string) ($order->coupon_code ?? ''),
            ],
        ]);

        return $session;
    }

    public function createDirectPaymentSession(array $lineItems, array $metadata = []): Session
    {
        $metadata['user_id'] = (string) ($metadata['user_id'] ?? (int) auth()->id());

        return $this->client->checkout->sessions->create([
            'customer_email' => auth()->user()->email,
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('store.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('store.checkout.cancel'),
            'metadata' => $metadata,
        ]);
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client->checkout->sessions->retrieve($sessionId);
    }

    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            config('shadow-store.stripe.webhook_secret')
        );
    }
}
