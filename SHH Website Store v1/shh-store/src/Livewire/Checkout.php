<?php

namespace ShhStore\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use ShhStore\Models\StoreOrder;
use ShhStore\Models\StoreProduct;
use Throwable;

class Checkout extends Component
{
    public StoreProduct $product;
    public string $billingCycle = 'monthly';
    public int $slots = 10;
    public string $customerEmail = '';
    public string $customerName = '';
    public string $paymentMethod = '';
    public bool $processing = false;

    public function mount(string $slug, string $cycle = 'monthly'): void
    {
        $this->product = StoreProduct::visible()
            ->where('slug', $slug)
            ->where('in_stock', true)
            ->firstOrFail();

        $this->billingCycle = in_array($cycle, ['monthly', 'quarterly', 'annually']) ? $cycle : 'monthly';

        if (auth()->check()) {
            $user = auth()->user();

            $this->customerEmail = (string) ($user->email ?? '');
            $this->customerName = (string) ($user->name ?? $user->username ?? '');
        }

        $this->slots = $this->product->hasPerSlotFee()
            ? $this->product->getResolvedDefaultSlots()
            : 1;
    }

    public function updatedSlots($value): void
    {
        $this->slots = $this->normalizeSlots((int) $value);
    }

    protected function normalizeSlots(int $slots): int
    {
        return min(128, max(1, $slots));
    }

    public function getAmount(): float
    {
        $unitPrice = $this->product->calculatePrice($this->billingCycle);

        if ($this->product->hasPerSlotFee()) {
            return $unitPrice * $this->normalizeSlots($this->slots);
        }

        return $unitPrice;
    }

    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->getAmount(), 2);
    }

    public function getCycleLabel(): string
    {
        if ($this->product->hasPerSlotFee()) {
            return match ($this->billingCycle) {
                'quarterly' => 'per slot per quarter',
                'annually' => 'per slot per year',
                default => 'per slot per month',
            };
        }

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
            'slots' => $this->product->hasPerSlotFee() ? 'required|integer|min:1|max:128' : 'nullable|integer|min:1|max:128',
        ]);

        $this->processing = true;
        $this->paymentMethod = 'stripe';

        $stripeSecret = (string) config('shh-store.stripe.secret', '');

        if (blank($stripeSecret)) {
            $this->processing = false;
            session()->flash('error', 'Stripe is not configured yet. Please contact support.');

            return;
        }

        $resolvedUserId = auth()->id()
            ?? User::query()->where('email', $this->customerEmail)->value('id');

        $order = StoreOrder::create([
            'order_number' => StoreOrder::generateOrderNumber(),
            'user_id' => $resolvedUserId,
            'product_id' => $this->product->id,
            'billing_cycle' => $this->billingCycle,
            'slots' => $this->product->hasPerSlotFee() ? $this->normalizeSlots($this->slots) : null,
            'amount' => $this->getAmount(),
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'stripe',
            'customer_email' => $this->customerEmail,
            'customer_name' => $this->customerName,
        ]);

        try {
            $stripe = new \Stripe\StripeClient($stripeSecret);

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'customer_email' => $this->customerEmail,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $this->product->name,
                            'description' => $this->product->tier . ' — ' . ($this->product->hasPerSlotFee() ? $this->normalizeSlots($this->slots) . ' slots · ' : '') . $this->billingCycle . ' billing',
                        ],
                        'unit_amount' => (int) round($this->getAmount() * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('shh-store.payment.success', ['order' => $order->order_number]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('shh-store.payment.cancel', ['order' => $order->order_number]),
                'metadata' => [
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                ],
            ]);

            $order->update(['payment_id' => $session->id]);

            $this->redirect($session->url);
        } catch (Throwable $e) {
            Log::error('SHH Store checkout: Stripe initialization failed', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            $order->cancel();
            $order->update(['meta' => ['error' => $e->getMessage()]]);
            $this->processing = false;
            session()->flash('error', 'Payment initialization failed. Please try again.');
        }
    }

    public function payWithPaypal(): void
    {
        $this->validate([
            'customerEmail' => 'required|email|max:255',
            'customerName' => 'required|string|max:255',
            'slots' => $this->product->hasPerSlotFee() ? 'required|integer|min:1|max:128' : 'nullable|integer|min:1|max:128',
        ]);

        $this->processing = true;
        $this->paymentMethod = 'paypal';

        $paypalClientId = (string) config('shh-store.paypal.client_id', '');
        $paypalClientSecret = (string) config('shh-store.paypal.client_secret', '');

        if (blank($paypalClientId) || blank($paypalClientSecret)) {
            $this->processing = false;
            session()->flash('error', 'PayPal is not configured yet. Please contact support.');

            return;
        }

        $resolvedUserId = auth()->id()
            ?? User::query()->where('email', $this->customerEmail)->value('id');

        $order = StoreOrder::create([
            'order_number' => StoreOrder::generateOrderNumber(),
            'user_id' => $resolvedUserId,
            'product_id' => $this->product->id,
            'billing_cycle' => $this->billingCycle,
            'slots' => $this->product->hasPerSlotFee() ? $this->normalizeSlots($this->slots) : null,
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
                'mode' => config('shh-store.paypal.mode'),
                'sandbox' => [
                    'client_id' => $paypalClientId,
                    'client_secret' => $paypalClientSecret,
                    'app_id' => '',
                ],
                'live' => [
                    'client_id' => $paypalClientId,
                    'client_secret' => $paypalClientSecret,
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
                    'description' => $this->product->name . ' — ' . ($this->product->hasPerSlotFee() ? $this->normalizeSlots($this->slots) . ' slots · ' : '') . $this->billingCycle . ' billing',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($this->getAmount(), 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => route('shh-store.paypal.capture', ['order' => $order->order_number]),
                    'cancel_url' => route('shh-store.payment.cancel', ['order' => $order->order_number]),
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

            $order->cancel();
            $order->update(['meta' => ['error' => 'Failed to create PayPal order', 'response' => $paypalOrder]]);
            $this->processing = false;
            session()->flash('error', 'PayPal initialization failed. Please try again.');
        } catch (Throwable $e) {
            Log::error('SHH Store checkout: PayPal initialization failed', [
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);

            $order->cancel();
            $order->update(['meta' => ['error' => $e->getMessage()]]);
            $this->processing = false;
            session()->flash('error', 'PayPal initialization failed. Please try again.');
        }
    }

    public function render()
    {
        return view('shh-store::livewire.checkout', [
            'formattedPrice' => $this->getFormattedPrice(),
            'cycleLabel' => $this->getCycleLabel(),
        ])->layout('shh-store::components.layouts.store', ['title' => 'Checkout — ' . $this->product->name]);
    }
}
