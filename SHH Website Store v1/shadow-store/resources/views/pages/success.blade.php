<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Complete - Shadow Haven Hosting</title>
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
        </div>
    </header>

    <main class="container mx-auto px-6 py-16 max-w-2xl text-center">
        <div class="w-20 h-20 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-4xl font-bold mb-4">Payment Successful!</h1>
        <p class="text-gray-400 text-lg mb-8">
            Your order has been processed and your server is being deployed.
        </p>

        @if($orders->count() > 0)
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 text-left mb-8">
                <h2 class="text-xl font-semibold mb-4">Order Details</h2>
                @foreach($orders as $order)
                    <div class="flex justify-between items-center py-3 border-b border-gray-700 last:border-0">
                        <div>
                            <div class="font-medium">{{ $order->product->name }}</div>
                            <div class="text-sm text-gray-400">{{ $order->order_number }}</div>
                            @if($order->slots)
                                <div class="text-sm text-blue-400">{{ $order->slots }} slots</div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="font-semibold">${{ number_format($order->total, 2) }}/mo</div>
                            <div class="text-sm text-green-400">{{ ucfirst($order->status) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="space-y-4">
            <p class="text-gray-400">
                Your server will be ready in a few moments. You can manage it from your dashboard.
            </p>
            <div class="flex gap-4 justify-center">
                <a href="/" class="bg-blue-600 hover:bg-blue-700 px-8 py-3 rounded-lg font-semibold transition">
                    Go to My Servers
                </a>
                <a href="/store" class="border border-gray-600 hover:border-gray-500 px-8 py-3 rounded-lg font-semibold transition">
                    Continue Shopping
                </a>
            </div>
        </div>
    </main>
</body>
</html>
