<x-shh-store::layouts.store title="Payment Cancelled">
    <div class="flex min-h-[60vh] items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-red-500/10">
                <svg class="h-10 w-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Payment Cancelled</h1>
            <p class="mt-3 text-sm text-gray-400">
                Your order <span class="font-mono font-semibold text-gray-300">{{ $order->order_number }}</span> has been cancelled.
                You have not been charged.
            </p>

            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ route('shh-store.product', $order->product->slug) }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500">
                    Try Again
                </a>
                <a href="{{ route('shh-store.store') }}" class="rounded-lg border border-gray-800 bg-gray-900 px-5 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white">
                    Back to Store
                </a>
            </div>
        </div>
    </div>
</x-shh-store::layouts.store>
