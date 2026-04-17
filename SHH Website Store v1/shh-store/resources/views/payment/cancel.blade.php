<x-shh-store::layouts.store title="Payment Cancelled">
    <div class="flex min-h-[60vh] items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-danger-500/10">
                <svg class="h-8 w-8 text-danger-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Payment Cancelled</h1>
            <p class="mt-2 text-sm text-gray-400">
                Order <span class="font-mono font-medium text-gray-300">{{ $order->order_number }}</span> has been cancelled. You have not been charged.
            </p>

            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('shh-store.product', $order->product->slug) }}" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-500">
                    Try Again
                </a>
                <a href="{{ route('shh-store.store') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-medium text-gray-300 transition hover:border-white/20 hover:text-white">
                    Back to Store
                </a>
            </div>
        </div>
    </div>
</x-shh-store::layouts.store>
