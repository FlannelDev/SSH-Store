<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Header -->
    <header class="border-b border-gray-800 sticky top-0 bg-gray-900/95 backdrop-blur z-50">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ $storeHeader['store_url'] }}" class="flex items-center gap-3">
                @include('shadow-store::pages.partials.store-logo', [
                    'sizeClass' => 'h-10 w-10',
                    'containerClass' => 'rounded-lg bg-blue-600 p-1',
                    'imageClass' => 'h-full w-full object-contain',
                    'textClass' => 'font-bold text-lg text-white',
                ])
                <div>
                    <div class="font-semibold text-lg">{{ $storeHeader['brand_name'] }}</div>
                    <div class="text-xs uppercase tracking-[0.18em] text-gray-500">{{ $storeHeader['brand_tagline'] }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-6">
                <a href="{{ $storeHeader['store_url'] }}" class="text-gray-300 hover:text-white">{{ $storeHeader['store_label'] }}</a>
                <a href="{{ $storeHeader['wiki_url'] }}" class="text-gray-300 hover:text-white">{{ $storeHeader['wiki_label'] }}</a>
                @auth
                    <a href="{{ route('store.billing') }}" class="text-gray-300 hover:text-white">Billing</a>
                @endauth
                <a href="{{ route('store.cart') }}" class="text-gray-300 hover:text-white">Cart</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-5xl">
        <h1 class="text-3xl font-bold mb-8">Checkout</h1>

        @if(session('error'))
            <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if(session('coupon_success'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('coupon_success') }}
            </div>
        @endif

        @if(session('coupon_error'))
            <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                {{ session('coupon_error') }}
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Order Summary -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
                    <div class="space-y-4">
                        @foreach($cartItems as $item)
                            <div class="flex justify-between items-center py-3 border-b border-gray-700 last:border-0">
                                <div>
                                    <div class="font-medium">{{ $item['product']->name }}</div>
                                    @if(!empty($item['tier_label']))
                                        <div class="text-sm text-emerald-400">{{ $item['tier_label'] }}</div>
                                    @endif
                                    @if($item['slots'])
                                        <div class="text-sm text-gray-400">{{ $item['slots'] }} player slots</div>
                                    @endif
                                    @if(!empty($item['billing_label']))
                                        <div class="text-xs text-gray-500">{{ $item['billing_label'] }}</div>
                                    @endif
                                </div>
                                <div class="font-semibold">${{ number_format($item['price'], 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h2 class="text-xl font-semibold mb-4">Payment Method</h2>
                    
                    @if(!$stripeEnabled && !$paypalEnabled)
                        <div class="bg-yellow-600/20 border border-yellow-500 text-yellow-400 px-4 py-3 rounded-lg">
                            <p class="font-medium">Payment methods not configured</p>
                            <p class="text-sm mt-1">Please contact support to complete your order, or check back later.</p>
                        </div>
                    @else
                        <form action="{{ route('store.checkout.process') }}" method="POST" class="mt-6">
                            @csrf
                            <div class="space-y-3">
                                @if($stripeEnabled)
                                    <label class="flex items-center gap-3 p-4 bg-gray-900/50 rounded-lg border border-gray-700 cursor-pointer hover:border-blue-500 transition">
                                        <input type="radio" name="payment_method" value="stripe" class="text-blue-600" checked>
                                        <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none">
                                            <rect width="32" height="32" rx="6" fill="#635BFF"/>
                                            <path d="M15.3 12.7c0-.8.7-1.1 1.8-1.1 1.6 0 3.6.5 5.2 1.4V8.6c-1.7-.7-3.5-1-5.2-1-4.3 0-7.1 2.2-7.1 6 0 5.8 8 4.9 8 7.4 0 1-.8 1.3-2 1.3-1.7 0-4-.7-5.7-1.7v4.5c1.9.8 3.9 1.2 5.7 1.2 4.4 0 7.4-2.2 7.4-6 0-6.3-8-5.2-8-7.6z" fill="#fff"/>
                                        </svg>
                                        <span class="font-medium">Credit / Debit Card</span>
                                    </label>
                                @endif

                                @if($paypalEnabled)
                                    <label class="flex items-center gap-3 p-4 bg-gray-900/50 rounded-lg border border-gray-700 cursor-pointer hover:border-blue-500 transition">
                                        <input type="radio" name="payment_method" value="paypal" class="text-blue-600" {{ !$stripeEnabled ? 'checked' : '' }}>
                                        <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none">
                                            <rect width="32" height="32" rx="6" fill="#003087"/>
                                            <path d="M23.4 10.4c0 3.4-2.8 6.2-6.2 6.2h-1.6l-.8 5.2h-2.4l.4-2.4h-2l1.6-10.4h4.8c3.4 0 6.2 1.4 6.2 1.4z" fill="#009cde"/>
                                            <path d="M21.4 8.4c0 3.4-2.8 6.2-6.2 6.2h-1.6l-.8 5.2h-2.4l2-13h4.8c2.4 0 4.2 1.6 4.2 1.6z" fill="#012169"/>
                                        </svg>
                                        <span class="font-medium">PayPal</span>
                                    </label>
                                @endif
                            </div>

                            <label class="flex items-start gap-3 text-sm text-gray-300 mt-5">
                                <input type="checkbox" name="accept_msa" value="1" required class="mt-0.5 rounded border-gray-600 bg-gray-900 text-blue-500 focus:ring-blue-500">
                                <span>
                                    I have read and understand the Shadow Haven Hosting Master Services Agreement.
                                    <a href="{{ route('store.msa') }}" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300 underline">Read Agreement</a>
                                </span>
                            </label>
                            @error('accept_msa')
                                <p class="text-xs text-red-400 mt-2">{{ $message }}</p>
                            @enderror

                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-lg font-semibold text-lg transition">
                                Pay ${{ number_format($total, 2) }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Price Breakdown -->
            <div>
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">Price Breakdown</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-400">Subtotal</span>
                            <span>${{ number_format($subtotal, 2) }}</span>
                        </div>
                        @if($taxRate > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-400">Tax ({{ $taxRate }}%)</span>
                                <span>${{ number_format($tax, 2) }}</span>
                            </div>
                        @endif
                        @if($discount > 0)
                            <div class="flex justify-between text-green-400">
                                <span>Discount
                                    @if($coupon->first_month_only ?? true)
                                        (first month only)
                                    @endif
                                    @if($coupon->type === 'percentage')
                                        ({{ rtrim(rtrim(number_format((float)$coupon->value, 2), '0'), '.') }}% off)
                                    @else
                                        (Fixed)
                                    @endif
                                </span>
                                <span>-${{ number_format($discount, 2) }}</span>
                            </div>
                        @endif
                        @if(!empty($proration) && $proration > 0)
                            <div class="flex justify-between text-amber-300">
                                <span>Proration credit</span>
                                <span>-${{ number_format($proration, 2) }}</span>
                            </div>
                        @endif
                        @if(!empty($creditApplied) && $creditApplied > 0)
                            <div class="flex justify-between text-emerald-300">
                                <span>Store credit</span>
                                <span>-${{ number_format($creditApplied, 2) }}</span>
                            </div>
                        @endif
                        <div class="border-t border-gray-700 pt-3">
                            <div class="flex justify-between text-xl font-bold">
                                <span>Total Due Today</span>
                                <span class="text-blue-400">${{ number_format($total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Coupon Code -->
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        @if($coupon)
                            <div class="flex items-center justify-between bg-green-600/10 border border-green-600/30 rounded-lg px-3 py-2">
                                <span class="text-green-400 text-sm font-medium">&#x1F3F7; {{ $coupon->code }}</span>
                                <form action="{{ route('store.checkout.coupon.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-400 transition text-xs ml-3">Remove</button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                @if($coupon->type === 'affiliate')
                                    Affiliate tracking code applied. This code does not provide a discount.
                                @elseif($coupon->first_month_only ?? true)
                                    Coupon discount applies to the first month only.
                                @else
                                    Coupon discount applies to the full billing period.
                                @endif
                            </p>
                        @else
                            <form action="{{ route('store.checkout.coupon') }}" method="POST" class="flex gap-2">
                                @csrf
                                <input type="text" name="coupon_code" placeholder="Coupon code"
                                    class="flex-1 bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500"
                                    style="text-transform:uppercase">
                                <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap">
                                    Apply
                                </button>
                            </form>
                            <p class="text-xs text-gray-500 mt-2">Coupons may apply to the first month or full billing period depending on the coupon.</p>
                        @endif
                    </div>

                    @if(!empty($canProrate) && $canProrate)
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <h3 class="text-sm font-semibold mb-2">Admin Proration</h3>
                        @if(!empty($proration) && $proration > 0)
                            <div class="flex items-center justify-between bg-amber-500/10 border border-amber-500/30 rounded-lg px-3 py-2 mb-2">
                                <span class="text-amber-300 text-sm font-medium">Credit: ${{ number_format($proration, 2) }}</span>
                                <form action="{{ route('store.checkout.proration.remove') }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-500 hover:text-red-400 transition text-xs ml-3">Remove</button>
                                </form>
                            </div>
                        @endif
                        <form action="{{ route('store.checkout.proration') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="number" name="amount" step="0.01" min="0" placeholder="Proration amount"
                                class="flex-1 bg-gray-900 border border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <button type="submit" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap">
                                Set
                            </button>
                        </form>
                    </div>
                    @endif

                    <p class="text-xs text-gray-500 mt-4">
                        @if(!empty($creditBalance) && $creditBalance > 0)
                            Available store credit: ${{ number_format($creditBalance, 2) }}.
                        @else
                            Available store credit: $0.00.
                        @endif
                    </p>

                    <p class="text-xs text-gray-500 mt-2">
                        By completing this purchase, you agree to our Terms of Service and Privacy Policy.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-gray-800 mt-auto">
        <div class="container mx-auto text-center text-sm text-gray-500">
            © {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
        </div>
    </footer>
</body>
</html>
