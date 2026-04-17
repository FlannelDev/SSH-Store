<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <header class="border-b border-gray-800">
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
                <a href="{{ route('store.billing') }}" class="text-gray-300 hover:text-white">Billing</a>
                <a href="/" class="text-gray-300 hover:text-white">My Servers</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-4xl">
        <h1 class="text-3xl font-bold mb-8">My Orders</h1>

        @if($orders->count() > 0)
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold">{{ $order->product->name }}</h3>
                                <p class="text-sm text-gray-400">{{ $order->order_number }}</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($order->status === 'paid') bg-green-600/20 text-green-400
                                @elseif($order->status === 'pending') bg-yellow-600/20 text-yellow-400
                                @elseif($order->status === 'cancelled') bg-red-600/20 text-red-400
                                @else bg-gray-600/20 text-gray-400
                                @endif">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            @if($order->slots)
                                <div>
                                    <span class="text-gray-400">Slots:</span>
                                    <span class="ml-1">{{ $order->slots }}</span>
                                </div>
                            @endif
                            <div>
                                <span class="text-gray-400">Total:</span>
                                <span class="ml-1">${{ number_format($order->total, 2) }}/mo</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Created:</span>
                                <span class="ml-1">{{ $order->created_at->format('M j, Y') }}</span>
                            </div>
                            @if(!empty($order->coupon_code))
                                <div>
                                    <span class="text-gray-400">Coupon:</span>
                                    <span class="ml-1 text-emerald-400">{{ $order->coupon_code }}</span>
                                </div>
                            @endif
                            @if($order->expires_at)
                                <div>
                                    <span class="text-gray-400">Expires:</span>
                                    <span class="ml-1">{{ $order->expires_at->format('M j, Y') }}</span>
                                </div>
                            @endif
                        </div>
                        @if($order->server)
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <a href="/server/{{ $order->server->uuid_short }}" class="text-blue-400 hover:text-blue-300 text-sm">
                                    → Manage Server
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <p class="text-gray-400 mb-6">You don't have any orders yet.</p>
                <a href="/store" class="inline-block bg-blue-600 hover:bg-blue-700 px-6 py-3 rounded-lg font-semibold transition">
                    Browse Store
                </a>
            </div>
        @endif
    </main>
</body>
</html>
