<div>
    {{-- Breadcrumb --}}
    <div class="border-b border-gray-800/60 bg-gray-900/30">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500">
                <a href="{{ route('store') }}" wire:navigate class="transition hover:text-white">Store</a>
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-gray-400">{{ $product->category->name ?? 'Game' }}</span>
                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-white">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-3">
            {{-- Left: Product Info --}}
            <div class="lg:col-span-2">
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-md bg-indigo-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-400">
                        {{ $product->category->name ?? 'Game' }}
                    </span>
                    @if($product->is_featured)
                        <span class="rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-bold uppercase text-amber-400">Featured</span>
                    @endif
                    @if($product->tier)
                        <span class="rounded-md bg-gray-800 px-2.5 py-1 text-xs font-semibold text-gray-300">{{ $product->tier }}</span>
                    @endif
                </div>

                <h1 class="text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $product->name }}</h1>

                @if($product->description)
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-gray-400">{{ $product->description }}</p>
                @endif

                {{-- Hardware Specs Card --}}
                <div class="mt-8 overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50">
                    <div class="border-b border-gray-800/60 bg-gray-900/30 px-6 py-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Hardware Specifications</h3>
                    </div>
                    <div class="divide-y divide-gray-800/60">
                        @if($product->cpu)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500/10">
                                    <svg class="h-5 w-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                </div>
                                <span class="text-sm font-medium uppercase tracking-wider text-gray-500">CPU</span>
                            </div>
                            <span class="text-sm font-semibold text-white">{{ $product->cpu }}</span>
                        </div>
                        @endif
                        @if($product->ram)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-500/10">
                                    <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <span class="text-sm font-medium uppercase tracking-wider text-gray-500">RAM</span>
                            </div>
                            <span class="text-sm font-semibold text-white">{{ $product->ram }}</span>
                        </div>
                        @endif
                        @if($product->storage)
                        <div class="flex items-center justify-between px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500/10">
                                    <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                                </div>
                                <span class="text-sm font-medium uppercase tracking-wider text-gray-500">Storage</span>
                            </div>
                            <span class="text-sm font-semibold text-white">{{ $product->storage }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Features --}}
                @if($product->features && count($product->features))
                <div class="mt-6 overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50">
                    <div class="border-b border-gray-800/60 bg-gray-900/30 px-6 py-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Included Features</h3>
                    </div>
                    <div class="divide-y divide-gray-800/60">
                        @foreach($product->features as $key => $value)
                        <div class="flex items-center justify-between px-6 py-3">
                            <span class="text-sm text-gray-400">{{ $key }}</span>
                            <span class="text-sm font-medium text-white">{{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Right: Pricing Sidebar --}}
            <div class="lg:col-span-1">
                <div class="sticky top-24 overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50"
                     x-data="{ cycle: @entangle('billingCycle') }">
                    <div class="border-b border-gray-800/60 bg-gray-900/30 px-6 py-4">
                        <h3 class="text-sm font-semibold text-white">Pricing</h3>
                    </div>
                    <div class="p-6">
                        {{-- Billing Toggle --}}
                        <div class="mb-6 flex gap-1 rounded-lg border border-gray-800 bg-gray-950 p-1">
                            <button wire:click="$set('billingCycle', 'monthly')"
                                    class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition {{ $billingCycle === 'monthly' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Monthly
                            </button>
                            <button wire:click="$set('billingCycle', 'quarterly')"
                                    class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition {{ $billingCycle === 'quarterly' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Quarterly
                            </button>
                            <button wire:click="$set('billingCycle', 'annually')"
                                    class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition {{ $billingCycle === 'annually' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Annual
                            </button>
                        </div>

                        {{-- Price Display --}}
                        <div class="mb-6 text-center">
                            <p class="text-4xl font-black text-white">{{ $activePrice }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                @switch($billingCycle)
                                    @case('quarterly') per quarter @break
                                    @case('annually') per year @break
                                    @default per month
                                @endswitch
                            </p>
                        </div>

                        {{-- CTA --}}
                        @if($product->in_stock)
                            <a href="{{ route('checkout', ['slug' => $product->slug, 'cycle' => $billingCycle]) }}"
                               wire:navigate
                               class="block w-full rounded-lg bg-indigo-600 px-6 py-3.5 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500 active:scale-[0.98]">
                                Configure & Deploy
                            </a>
                        @else
                            <button disabled class="w-full cursor-not-allowed rounded-lg bg-gray-800 px-6 py-3.5 text-sm font-bold text-gray-500">
                                Out of Stock
                            </button>
                        @endif

                        <p class="mt-3 text-center text-[10px] text-gray-600">
                            Instant deployment · DDoS protected · Ryzen 9 9950X3D
                        </p>

                        {{-- Quick specs summary --}}
                        <div class="mt-6 space-y-3 border-t border-gray-800/60 pt-6">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Quick Specs</p>
                            @if($product->cpu)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">CPU</span>
                                <span class="text-gray-300">{{ $product->cpu }}</span>
                            </div>
                            @endif
                            @if($product->ram)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">RAM</span>
                                <span class="text-gray-300">{{ $product->ram }}</span>
                            </div>
                            @endif
                            @if($product->storage)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Storage</span>
                                <span class="text-gray-300">{{ $product->storage }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Related Products --}}
        @if($relatedProducts->isNotEmpty())
        <div class="mt-16 border-t border-gray-800/60 pt-12">
            <h2 class="mb-6 text-lg font-bold text-white">Other {{ $product->category->name ?? '' }} configurations</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('store.product', $related->slug) }}"
                       wire:navigate
                       class="group flex flex-col overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50 transition hover:border-indigo-500/40 hover:bg-gray-900/80">
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300">{{ $related->name }}</h3>
                            <div class="mt-3 space-y-1.5">
                                @if($related->ram)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">RAM</span>
                                    <span class="text-gray-300">{{ $related->ram }}</span>
                                </div>
                                @endif
                                @if($related->storage)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Storage</span>
                                    <span class="text-gray-300">{{ $related->storage }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="mt-auto border-t border-gray-800/60 pt-3 mt-4">
                                <span class="text-lg font-bold text-white">${{ number_format((float) $related->price_monthly, 2) }}</span>
                                <span class="text-xs text-gray-600">/mo</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
