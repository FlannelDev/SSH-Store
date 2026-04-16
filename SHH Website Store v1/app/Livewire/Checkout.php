<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

class Checkout extends Component
{
    public Product $product;
    public string $billingCycle = 'monthly';
    public string $customerEmail = '';
    public string $customerName = '';
    public string $paymentMethod = '';
    public bool $processing = false;

    public function mount(string $slug, string $cycle = 'monthly'): void
    {
        $this->product = Product::visible()
            ->where('slug', $slug)
            ->where('in_stock', true)
            ->firstOrFail();

        $this->billingCycle = in_array($cycle, ['monthly', 'quarterly', 'annually']) ? $cycle : 'monthly';

        if (auth()->check()) {
            $this->customerEmail = auth()->user()->email;
            $this->customerName = auth()->user()->name;
        }
    }

    public function getAmount(): float
    {
        return match ($this->billingCycle) {
            'quarterly' => (float) ($this->product->price_quarterly ?: $this->product->price_monthly * 3),
            'annually' => (float) ($this->product->price_annually ?: $this->product->price_monthly * 12),
            default => (float) $this->product->price_monthly,
        };
    }

    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->getAmount(), 2);
    }

    public function getCycleLabel(): string
    {
        return match ($this->billingCycle) {
            'quarterly' => 'per quarter',
            'annually' => 'per year',
            default => 'per month',
        };
    }

    public function payWithStripe(): void
    {
        $this->validate([
            'customerEmail' => 'required|email|max:255',
            'customerName' => 'required|string|max:255',
        ]);

        $this->processing = true;
        $this->paymentMethod = 'stripe';

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => auth()->id(),
            'product_id' => $this->product->id,
            'billing_cycle' => $this->billingCycle,
            'amount' => $this->getAmount(),
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'customer_email' => $this->customerEmail,
            'customer_name' => $this->customerName,
        ]);

        try {
            $stripe = new \Stripe\StripeClient(config('payment.stripe.secret'));

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'customer_email' => $this->customerEmail,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $this->product->name,
                            'description' => $this->product->tier . ' — ' . $this->billingCycle . ' billing',
                        ],
                        'unit_amount' => (int) round($this->getAmount() * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('checkout.success', ['order' => $order->order_number]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', ['order' => $order->order_number]),
                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                ],
            ]);

            $order->update(['payment_id' => $session->id]);

            $this->redirect($session->url);
        } catch (\Exception $e) {
            $order->update(['status' => 'cancelled', 'meta' => ['error' => $e->getMessage()]]);
            $this->processing = false;
            session()->flash('error', 'Payment initialization failed. Please try again.');
        }
    }

    public function payWithPaypal(): void
    {
        $this->validate([
            'customerEmail' => 'required|email|max:255',
            'customerName' => 'required|string|max:255',
        ]);

        $this->processing = true;
        $this->paymentMethod = 'paypal';

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => auth()->id(),
            'product_id' => $this->product->id,
            'billing_cycle' => $this->billingCycle,
            'amount' => $this->getAmount(),
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'paypal',
            'customer_email' => $this->customerEmail,
            'customer_name' => $this->customerName,
        ]);

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

            $paypalOrder = $provider->createOrder([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'description' => $this->product->name . ' — ' . $this->billingCycle . ' billing',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($this->getAmount(), 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('paypal.capture', ['order' => $order->order_number]),
                    'cancel_url' => route('checkout.cancel', ['order' => $order->order_number]),
                    'brand_name' => 'Shadow Haven Hosting',
                    'user_action' => 'PAY_NOW',
                ],
            ]);

            if (isset($paypalOrder['id'])) {
                $order->update(['payment_id' => $paypalOrder['id']]);

                $approvalUrl = collect($paypalOrder['links'])->firstWhere('rel', 'approve')['href'] ?? null;

                if ($approvalUrl) {
                    $this->redirect($approvalUrl);
                    return;
                }
            }

            $order->update(['status' => 'cancelled', 'meta' => ['error' => 'Failed to create PayPal order', 'response' => $paypalOrder]]);
            $this->processing = false;
            session()->flash('error', 'PayPal initialization failed. Please try again.');
        } catch (\Exception $e) {
            $order->update(['status' => 'cancelled', 'meta' => ['error' => $e->getMessage()]]);
            $this->processing = false;
            session()->flash('error', 'PayPal initialization failed. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.checkout', [
            'formattedPrice' => $this->getFormattedPrice(),
            'cycleLabel' => $this->getCycleLabel(),
        ])->layout('components.layouts.store', ['title' => 'Checkout — ' . $this->product->name]);
    }
}
