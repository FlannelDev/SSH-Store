<?php

namespace App\Plugins\ShadowStore\Http\Controllers;

use App\Enums\SuspendAction;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Plugins\ShadowStore\Models\ClientCredit;
use App\Plugins\ShadowStore\Models\Coupon;
use App\Plugins\ShadowStore\Models\CouponRedemption;
use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Models\PaymentReceipt;
use App\Plugins\ShadowStore\Models\Product;
use App\Plugins\ShadowStore\Models\ServerBilling;
use App\Plugins\ShadowStore\Services\StoreWebhookNotifier;
use App\Plugins\ShadowStore\Services\StripeService;
use App\Services\Servers\SuspensionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\QueryException;
use Exception;
use Throwable;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('store.cart')->with('error', 'Your cart is empty.');
        }

        $cartItems = [];
        $subtotal = 0;
        $firstMonthSubtotal = 0;

        $billingCycleData = [
            'monthly'   => ['months' => 1,  'discount' => 0,    'label' => 'Monthly'],
            'quarterly' => ['months' => 3,  'discount' => 0.05, 'label' => 'Quarterly (3 mo)'],
            'semi'      => ['months' => 6,  'discount' => 0.10, 'label' => 'Semi-Annual (6 mo)'],
            'annual'    => ['months' => 12, 'discount' => 0.17, 'label' => 'Annual (12 mo)'],
        ];

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $slots = $item['slots'] ?? $product->default_slots ?? 64;
                $monthlyBasePrice = isset($item['custom_monthly_price'])
                    ? (float) $item['custom_monthly_price']
                    : ($product->billing_type === 'slots'
                        ? $product->price_per_slot * $slots
                        : (float) $product->base_price);
                $cycle = $billingCycleData[$item['billing_cycle'] ?? 'monthly'] ?? $billingCycleData['monthly'];
                $price = $monthlyBasePrice * $cycle['months'] * (1 - $cycle['discount']);

                $cartItems[] = [
                    'key' => $key,
                    'product' => $product,
                    'slots' => $slots,
                    'tier_label' => $item['tier_label'] ?? null,
                    'variables' => $item['variables'] ?? [],
                    'price' => $price,
                    'monthly_price' => $monthlyBasePrice,
                    'billing_cycle' => $item['billing_cycle'] ?? 'monthly',
                    'billing_label' => $cycle['label'],
                ];
                $subtotal += $price;
                $firstMonthSubtotal += $monthlyBasePrice;
            }
        }

        $taxRate = config('shadow-store.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);

        $coupon = null;
        $discount = 0;
        $couponCode = Session::get('checkout_coupon');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid() && $coupon->isUsableByUser((int) auth()->id())) {
                $discountBase = ($coupon->first_month_only ?? true) ? $firstMonthSubtotal : $subtotal;
                $discount = $coupon->calculateDiscount($discountBase);
            } else {
                Session::forget('checkout_coupon');
                $coupon = null;
            }
        }

        $proration = max(0, (float) Session::get('checkout_proration', 0));
        $maxProration = max(0, $subtotal + $tax - $discount);
        if ($proration > $maxProration) {
            $proration = $maxProration;
            Session::put('checkout_proration', $proration);
        }

        $creditBalance = auth()->check() ? max(0, ClientCredit::balanceForUser((int) auth()->id())) : 0;
        $creditApplied = min($creditBalance, max(0, $subtotal + $tax - $discount - $proration));

        $total = max(0, $subtotal + $tax - $discount - $proration - $creditApplied);

        $canProrate = auth()->user()?->isRootAdmin() ?? false;

        $stripeEnabled = config('shadow-store.stripe.enabled', false);
        $paypalEnabled = $this->paypalEnabled();

        return view('shadow-store::pages.checkout', compact(
            'cartItems', 'subtotal', 'tax', 'total', 'taxRate',
            'stripeEnabled', 'paypalEnabled', 'coupon', 'discount',
            'firstMonthSubtotal', 'proration', 'canProrate', 'creditBalance', 'creditApplied'
        ));
    }

    public function process(Request $request)
    {
        $request->validate([
            'accept_msa' => 'accepted',
        ]);

        $paymentMethod = $request->input('payment_method', 'stripe');
        $cart = Session::get('cart', []);
        
        if (empty($cart)) {
            return redirect()->route('store.cart')->with('error', 'Your cart is empty.');
        }

        // Build cart items with prices
        $cartItems = [];
        $subtotal = 0;
        $firstMonthSubtotal = 0;
        $billingCycleData = [
            'monthly'   => ['months' => 1,  'discount' => 0,    'label' => 'Monthly'],
            'quarterly' => ['months' => 3,  'discount' => 0.05, 'label' => 'Quarterly (3 mo)'],
            'semi'      => ['months' => 6,  'discount' => 0.10, 'label' => 'Semi-Annual (6 mo)'],
            'annual'    => ['months' => 12, 'discount' => 0.17, 'label' => 'Annual (12 mo)'],
        ];

        foreach ($cart as $key => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $slots = $item['slots'] ?? $product->default_slots ?? 64;
                $monthlyBasePrice = isset($item['custom_monthly_price'])
                    ? (float) $item['custom_monthly_price']
                    : ($product->billing_type === 'slots'
                        ? $product->price_per_slot * $slots
                        : (float) $product->base_price);
                $cycle = $billingCycleData[$item['billing_cycle'] ?? 'monthly'] ?? $billingCycleData['monthly'];
                $price = $monthlyBasePrice * $cycle['months'] * (1 - $cycle['discount']);
                
                $cartItems[] = [
                    'key' => $key,
                    'product' => $product,
                    'slots' => $slots,
                    'tier_label' => $item['tier_label'] ?? null,
                    'variables' => $item['variables'] ?? [],
                    'price' => $price,
                    'monthly_price' => $monthlyBasePrice,
                    'cart_item' => $item,
                ];
                $subtotal += $price;
                $firstMonthSubtotal += $monthlyBasePrice;
            }
        }

        $taxRate = config('shadow-store.tax_rate', 0);
        $tax = $subtotal * ($taxRate / 100);

        $coupon = null;
        $discount = 0;
        $couponCode = Session::get('checkout_coupon');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid() && $coupon->isUsableByUser((int) auth()->id())) {
                $discountBase = ($coupon->first_month_only ?? true) ? $firstMonthSubtotal : $subtotal;
                $discount = $coupon->calculateDiscount($discountBase);
            } else {
                $couponCode = null;
            }
        }

        $proration = max(0, (float) Session::get('checkout_proration', 0));
        $maxProration = max(0, $subtotal + $tax - $discount);
        if ($proration > $maxProration) {
            $proration = $maxProration;
            Session::put('checkout_proration', $proration);
        }

        $creditBalance = auth()->check() ? max(0, ClientCredit::balanceForUser((int) auth()->id())) : 0;
        $creditApplied = min($creditBalance, max(0, $subtotal + $tax - $discount - $proration));

        $total = max(0, $subtotal + $tax - $discount - $proration - $creditApplied);
        Session::put('checkout_credit_applied', $creditApplied);

        // Create orders for each cart item
        $orders = [];
        $webhookNotifier = app(StoreWebhookNotifier::class);
        foreach ($cartItems as $item) {
            $notes = [];
            if ($couponCode) {
                if (($coupon?->type ?? null) === 'affiliate') {
                    $notes[] = 'Affiliate tracking code: ' . $couponCode;
                } elseif (($coupon?->first_month_only ?? true) === true) {
                    $notes[] = 'Coupon (first month only): ' . $couponCode;
                } else {
                    $notes[] = 'Coupon (full billing period): ' . $couponCode;
                }
            }
            if ($proration > 0) {
                $notes[] = 'Proration credit applied: $' . number_format($proration, 2);
            }
            if ($creditApplied > 0) {
                $notes[] = 'Store credit applied: $' . number_format($creditApplied, 2);
            }

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'product_id' => $item['product']->id,
                'tier_label' => $item['tier_label'] ?? null,
                'coupon_code' => $couponCode,
                'status' => 'pending',
                'slots' => $item['slots'],
                'variables' => $item['variables'],
                'billing_period' => $item['cart_item']['billing_cycle'] ?? 'monthly',
                'subtotal' => $item['price'],
                'tax' => $item['price'] * ($taxRate / 100),
                'total' => $item['price'] * (1 + $taxRate / 100),
                'payment_method' => $paymentMethod,
                'currency' => 'USD',
                'auto_renew' => true,
                'notes' => !empty($notes) ? implode(' | ', $notes) : null,
            ]);
            $orders[] = $order;
            $webhookNotifier->sendOrderCreated($order->loadMissing(['user', 'product']));
        }

        // Store order IDs in session for webhook processing
        Session::put('pending_order_ids', collect($orders)->pluck('id')->toArray());

        if ($total <= 0) {
            foreach ($orders as $order) {
                $order->activate('store-credit', null);
                $webhookNotifier->sendOrderPaid($order->fresh(['user', 'product']), 'free_checkout:' . $order->id, 'free_checkout');
            }

            if ($creditApplied > 0) {
                ClientCredit::create([
                    'user_id' => (int) auth()->id(),
                    'applied_by' => null,
                    'amount' => -1 * round($creditApplied, 2),
                    'note' => 'Credit used on checkout: ' . collect($orders)->pluck('order_number')->implode(', '),
                ]);
            }

            $usedCoupon = Session::get('checkout_coupon');
            if ($usedCoupon) {
                $coupon = Coupon::where('code', $usedCoupon)->first();
                if ($coupon) {
                    $reference = 'free_checkout:' . collect($orders)->pluck('id')->implode('-') . ':coupon:' . $coupon->id;
                    if ($this->storeCouponRedemption($coupon, (int) auth()->id(), $reference)) {
                        $coupon->increment('uses');
                    }
                }
            }

            Session::forget(['cart', 'stripe_order_ids', 'pending_order_ids', 'checkout_coupon', 'checkout_proration', 'checkout_credit_applied']);

            return view('shadow-store::pages.success', [
                'orders' => collect($orders),
            ]);
        }

        if ($paymentMethod === 'stripe' && config('shadow-store.stripe.enabled')) {
            return $this->processStripe($orders, $cartItems, $total);
        } elseif ($paymentMethod === 'paypal' && $this->paypalEnabled()) {
            return $this->processPayPal($orders, $cartItems, $total);
        }

        // Mark orders as failed
        foreach ($orders as $order) {
            $order->update(['status' => 'failed']);
        }

        Session::forget('checkout_credit_applied');

        return redirect()->route('store.checkout')->with('error', 'Payment method not available.');
    }

    protected function processStripe(array $orders, array $cartItems, float $total)
    {
        try {
            $stripeService = new StripeService();
            $orderIds = collect($orders)->pluck('id')->values()->all();
            
            // For simplicity, use first order for metadata (multi-item checkout)
            $session = $stripeService->createOneTimeCheckoutSession(
                $orders[0],
                $cartItems,
                $total,
                $orderIds,
                (int) auth()->id()
            );

            // Store all order IDs in session metadata
            Session::put('stripe_order_ids', collect($orders)->pluck('id')->toArray());

            return redirect($session->url);
        } catch (Exception $e) {
            report($e);
            
            // Mark orders as failed
            foreach ($orders as $order) {
                $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            }

            return redirect()->route('store.checkout')
                ->with('error', 'Unable to create payment session right now. Please try again or contact support.');
        }
    }

    protected function processPayPal(array $orders, array $cartItems, float $total)
    {
        // TODO: Implement PayPal
        foreach ($orders as $order) {
            $order->update(['status' => 'failed']);
        }
        
        return redirect()->route('store.checkout')
            ->with('error', 'PayPal integration coming soon.');
    }

    public function applyCoupon(Request $request)
    {
        $code = strtoupper(trim($request->input('coupon_code', '')));

        if (empty($code)) {
            return redirect()->route('store.checkout')->with('coupon_error', 'Please enter a coupon code.');
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !$coupon->isValid()) {
            return redirect()->route('store.checkout')->with('coupon_error', 'Invalid or expired coupon code.');
        }

        $userId = (int) auth()->id();
        if (!$coupon->isUsableByUser($userId)) {
            return redirect()->route('store.checkout')->with('coupon_error', 'You have reached the usage limit for this coupon.');
        }

        $cart = Session::get('cart', []);
        $firstMonthSubtotal = 0;
        $billingCycleData = [
            'monthly'   => ['months' => 1,  'discount' => 0,    'label' => 'Monthly'],
            'quarterly' => ['months' => 3,  'discount' => 0.05, 'label' => 'Quarterly (3 mo)'],
            'semi'      => ['months' => 6,  'discount' => 0.10, 'label' => 'Semi-Annual (6 mo)'],
            'annual'    => ['months' => 12, 'discount' => 0.17, 'label' => 'Annual (12 mo)'],
        ];

        foreach ($cart as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $slots = $item['slots'] ?? $product->default_slots ?? 64;
                $monthlyBasePrice = $product->billing_type === 'slots'
                    ? $product->price_per_slot * $slots
                    : $product->base_price;
                // Coupon eligibility is based on first month spend only.
                $firstMonthSubtotal += $monthlyBasePrice;
            }
        }

        if ($coupon->type !== 'affiliate' && $coupon->min_order && $firstMonthSubtotal < $coupon->min_order) {
            return redirect()->route('store.checkout')
                ->with('coupon_error', 'A minimum order of $' . number_format($coupon->min_order, 2) . ' is required for this coupon.');
        }

        Session::put('checkout_coupon', $code);
        $successMessage = $coupon->type === 'affiliate'
            ? 'Affiliate code "' . $code . '" applied. This code is for tracking only and does not change the order total.'
            : 'Coupon "' . $code . '" applied successfully!';

        return redirect()->route('store.checkout')->with('coupon_success', $successMessage);
    }

    public function applyProration(Request $request)
    {
        if (!(auth()->user()?->isRootAdmin() ?? false)) {
            abort(403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $amount = round((float) $request->input('amount'), 2);
        Session::put('checkout_proration', $amount);

        return redirect()->route('store.checkout')->with('coupon_success', 'Proration credit updated.');
    }

    public function removeProration()
    {
        if (!(auth()->user()?->isRootAdmin() ?? false)) {
            abort(403);
        }

        Session::forget('checkout_proration');
        return redirect()->route('store.checkout');
    }

    public function removeCoupon()
    {
        Session::forget('checkout_coupon');
        return redirect()->route('store.checkout');
    }

    protected function paypalEnabled(): bool
    {
        return false;
    }

    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('store.index')->with('error', 'Invalid session.');
        }

        try {
            $stripeService = new StripeService();
            $session = $stripeService->retrieveSession($sessionId);

            if ($session->payment_status === 'paid') {
                $authUserId = (int) auth()->id();
                $metadataUserId = (int) ($session->metadata->user_id ?? 0);

                if ($metadataUserId <= 0 || $metadataUserId !== $authUserId) {
                    return redirect()->route('store.index')
                        ->with('error', 'Payment verification failed. Please contact support.');
                }

                $serverBillingIdsMeta = (string) ($session->metadata->server_billing_ids ?? '');
                if ($serverBillingIdsMeta !== '') {
                    if (!$this->storePaymentReceipt('stripe_session:' . $session->id, 'stripe', 'server_billing_checkout', [
                        'session_id' => $session->id,
                        'user_id' => $metadataUserId,
                    ])) {
                        return view('shadow-store::pages.success', [
                            'orders' => collect(),
                        ]);
                    }

                    $serverBillingIds = collect(explode(',', $serverBillingIdsMeta))
                        ->map(fn ($id) => (int) trim($id))
                        ->filter(fn ($id) => $id > 0)
                        ->values();

                    if ($serverBillingIds->isNotEmpty()) {
                        $this->settleServerBillings($serverBillingIds->all(), 'stripe_session:' . $session->id, 'server_billing_checkout');

                        return view('shadow-store::pages.success', [
                            'orders' => collect(),
                        ]);
                    }
                }

                $metadataOrderIds = (string) ($session->metadata->order_ids ?? '');
                $orderIds = collect(explode(',', $metadataOrderIds))
                    ->map(fn ($id) => (int) trim($id))
                    ->filter(fn ($id) => $id > 0)
                    ->values()
                    ->all();

                if (empty($orderIds)) {
                    return redirect()->route('store.index')
                        ->with('error', 'Payment verification failed. Please contact support.');
                }

                if (!$this->storePaymentReceipt('stripe_session:' . $session->id, 'stripe', 'order_checkout', [
                    'session_id' => $session->id,
                    'user_id' => $metadataUserId,
                    'order_ids' => $orderIds,
                ])) {
                    return view('shadow-store::pages.success', [
                        'orders' => Order::whereIn('id', $orderIds)->where('user_id', $authUserId)->get(),
                    ]);
                }

                $validOrderCount = Order::query()
                    ->whereIn('id', $orderIds)
                    ->where('user_id', $authUserId)
                    ->count();

                if ($validOrderCount !== count($orderIds)) {
                    return redirect()->route('store.index')
                        ->with('error', 'Payment verification failed. Please contact support.');
                }
                
                foreach ($orderIds as $orderId) {
                    $order = Order::find($orderId);
                    if ($order && $order->status === 'pending') {
                        $order->activate($session->payment_intent, $session->subscription ?? null);
                        $webhookNotifier = app(StoreWebhookNotifier::class);
                        $webhookNotifier->sendOrderPaid($order->fresh(['user', 'product']), 'stripe_session:' . $session->id, 'order_checkout');
                    }
                }

                // Clear cart and session data
                $usedCoupon = Session::get('checkout_coupon');
                if ($usedCoupon) {
                    $coupon = Coupon::where('code', $usedCoupon)->first();
                    if ($coupon) {
                        $reference = 'stripe_session:' . $session->id . ':coupon:' . $coupon->id;
                        if ($this->storeCouponRedemption($coupon, $authUserId, $reference)) {
                            $coupon->increment('uses');
                        }
                    }
                }

                $appliedCredit = max(0, (float) Session::get('checkout_credit_applied', 0));
                if ($appliedCredit > 0) {
                    ClientCredit::create([
                        'user_id' => $authUserId,
                        'applied_by' => null,
                        'amount' => -1 * round($appliedCredit, 2),
                        'note' => 'Credit used on checkout: ' . collect($orderIds)
                            ->map(fn (int $id) => Order::find($id)?->order_number)
                            ->filter()
                            ->implode(', '),
                    ]);
                }

                Session::forget(['cart', 'stripe_order_ids', 'pending_order_ids', 'checkout_coupon', 'checkout_proration', 'checkout_credit_applied']);

                return view('shadow-store::pages.success', [
                    'orders' => Order::whereIn('id', $orderIds)->get(),
                ]);
            }
        } catch (Exception $e) {
            report($e);
        }

        return redirect()->route('store.index')
            ->with('error', 'Payment verification failed. Please contact support.');
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

    public function cancel()
    {
        // Mark pending orders as cancelled
        $orderIds = Session::get('pending_order_ids', []);
        Order::whereIn('id', $orderIds)->update(['status' => 'cancelled']);
        
        Session::forget(['pending_order_ids', 'stripe_order_ids', 'checkout_coupon', 'checkout_proration', 'checkout_credit_applied']);

        return redirect()->route('store.cart')
            ->with('error', 'Payment was cancelled.');
    }

    public function orders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['product', 'server'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('shadow-store::pages.orders', compact('orders'));
    }

    public function billing()
    {
        $user = auth()->user();
        $userId = (int) $user->id;
        $now = now();

        // Include billings for servers the user can directly access (owner or subuser).
        $accessibleServerIds = $user->directAccessibleServers()
            ->pluck('servers.id')
            ->unique()
            ->values();

        $creditBalance = SchemaFacade::hasTable('store_client_credits')
            ? max(0, ClientCredit::balanceForUser($userId))
            : 0;

        $orderBillings = collect();
        if (SchemaFacade::hasColumn('store_orders', 'bill_due_at')) {
            $orderBillings = Order::query()
                ->where(function ($query) use ($userId, $accessibleServerIds) {
                    $query->where('user_id', $userId);

                    if ($accessibleServerIds->isNotEmpty()) {
                        $query->orWhereIn('server_id', $accessibleServerIds->all());
                    }
                })
                ->whereNotNull('bill_due_at')
                ->with(['product', 'server'])
                ->orderBy('bill_due_at')
                ->get();
        }

        $serverBillings = collect();
        if (SchemaFacade::hasTable('store_server_billings')) {
            $serverBillings = ServerBilling::query()
                ->where(function ($query) use ($userId, $accessibleServerIds) {
                    $query->where('user_id', $userId);

                    if ($accessibleServerIds->isNotEmpty()) {
                        $query->orWhereIn('server_id', $accessibleServerIds->all());
                    }
                })
                ->whereNotNull('bill_due_at')
                ->with('server')
                ->orderBy('bill_due_at')
                ->get();
        }

        $allDueDates = $orderBillings->pluck('bill_due_at')
            ->merge($serverBillings->pluck('bill_due_at'))
            ->filter();

        $summary = [
            'credit_balance' => $creditBalance,
            'total_items' => $orderBillings->count() + $serverBillings->count(),
            'overdue_items' => $allDueDates->filter(fn ($date) => $date->lt($now))->count(),
            'due_soon_items' => $allDueDates->filter(fn ($date) => $date->gte($now) && $date->lte($now->copy()->addDays(7)))->count(),
        ];

        return view('shadow-store::pages.billing', compact(
            'orderBillings',
            'serverBillings',
            'creditBalance',
            'summary',
            'now'
        ));
    }

    public function makePayment()
    {
        $user = auth()->user();
        $userId = (int) $user->id;
        $now = now();

        $accessibleServerIds = $user->directAccessibleServers()
            ->pluck('servers.id')
            ->unique()
            ->values();

        $dueOrders = Order::query()
            ->where(function ($query) use ($userId, $accessibleServerIds) {
                $query->where('user_id', $userId);

                if ($accessibleServerIds->isNotEmpty()) {
                    $query->orWhereIn('server_id', $accessibleServerIds->all());
                }
            })
            ->whereNotNull('bill_due_at')
            ->where('bill_due_at', '<=', $now)
            ->whereNotNull('product_id')
            ->with('product')
            ->orderBy('bill_due_at')
            ->get();

        $dueServerBillings = collect();
        if (SchemaFacade::hasTable('store_server_billings')) {
            $dueServerBillings = ServerBilling::query()
                ->where(function ($query) use ($userId, $accessibleServerIds) {
                    $query->where('user_id', $userId);

                    if ($accessibleServerIds->isNotEmpty()) {
                        $query->orWhereIn('server_id', $accessibleServerIds->all());
                    }
                })
                ->whereNotNull('bill_due_at')
                ->where('bill_due_at', '<=', $now)
                ->with('server')
                ->orderBy('bill_due_at')
                ->get();
        }

        if ($dueOrders->isEmpty() && $dueServerBillings->isEmpty()) {
            return redirect()->route('store.billing')
                ->with('error', 'No due billings are available for online payment right now.');
        }

        $cart = [];

        // Direct order billings can be added immediately.
        foreach ($dueOrders as $order) {
            if (!$order->product) {
                continue;
            }

            $cart['billing_' . $order->id] = [
                'product_id' => $order->product_id,
                'slots' => $order->slots,
                'variables' => is_array($order->variables) ? $order->variables : [],
                'billing_cycle' => 'monthly',
            ];
        }

        // Map server billings to the latest payable order for each server.
        $serverIds = $dueServerBillings->pluck('server_id')->filter()->unique()->values();
        if ($serverIds->isNotEmpty()) {
            $latestOrders = Order::query()
                ->whereIn('server_id', $serverIds->all())
                ->whereNotNull('product_id')
                ->whereNotIn('status', ['cancelled', 'refunded', 'failed'])
                ->orderByDesc('id')
                ->get()
                ->groupBy('server_id')
                ->map(fn ($group) => $group->first());

            foreach ($dueServerBillings as $billing) {
                if (!$billing->server_id || !isset($latestOrders[$billing->server_id])) {
                    continue;
                }

                $sourceOrder = $latestOrders[$billing->server_id];

                $cart['server_billing_' . $billing->id] = [
                    'product_id' => $sourceOrder->product_id,
                    'slots' => $sourceOrder->slots,
                    'variables' => is_array($sourceOrder->variables) ? $sourceOrder->variables : [],
                    'billing_cycle' => 'monthly',
                ];
            }
        }

        if (empty($cart)) {
            // Fallback: create a direct Stripe payment session for due server billings.
            $invoiceLineItems = [];
            $invoiceBillingIds = [];

            foreach ($dueServerBillings as $billing) {
                $amount = null;

                if (filled($billing->billing_amount) && (float) $billing->billing_amount > 0) {
                    $amount = (float) $billing->billing_amount;
                } elseif (filled($billing->node_amount) && (float) $billing->node_amount > 0) {
                    $amount = (float) $billing->node_amount;
                }

                if ($amount === null) {
                    continue;
                }

                $serverName = $billing->server?->name ?? ('Server #' . $billing->server_id);

                $invoiceLineItems[] = [
                    'price_data' => [
                        'currency' => strtolower(config('shadow-store.currency', 'USD')),
                        'product_data' => [
                            'name' => 'Server Billing - ' . $serverName,
                            'description' => 'Past-due server billing payment',
                        ],
                        'unit_amount' => (int) round($amount * 100),
                    ],
                    'quantity' => 1,
                ];

                $invoiceBillingIds[] = (int) $billing->id;
            }

            if (empty($invoiceLineItems)) {
                return redirect()->route('store.billing')
                    ->with('error', 'Past-due items were found, but no payable invoice amounts are set.');
            }

            try {
                $stripeService = new StripeService();
                $session = $stripeService->createDirectPaymentSession(
                    lineItems: $invoiceLineItems,
                    metadata: [
                        'server_billing_ids' => implode(',', $invoiceBillingIds),
                        'billing_type' => 'server_billing',
                        'user_id' => (string) $userId,
                    ]
                );

                return redirect($session->url);
            } catch (Exception $e) {
                report($e);

                return redirect()->route('store.billing')
                    ->with('error', 'Unable to create billing payment session: ' . $e->getMessage());
            }
        }

        Session::put('cart', $cart);
        Session::forget(['checkout_coupon', 'checkout_proration', 'checkout_credit_applied']);

        return redirect()->route('store.checkout')
            ->with('coupon_success', 'Due billings added to checkout. Complete payment below.');
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
            // Duplicate unique key means the session/event was already processed.
            return false;
        }
    }

    protected function storeCouponRedemption(Coupon $coupon, int $userId, string $reference): bool
    {
        try {
            CouponRedemption::create([
                'coupon_id' => $coupon->id,
                'user_id' => $userId,
                'reference' => $reference,
            ]);

            return true;
        } catch (QueryException $exception) {
            return false;
        }
    }
}
