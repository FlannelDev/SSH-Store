<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-white min-h-screen">
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
                <a href="{{ route('store.orders') }}" class="text-gray-300 hover:text-white">Orders</a>
                <a href="{{ route('store.billing') }}" class="text-white font-medium">Billing</a>
                <a href="/" class="text-gray-300 hover:text-white">My Servers</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-6 py-12 max-w-6xl">
        @if(session('error'))
            <div class="bg-red-600/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold">Billing</h1>
                <p class="text-gray-400 mt-2">Review due dates, overdue services, and store credit.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('store.orders') }}" class="text-sm text-blue-400 hover:text-blue-300">View order history</a>
                <form action="{{ route('store.billing.make-payment') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
                    >
                        Make Payment
                    </button>
                </form>
            </div>
        </div>

        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
                <div class="text-sm text-gray-400">Store Credit</div>
                <div class="text-2xl font-bold text-emerald-300 mt-2">${{ number_format($summary['credit_balance'], 2) }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
                <div class="text-sm text-gray-400">Tracked Billing Items</div>
                <div class="text-2xl font-bold mt-2">{{ $summary['total_items'] }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
                <div class="text-sm text-gray-400">Due Within 7 Days</div>
                <div class="text-2xl font-bold text-amber-300 mt-2">{{ $summary['due_soon_items'] }}</div>
            </div>
            <div class="bg-gray-800 border border-gray-700 rounded-xl p-5">
                <div class="text-sm text-gray-400">Overdue</div>
                <div class="text-2xl font-bold text-red-300 mt-2">{{ $summary['overdue_items'] }}</div>
            </div>
        </div>

        <div class="grid xl:grid-cols-2 gap-8">
            <section class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold">Store Orders</h2>
                    <p class="text-sm text-gray-400 mt-1">Billing tied to store orders created through checkout.</p>
                </div>
                @if($orderBillings->isNotEmpty())
                    <div class="divide-y divide-gray-700">
                        @foreach($orderBillings as $order)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-semibold">{{ $order->product->name ?? 'Order' }}</div>
                                        <div class="text-sm text-gray-400">{{ $order->order_number }}</div>
                                        @if($order->server)
                                            <div class="text-sm text-gray-500 mt-1">Server: {{ $order->server->name }}</div>
                                        @endif
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->bill_due_at && $order->bill_due_at->lt($now) ? 'bg-red-500/15 text-red-300' : 'bg-amber-500/15 text-amber-300' }}">
                                        {{ $order->bill_due_at && $order->bill_due_at->lt($now) ? 'Overdue' : 'Active' }}
                                    </span>
                                </div>
                                <div class="grid sm:grid-cols-3 gap-3 mt-4 text-sm">
                                    <div>
                                        <span class="text-gray-400">Due Date:</span>
                                        <span class="ml-1">{{ optional($order->bill_due_at)->format('M j, Y g:i A') ?? 'Not set' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Total:</span>
                                        <span class="ml-1">${{ number_format((float) $order->total, 2) }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Status:</span>
                                        <span class="ml-1">{{ ucfirst($order->status) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-10 text-gray-400 text-sm">No store order billing entries are currently tracked.</div>
                @endif
            </section>

            <section class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold">Existing Server Billing</h2>
                    <p class="text-sm text-gray-400 mt-1">Due dates assigned to your already existing servers.</p>
                </div>
                @if($serverBillings->isNotEmpty())
                    <div class="divide-y divide-gray-700">
                        @foreach($serverBillings as $billing)
                            <div class="px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="font-semibold">{{ $billing->server->name ?? 'Server' }}</div>
                                        <div class="text-sm text-gray-400">{{ $billing->server->uuid_short ?? 'Unknown server' }}</div>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $billing->bill_due_at && $billing->bill_due_at->lt($now) ? 'bg-red-500/15 text-red-300' : 'bg-blue-500/15 text-blue-300' }}">
                                        {{ $billing->bill_due_at && $billing->bill_due_at->lt($now) ? 'Past Due' : 'Tracked' }}
                                    </span>
                                </div>
                                <div class="grid sm:grid-cols-4 gap-3 mt-4 text-sm">
                                    <div>
                                        <span class="text-gray-400">Server Amount:</span>
                                        <span class="ml-1">{{ filled($billing->billing_amount) ? '$' . number_format((float) $billing->billing_amount, 2) : 'Not set' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Node Amount:</span>
                                        <span class="ml-1">{{ filled($billing->node_amount) ? '$' . number_format((float) $billing->node_amount, 2) : 'Not set' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Due Date:</span>
                                        <span class="ml-1">{{ optional($billing->bill_due_at)->format('M j, Y g:i A') ?? 'Not set' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400">Suspended:</span>
                                        <span class="ml-1">{{ !empty($billing->suspended_for_nonpayment_at) ? 'Yes' : 'No' }}</span>
                                    </div>
                                </div>
                                @if($billing->server)
                                    <div class="mt-4 pt-4 border-t border-gray-700">
                                        <a href="/server/{{ $billing->server->uuid_short }}" class="text-blue-400 hover:text-blue-300 text-sm">→ Manage Server</a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-10 text-gray-400 text-sm">No existing server billing entries are currently tracked.</div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
