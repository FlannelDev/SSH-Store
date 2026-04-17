<div>
    {{-- Breadcrumb --}}
    <div class="border-b border-white/5">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500">
                <a href="{{ route('shh-store.store') }}" wire:navigate class="transition hover:text-white">Store</a>
                <span class="text-gray-600">/</span>
                <a href="{{ route('shh-store.product', $product->slug) }}" wire:navigate class="transition hover:text-white">{{ $product->name }}</a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-300">Checkout</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        @if(session('error'))
            <div class="mb-4 rounded-lg border border-danger-500/20 bg-danger-500/10 p-3">
                <p class="text-sm text-danger-400">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Left: Order Summary --}}
            <div class="lg:col-span-3">
                <h1 class="text-xl font-bold text-white">Checkout</h1>
                <p class="mt-1 text-sm text-gray-500">Complete your order for {{ $product->name }}</p>

                {{-- Order Summary --}}
                <div class="mt-5 rounded-lg border border-white/5 bg-white/5">
                    <div class="border-b border-white/5 px-5 py-3">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Order Summary</h3>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h4 class="text-sm font-medium text-white">{{ $product->name }}</h4>
                                <p class="mt-1 text-xs text-gray-500">{{ $product->category->name ?? '' }} · {{ $product->tier }}</p>
                                <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-400">
                                    @if($product->cpu)
                                        <span>{{ $product->cpu }}</span>
                                    @endif
                                    @if($product->ram)
                                        <span>{{ $product->ram }} RAM</span>
                                    @endif
                                    @if($product->storage)
                                        <span>{{ $product->storage }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-base font-semibold text-white">{{ $formattedPrice }}</p>
                                <p class="text-xs text-gray-500">{{ $cycleLabel }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer Info --}}
                <div class="mt-4 rounded-lg border border-white/5 bg-white/5">
                    <div class="border-b border-white/5 px-5 py-3">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Your Information</h3>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label for="customerName" class="block text-xs font-medium text-gray-400">Full Name</label>
                            <input type="text" id="customerName"
                                   wire:model="customerName"
                                   class="mt-1.5 w-full rounded-lg border border-white/10 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                   placeholder="Your name">
                            @error('customerName')
                                <p class="mt-1 text-xs text-danger-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="customerEmail" class="block text-xs font-medium text-gray-400">Email Address</label>
                            <input type="email" id="customerEmail"
                                   wire:model="customerEmail"
                                   class="mt-1.5 w-full rounded-lg border border-white/10 bg-gray-950 px-4 py-2.5 text-sm text-white placeholder-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                                   placeholder="you@email.com">
                            @error('customerEmail')
                                <p class="mt-1 text-xs text-danger-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Payment --}}
            <div class="lg:col-span-2">
                <div class="sticky top-20">
                    <div class="rounded-lg border border-white/5 bg-white/5">
                        <div class="border-b border-white/5 px-5 py-3">
                            <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Payment Method</h3>
                        </div>
                        <div class="space-y-3 p-5">
                            {{-- Stripe --}}
                            <button wire:click="payWithStripe"
                                    wire:loading.attr="disabled"
                                    @if($processing) disabled @endif
                                    class="group relative w-full rounded-lg border border-white/10 bg-gray-950 p-4 text-left transition hover:border-white/20 disabled:cursor-wait disabled:opacity-60">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-white/10">
                                        <svg class="h-4 w-4 text-gray-300" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.591-7.305z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">Pay with Card</p>
                                        <p class="text-xs text-gray-500">Visa, Mastercard, Amex & more</p>
                                    </div>
                                </div>
                                <div wire:loading wire:target="payWithStripe" class="absolute inset-0 flex items-center justify-center rounded-lg bg-gray-950/80">
                                    <svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            </button>

                            {{-- PayPal --}}
                            <button wire:click="payWithPaypal"
                                    wire:loading.attr="disabled"
                                    @if($processing) disabled @endif
                                    class="group relative w-full rounded-lg border border-white/10 bg-gray-950 p-4 text-left transition hover:border-white/20 disabled:cursor-wait disabled:opacity-60">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-white/10">
                                        <svg class="h-4 w-4 text-gray-300" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944.901C5.026.382 5.474 0 5.998 0h7.46c2.57 0 4.578.543 5.69 1.81 1.01 1.15 1.304 2.42 1.012 4.287-.023.143-.047.288-.077.437-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.12 7.106zm14.146-14.42a3.35 3.35 0 0 0-.607-.541c1.907 1.375 2.14 3.818 1.397 7.63-.983 5.05-4.349 6.797-8.647 6.797h-2.19c-.524 0-.968.382-1.05.9l-1.5 9.516a.641.641 0 0 1-.633.542H4.178a.641.641 0 0 1-.633-.74l.652-4.131"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">Pay with PayPal</p>
                                        <p class="text-xs text-gray-500">PayPal balance or linked cards</p>
                                    </div>
                                </div>
                                <div wire:loading wire:target="payWithPaypal" class="absolute inset-0 flex items-center justify-center rounded-lg bg-gray-950/80">
                                    <svg class="h-5 w-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>

                    {{-- Total --}}
                    <div class="mt-3 rounded-lg border border-white/5 bg-white/5 p-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">Total</span>
                            <span class="text-lg font-bold text-white">{{ $formattedPrice }}</span>
                        </div>
                        <p class="mt-1 text-right text-xs text-gray-600">Billed {{ $cycleLabel }}</p>
                    </div>

                    <p class="mt-3 text-center text-[11px] text-gray-600">SSL Encrypted · Secure Payments</p>
                </div>
            </div>
        </div>
    </div>
</div>
