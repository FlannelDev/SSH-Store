<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Shadow Haven Hosting</title>
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
                    <a href="/" class="text-gray-300 hover:text-white">My Servers</a>
                @else
                    <a href="/login" class="text-gray-300 hover:text-white">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-4xl">
        <h1 class="text-3xl font-bold mb-8">Shopping Cart</h1>

        @if(session('success'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if(count($cartItems) > 0)
            <div class="space-y-4 mb-8">
                @foreach($cartItems as $item)
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold">{{ $item['product']->name }}</h3>
                                <p class="text-gray-400 text-sm">{{ $item['product']->game }}</p>
                                @if(!empty($item['tier_label']))
                                    <p class="text-emerald-400 text-sm mt-1">{{ $item['tier_label'] }}</p>
                                @endif
                                @if($item['slots'])
                                    <p class="text-blue-400 text-sm mt-1">{{ $item['slots'] }} player slots</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="text-xl font-bold">${{ number_format($item['price'], 2) }}/mo</div>
                                @if($item['slots'] && empty($item['tier_label']))
                                    <div class="text-sm text-gray-400">
                                        ${{ number_format($item['product']->price_per_slot, 2) }}/slot × {{ $item['slots'] }}
                                    </div>
                                @endif
                            </div>
                            <form action="{{ route('store.cart.remove') }}" method="POST" class="ml-4">
                                @csrf
                                <input type="hidden" name="key" value="{{ $item['key'] }}">
                                <button type="submit" class="text-red-400 hover:text-red-300 p-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Order Summary -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h2 class="text-xl font-semibold mb-4">Order Summary</h2>
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
                    <div class="border-t border-gray-700 pt-3">
                        <div class="flex justify-between text-lg font-semibold">
                            <span>Monthly Total</span>
                            <span class="text-blue-400">${{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    @auth
                        <a href="{{ route('store.checkout') }}" class="block w-full bg-blue-600 hover:bg-blue-700 py-3 rounded-lg font-semibold text-center transition">
                            Proceed to Checkout
                        </a>
                    @else
                        <a href="/login?redirect=/store/checkout" class="block w-full bg-blue-600 hover:bg-blue-700 py-3 rounded-lg font-semibold text-center transition">
                            Login to Checkout
                        </a>
                    @endauth
                    <a href="/store" class="block w-full border border-gray-600 hover:border-gray-500 py-3 rounded-lg font-semibold text-center transition">
                        Continue Shopping
                    </a>
                </div>
            </div>
        @else
            <div class="text-center py-16">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h2 class="text-xl font-semibold mb-2">Your cart is empty</h2>
                <p class="text-gray-400 mb-6">Browse our game servers and add something to your cart.</p>
                <a href="/store" class="inline-block bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold transition">
                    Browse Store
                </a>
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="py-12 px-6 border-t border-gray-800 mt-auto">
        <div class="container mx-auto text-center text-sm text-gray-500">
            © {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
        </div>
    </footer>
</body>
</html>
