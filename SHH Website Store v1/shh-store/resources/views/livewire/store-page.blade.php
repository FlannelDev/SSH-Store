<div>
    @php use ShhStore\Models\StoreSetting; @endphp
    {{-- Hero --}}
    <section class="border-b border-white/5">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ StoreSetting::getValue('hero_title', 'Game Servers') }}</h1>
                <p class="mt-3 text-base text-gray-400">
                    {{ StoreSetting::getValue('hero_subtitle', '200+ supported games on Ryzen 9 9950X3D with NVMe storage, sensible density, and clean billing.') }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="#catalog" class="rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-500">
                        {{ StoreSetting::getValue('hero_cta_text', 'Browse Catalog') }}
                    </a>
                    <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-medium text-gray-300 transition hover:border-white/20 hover:text-white">
                        {{ StoreSetting::getValue('hero_cta2_text', 'Join Discord') }}
                    </a>
                </div>
            </div>

            <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @php
                $heroStats = [];
                foreach (range(1, 4) as $i) {
                    $heroStats[] = [
                        'value' => StoreSetting::getValue("hero_stat{$i}_value", match ($i) {
                            1 => '$20', 2 => '200+', 3 => '9950X3D', 4 => 'NVMe',
                        }),
                        'label' => StoreSetting::getValue("hero_stat{$i}_label", match ($i) {
                            1 => 'Starting monthly', 2 => 'Games supported', 3 => 'Ryzen CPU', 4 => 'Fast storage',
                        }),
                    ];
                }
                @endphp
                @foreach($heroStats as $stat)
                <div class="rounded-lg border border-white/5 bg-white/5 px-4 py-3">
                    <p class="text-lg font-semibold text-white">{{ $stat['value'] }}</p>
                    <p class="text-xs text-gray-500">{{ $stat['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Featured Products --}}
    @if($featuredProducts->isNotEmpty())
    <section class="border-b border-white/5 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-white">{{ StoreSetting::getValue('featured_heading', 'Featured') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ StoreSetting::getValue('featured_subtitle', 'Hand-picked configurations for a quick start.') }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($featuredProducts as $product)
                    <a href="{{ route('shh-store.product', $product->slug) }}"
                       wire:navigate
                       class="group flex flex-col rounded-lg border border-white/5 bg-white/5 transition hover:border-white/10 hover:bg-white/[0.07]">
                        <div class="flex flex-1 flex-col p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-medium text-gray-300">
                                    {{ $product->category->name ?? 'Game' }}
                                </span>
                                <span class="rounded bg-primary-500/15 px-2 py-0.5 text-[11px] font-medium text-primary-400">Featured</span>
                            </div>
                            <h3 class="text-sm font-medium text-white">{{ $product->name }}</h3>
                            <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $product->description }}</p>

                            <div class="mt-3 space-y-1.5">
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

                            <div class="mt-auto pt-3">
                                <div class="border-t border-white/5 pt-3">
                                    <span class="text-base font-semibold text-white">${{ number_format($product->calculatePrice('monthly'), 2) }}</span>
                                    <span class="text-xs text-gray-500">{{ $product->cyclePriceSuffix('monthly') }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Catalog --}}
    <section id="catalog" class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-white">{{ StoreSetting::getValue('catalog_heading', 'All Configurations') }}</h2>
            </div>

            {{-- Filters --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button wire:click="selectCategory('')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $selectedCategory === '' ? 'bg-primary-600 text-white' : 'border border-white/10 text-gray-400 hover:border-white/20 hover:text-white' }}">
                        All
                    </button>
                    @foreach($categories as $cat)
                        <button wire:click="selectCategory('{{ $cat->slug }}')"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition {{ $selectedCategory === $cat->slug ? 'bg-primary-600 text-white' : 'border border-white/10 text-gray-400 hover:border-white/20 hover:text-white' }}">
                            {{ $cat->name }}
                            <span class="ml-1 opacity-50">{{ $cat->products_count }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="search"
                               type="text"
                               placeholder="Search..."
                               class="w-48 rounded-lg border border-white/10 bg-white/5 py-2 pl-10 pr-4 text-sm text-white placeholder-gray-500 transition focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    </div>
                    <select wire:model.live="sortBy"
                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-gray-300 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        <option value="price_asc">Price: Low → High</option>
                        <option value="price_desc">Price: High → Low</option>
                        <option value="name_asc">Name: A → Z</option>
                        <option value="name_desc">Name: Z → A</option>
                    </select>
                </div>
            </div>

            <p class="mb-4 text-sm text-gray-500">
                <span class="font-medium text-gray-300">{{ $products->count() }}</span> configurations
            </p>

            {{-- Grid --}}
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                 wire:loading.class="opacity-50"
                 wire:target="search, selectedCategory, sortBy">
                @forelse($products as $product)
                    <a href="{{ route('shh-store.product', $product->slug) }}"
                       wire:navigate
                       wire:key="product-{{ $product->id }}"
                       class="group flex flex-col rounded-lg border border-white/5 bg-white/5 transition hover:border-white/10 hover:bg-white/[0.07]">
                        <div class="flex flex-1 flex-col p-4">
                            <div class="mb-2 flex items-center gap-2">
                                <span class="rounded bg-white/10 px-2 py-0.5 text-[11px] font-medium text-gray-300">
                                    {{ $product->category->name ?? 'Game' }}
                                </span>
                                @if($product->is_featured)
                                    <span class="rounded bg-primary-500/15 px-2 py-0.5 text-[11px] font-medium text-primary-400">Featured</span>
                                @endif
                                @unless($product->in_stock)
                                    <span class="rounded bg-danger-500/15 px-2 py-0.5 text-[11px] font-medium text-danger-400">Out of Stock</span>
                                @endunless
                            </div>

                            <h3 class="text-sm font-medium text-white">{{ $product->name }}</h3>
                            <p class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $product->description }}</p>

                            <div class="mt-3 space-y-1.5">
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

                            <div class="mt-auto pt-3">
                                <div class="flex items-baseline justify-between border-t border-white/5 pt-3">
                                    <span class="text-xs text-gray-500">Monthly</span>
                                    <div>
                                        <span class="text-base font-semibold text-white">${{ number_format($product->calculatePrice('monthly'), 2) }}</span>
                                        <span class="text-xs text-gray-500">{{ $product->cyclePriceSuffix('monthly') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <p class="text-sm text-gray-500">No configurations found.</p>
                        <button wire:click="clearFilters" class="mt-2 text-sm font-medium text-primary-400 hover:text-primary-300">
                            Clear filters
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="border-t border-white/5 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h2 class="mb-6 text-lg font-semibold text-white">{{ StoreSetting::getValue('features_heading', 'Why Shadow Haven') }}</h2>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['title' => StoreSetting::getValue('feature1_title', 'Fast Launch'), 'desc' => StoreSetting::getValue('feature1_desc', 'Provisioning and checkout wired to minimize the gap between payment and playable server.')],
                    ['title' => StoreSetting::getValue('feature2_title', 'Clean Pricing'), 'desc' => StoreSetting::getValue('feature2_desc', 'Straightforward catalog, visible monthly costs, and consistent billing.')],
                    ['title' => StoreSetting::getValue('feature3_title', '200+ Games'), 'desc' => StoreSetting::getValue('feature3_desc', 'A large supported game list with breadth and performance for niche communities.')],
                    ['title' => StoreSetting::getValue('feature4_title', 'Real Hardware'), 'desc' => StoreSetting::getValue('feature4_desc', 'High-cache Ryzen 9 9950X3D CPU and NVMe storage tuned for game workloads.')],
                    ['title' => StoreSetting::getValue('feature5_title', 'DDoS Protected'), 'desc' => StoreSetting::getValue('feature5_desc', 'DDoS mitigation and hardened infrastructure as the baseline, not an upsell.')],
                    ['title' => StoreSetting::getValue('feature6_title', 'Unified Management'), 'desc' => StoreSetting::getValue('feature6_desc', 'Billing, payments, and server management in one clean storefront.')],
                ];
                @endphp

                @foreach($features as $feature)
                    <div class="rounded-lg border border-white/5 bg-white/5 p-4">
                        <p class="text-sm font-medium text-white">{{ $feature['title'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
