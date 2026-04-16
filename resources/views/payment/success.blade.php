<x-shh-store::layouts.store title="Payment Successful">
    <div class="flex min-h-[60vh] items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/10">
                <svg class="h-10 w-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-white">Payment Successful!</h1>
            <p class="mt-3 text-sm text-gray-400">
                Your order <span class="font-mono font-semibold text-indigo-400">{{ $order->order_number }}</span> has been received.
            </p>

            <div class="mt-8 overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50 text-left">
                <div class="border-b border-gray-800/60 bg-gray-900/30 px-6 py-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Order Details</h3>
                </div>
                <div class="divide-y divide-gray-800/60">
                    <div class="flex justify-between px-6 py-3">
                        <span class="text-xs text-gray-500">Product</span>
                        <span class="text-xs font-medium text-white">{{ $order->product->name }}</span>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <span class="text-xs text-gray-500">Billing</span>
                        <span class="text-xs font-medium text-white capitalize">{{ $order->billing_cycle }}</span>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <span class="text-xs text-gray-500">Amount</span>
                        <span class="text-xs font-medium text-white">${{ number_format((float) $order->amount, 2) }} {{ $order->currency }}</span>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <span class="text-xs text-gray-500">Payment</span>
                        <span class="text-xs font-medium text-white capitalize">{{ $order->payment_method }}</span>
                    </div>
                    <div class="flex justify-between px-6 py-3">
                        <span class="text-xs text-gray-500">Status</span>
                        <span class="inline-flex rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-400">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="mt-6 text-xs text-gray-600">
                A confirmation will be sent to <span class="text-gray-400">{{ $order->customer_email }}</span>.
                Your server will be provisioned shortly.
            </p>

            <div class="mt-8 flex justify-center gap-3">
                <a href="{{ route('shh-store.store') }}" class="rounded-lg border border-gray-800 bg-gray-900 px-5 py-2.5 text-sm font-semibold text-gray-300 transition hover:bg-gray-800 hover:text-white">
                    Back to Store
                </a>
            </div>
        </div>
    </div>
</x-shh-store::layouts.store>
