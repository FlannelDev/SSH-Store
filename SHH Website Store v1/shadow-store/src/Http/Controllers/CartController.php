<?php

namespace App\Plugins\ShadowStore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\ShadowStore\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function index()
    {
        $cart = Session::get('cart', []);
        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $price = isset($item['custom_monthly_price'])
                    ? (float) $item['custom_monthly_price']
                    : ($product->billing_type === 'slots'
                        ? $product->price_per_slot * ($item['slots'] ?? $product->default_slots ?? 64)
                        : $product->base_price);
                
                $cartItems[] = [
                    'key' => $key,
                    'product' => $product,
                    'slots' => $item['slots'] ?? null,
                    'tier_label' => $item['tier_label'] ?? null,
                    'variables' => $item['variables'] ?? [],
                    'price' => $price,
                ];
                $subtotal += $price;
            }
        }

        $taxRate = config('shadow-store.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        return view('shadow-store::pages.cart', compact('cartItems', 'subtotal', 'tax', 'total', 'taxRate'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:store_products,id',
            'slots' => 'nullable|integer|min:1',
            'variables' => 'nullable|array',
            'billing_cycle' => 'nullable|in:monthly,quarterly,semi,annual',
            'custom_monthly_price' => 'nullable|numeric|min:0',
            'tier_label' => 'nullable|string|max:255',
            'accept_msa' => 'accepted',
        ]);

        $product = Product::with('egg.variables')->findOrFail($request->product_id);

        $tierLabel = trim((string) $request->input('tier_label', ''));
        if ($product->game === 'arma-reforger' && $tierLabel === 'Shadow Box 1') {
            return redirect()->back()->with('error', 'Shadow Box 1 does not meet the minimum specs for this server type. Please select Shadow Box 2 or higher.');
        }
        
        // Validate slots against product limits
        $slots = $request->slots ?? $product->default_slots ?? 64;
        if ($product->billing_type === 'slots') {
            $slots = max($product->min_slots ?? 1, min($product->max_slots ?? 128, $slots));
        }

        // Validate required variables
        $variables = $request->variables ?? [];
        if ($product->egg) {
            foreach ($product->egg->variables as $variable) {
                if ($variable->user_editable) {
                    // Use submitted value or default
                    if (!isset($variables[$variable->env_variable]) || $variables[$variable->env_variable] === '') {
                        $variables[$variable->env_variable] = $variable->default_value;
                    }
                }
            }
        }

        $cart = Session::get('cart', []);
        
        // Create unique key for this cart item (using timestamp to allow multiple of same product)
        $cartKey = $product->id . '-' . time();
        
        $cart[$cartKey] = [
            'product_id' => $product->id,
            'slots' => $slots,
            'variables' => $variables,
            'billing_cycle' => $request->billing_cycle ?? 'monthly',
            'custom_monthly_price' => $request->filled('custom_monthly_price') ? round((float) $request->custom_monthly_price, 2) : null,
            'tier_label' => $request->input('tier_label'),
            'added_at' => now()->toDateTimeString(),
        ];

        Session::put('cart', $cart);

        return redirect()->route('store.cart')->with('success', $product->name . ' added to cart!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'slots' => 'nullable|integer|min:1',
        ]);

        $cart = Session::get('cart', []);
        
        if (isset($cart[$request->key])) {
            $product = Product::find($cart[$request->key]['product_id']);
            if ($product && $product->billing_type === 'slots' && $request->slots) {
                $slots = max($product->min_slots ?? 1, min($product->max_slots ?? 128, $request->slots));
                $cart[$request->key]['slots'] = $slots;
            }
            Session::put('cart', $cart);
        }

        return redirect()->route('store.cart');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $cart = Session::get('cart', []);
        unset($cart[$request->key]);
        Session::put('cart', $cart);

        return redirect()->route('store.cart')->with('success', 'Item removed from cart.');
    }

    public function checkout()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('store.cart')->with('error', 'Your cart is empty.');
        }

        $cartItems = [];
        $subtotal = 0;

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $price = isset($item['custom_monthly_price'])
                    ? (float) $item['custom_monthly_price']
                    : ($product->billing_type === 'slots'
                        ? $product->price_per_slot * ($item['slots'] ?? $product->default_slots ?? 64)
                        : $product->base_price);
                
                $cartItems[] = [
                    'key' => $key,
                    'product' => $product,
                    'slots' => $item['slots'] ?? null,
                    'tier_label' => $item['tier_label'] ?? null,
                    'variables' => $item['variables'] ?? [],
                    'price' => $price,
                ];
                $subtotal += $price;
            }
        }

        $taxRate = config('shadow-store.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);
        $total = $subtotal + $tax;

        $stripeEnabled = config('shadow-store.stripe.enabled', false);
        $paypalEnabled = config('shadow-store.paypal.enabled', false);

        return view('shadow-store::pages.checkout', compact(
            'cartItems', 'subtotal', 'tax', 'total', 'taxRate',
            'stripeEnabled', 'paypalEnabled'
        ));
    }

    public function processCheckout(Request $request)
    {
        $paymentMethod = $request->input('payment_method', 'stripe');
        
        if ($paymentMethod === 'stripe' && config('shadow-store.stripe.enabled')) {
            return $this->processStripeCheckout($request);
        } elseif ($paymentMethod === 'paypal' && config('shadow-store.paypal.enabled')) {
            return $this->processPayPalCheckout($request);
        }
        
        return redirect()->route('store.checkout')->with('error', 'Payment method not available.');
    }

    protected function processStripeCheckout(Request $request)
    {
        // TODO: Implement Stripe checkout
        return redirect()->route('store.checkout')->with('error', 'Stripe payment processing coming soon.');
    }

    protected function processPayPalCheckout(Request $request)
    {
        // TODO: Implement PayPal checkout
        return redirect()->route('store.checkout')->with('error', 'PayPal payment processing coming soon.');
    }
}
