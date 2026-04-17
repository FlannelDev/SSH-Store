<x-shh-store::layouts.store title="Payment Successful">
    @php use ShhStore\Models\StoreSetting; @endphp
    <div class="flex min-h-[60vh] items-center justify-center px-4">
        <div class="w-full max-w-md text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-success-500/10">
                <svg class="h-8 w-8 text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">{{ StoreSetting::getValue('payment_success_title', 'Payment Successful') }}</h1>
            <p class="mt-2 text-sm text-gray-400">
                Order <span class="font-mono font-medium text-gray-300">{{ $order->order_number }}</span> has been received.
            </p>

            <div class="mt-6 rounded-lg border border-white/5 bg-white/5 text-left">
                <div class="border-b border-white/5 px-5 py-3">
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Order Details</h3>
                </div>
                <div class="divide-y divide-white/5">
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-xs text-gray-500">Product</span>
                        <span class="text-xs font-medium text-white">{{ $order->product->name }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-xs text-gray-500">Billing</span>
                        <span class="text-xs font-medium text-white capitalize">{{ $order->billing_cycle }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-xs text-gray-500">Amount</span>
                        <span class="text-xs font-medium text-white">${{ number_format((float) $order->amount, 2) }} {{ $order->currency }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-xs text-gray-500">Payment</span>
                        <span class="text-xs font-medium text-white capitalize">{{ $order->payment_method }}</span>
                    </div>
                    <div class="flex justify-between px-5 py-3">
                        <span class="text-xs text-gray-500">Status</span>
                        <span class="rounded bg-success-500/15 px-2 py-0.5 text-xs font-medium text-success-400">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-600">
                Confirmation sent to <span class="text-gray-400">{{ $order->customer_email }}</span>.
                {{ StoreSetting::getValue('payment_success_message', 'Your server will be provisioned shortly.') }}
            </p>

            <div class="mt-6 flex justify-center">
                <a href="{{ route('shh-store.store') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-medium text-gray-300 transition hover:border-white/20 hover:text-white">
                    Back to Store
                </a>
            </div>
        </div>
    </div>
</x-shh-store::layouts.store>
