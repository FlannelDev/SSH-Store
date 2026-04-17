<div>
    @php use ShhStore\Models\StoreSetting; @endphp
    {{-- Breadcrumb --}}
    <div class="border-b border-white/5">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-gray-500">
                <a href="{{ route('shh-store.store') }}" wire:navigate class="transition hover:text-white">Store</a>
                <span class="text-gray-600">/</span>
                <span class="text-gray-400">{{ $product->category->name ?? 'Game' }}</span>
                <span class="text-gray-600">/</span>
                <span class="text-gray-300">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Left: Product Info --}}
            <div class="lg:col-span-2">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <span class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-medium text-gray-300">
                        {{ $product->category->name ?? 'Game' }}
                    </span>
                    @if($product->is_featured)
                        <span class="rounded bg-primary-500/15 px-2 py-0.5 text-[11px] font-medium text-primary-400">Featured</span>
                    @endif
                    @if($product->tier)
                        <span class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-medium text-gray-400">{{ $product->tier }}</span>
                    @endif
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">{{ $product->name }}</h1>

                @if($product->description)
                    <p class="mt-3 max-w-2xl text-sm text-gray-400">{{ $product->description }}</p>
                @endif

                {{-- Hardware Specs --}}
                <div class="mt-6 rounded-lg border border-white/5 bg-white/5">
                    <div class="border-b border-white/5 px-5 py-3">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Hardware Specifications</h3>
                    </div>
                    <div class="divide-y divide-white/5">
                        @if($product->cpu)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-gray-400">CPU</span>
                            <span class="text-sm font-medium text-white">{{ $product->cpu }}</span>
                        </div>
                        @endif
                        @if($product->ram)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-gray-400">RAM</span>
                            <span class="text-sm font-medium text-white">{{ $product->ram }}</span>
                        </div>
                        @endif
                        @if($product->storage)
                        <div class="flex items-center justify-between px-5 py-3">
                            <span class="text-sm text-gray-400">Storage</span>
                            <span class="text-sm font-medium text-white">{{ $product->storage }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Features --}}
                @if($product->features && count($product->features))
                <div class="mt-4 rounded-lg border border-white/5 bg-white/5">
                    <div class="border-b border-white/5 px-5 py-3">
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400">Included Features</h3>
                    </div>
                    <div class="divide-y divide-white/5">
                        @foreach($product->features as $key => $value)
                        <div class="flex items-center justify-between px-5 py-3">
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
                <div class="sticky top-20 rounded-lg border border-white/5 bg-white/5"
                     x-data="{ cycle: @entangle('billingCycle') }">
                    <div class="border-b border-white/5 px-5 py-3">
                        <h3 class="text-sm font-medium text-white">Pricing</h3>
                    </div>
                    <div class="p-5">
                        {{-- Billing Toggle --}}
                        <div class="mb-5 flex gap-1 rounded-lg border border-white/10 bg-gray-950 p-1">
                            <button wire:click="$set('billingCycle', 'monthly')"
                                    class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition {{ $billingCycle === 'monthly' ? 'bg-primary-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Monthly
                            </button>
                            <button wire:click="$set('billingCycle', 'quarterly')"
                                    class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition {{ $billingCycle === 'quarterly' ? 'bg-primary-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Quarterly
                            </button>
                            <button wire:click="$set('billingCycle', 'annually')"
                                    class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition {{ $billingCycle === 'annually' ? 'bg-primary-600 text-white' : 'text-gray-400 hover:text-white' }}">
                                Annual
                            </button>
                        </div>

                        {{-- Price --}}
                        <div class="mb-5 text-center">
                            <p class="text-3xl font-bold text-white">{{ $activePrice }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                @switch($billingCycle)
                                    @case('quarterly') per quarter @break
                                    @case('annually') per year @break
                                    @default per month
                                @endswitch
                            </p>
                        </div>

                        @if($product->in_stock)
                            <a href="{{ route('shh-store.checkout', ['slug' => $product->slug, 'cycle' => $billingCycle]) }}"
                               wire:navigate
                               class="block w-full rounded-lg bg-primary-600 px-5 py-2.5 text-center text-sm font-medium text-white transition hover:bg-primary-500">
                                {{ StoreSetting::getValue('product_cta_text', 'Configure & Deploy') }}
                            </a>
                        @else
                            <button disabled class="w-full cursor-not-allowed rounded-lg bg-white/5 px-5 py-2.5 text-sm font-medium text-gray-500">
                                Out of Stock
                            </button>
                        @endif

                        <p class="mt-3 text-center text-[11px] text-gray-600">
                            {{ StoreSetting::getValue('product_tagline', 'Instant deployment · DDoS protected · Ryzen 9 9950X3D') }}
                        </p>

                        {{-- Quick Specs --}}
                        <div class="mt-5 space-y-2 border-t border-white/5 pt-5">
                            <p class="text-xs font-medium text-gray-500">Quick Specs</p>
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
        <div class="mt-12 border-t border-white/5 pt-8">
            <h2 class="mb-4 text-base font-semibold text-white">Other {{ $product->category->name ?? '' }} configurations</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('shh-store.product', $related->slug) }}"
                       wire:navigate
                       class="group flex flex-col rounded-lg border border-white/5 bg-white/5 transition hover:border-white/10 hover:bg-white/[0.07]">
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="text-sm font-medium text-white">{{ $related->name }}</h3>
                            <div class="mt-2 space-y-1.5">
                                @if($related->ram)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">RAM</span>
                                    <span class="text-gray-300">{{ $related->ram }}</span>
                                </div>
                                @endif
                                @if($related->storage)
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Storage</span>
                                    <span class="text-gray-300">{{ $related->storage }}</span>
                                </div>
                                @endif
                            </div>
                            <div class="mt-auto border-t border-white/5 pt-3 mt-3">
                                <span class="text-base font-semibold text-white">${{ number_format((float) $related->price_monthly, 2) }}</span>
                                <span class="text-xs text-gray-500">/mo</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
