<div>
    {{-- Hero Section --}}
    <section class="relative overflow-hidden border-b border-gray-800/60 bg-gradient-to-br from-gray-950 via-gray-900 to-indigo-950">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-600/30 via-transparent to-transparent"></div>
        </div>
        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
            <div class="max-w-3xl">
                <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-indigo-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
                    </span>
                    Game Infrastructure
                </p>
                <h1 class="text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Game servers with <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">dedicated-grade</span> headroom.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-gray-400">
                    Launch across a catalog of 200+ supported games on Ryzen 9 9950X3D infrastructure with sensible density, fast storage, and clean billing.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="#catalog" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-500">
                        Browse Game Servers
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                    <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-700 bg-gray-800/50 px-6 py-3 text-sm font-semibold text-gray-300 transition hover:border-gray-600 hover:text-white">
                        Join Discord
                    </a>
                </div>
            </div>

            {{-- Stats strip --}}
            <div class="mt-16 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-4 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-white">$20</p>
                    <p class="mt-1 text-xs text-gray-500">Starting monthly</p>
                </div>
                <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-4 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-white">200+</p>
                    <p class="mt-1 text-xs text-gray-500">Games supported</p>
                </div>
                <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-4 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-white">X3D</p>
                    <p class="mt-1 text-xs text-gray-500">Ryzen 9 9950X3D</p>
                </div>
                <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-4 backdrop-blur-sm">
                    <p class="text-2xl font-bold text-white">NVMe</p>
                    <p class="mt-1 text-xs text-gray-500">Fast storage</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured Products --}}
    @if($featuredProducts->isNotEmpty())
    <section class="border-b border-gray-800/60 bg-gray-950 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 flex items-end justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Featured Drops</p>
                    <h2 class="mt-2 text-2xl font-bold text-white">Priority configurations</h2>
                    <p class="mt-1 text-sm text-gray-500">Hand-picked builds surfaced for the quickest path to a proven setup.</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($featuredProducts as $product)
                    <a href="{{ route('store.product', $product->slug) }}"
                       wire:navigate
                       class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50 transition-all duration-200 hover:border-indigo-500/40 hover:bg-gray-900/80 hover:shadow-lg hover:shadow-indigo-600/5">
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="inline-flex rounded-md bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                                    {{ $product->category->name ?? 'Game' }}
                                </span>
                                <span class="rounded-md bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-400">Featured</span>
                            </div>
                            <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300">{{ $product->name }}</h3>
                            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-gray-500">{{ $product->description }}</p>

                            {{-- Specs --}}
                            <div class="mt-4 space-y-2">
                                @if($product->cpu)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">CPU</span>
                                    <span class="text-gray-300">{{ $product->cpu }}</span>
                                </div>
                                @endif
                                @if($product->ram)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">RAM</span>
                                    <span class="text-gray-300">{{ $product->ram }}</span>
                                </div>
                                @endif
                                @if($product->storage)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">Storage</span>
                                    <span class="text-gray-300">{{ $product->storage }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Price --}}
                            <div class="mt-auto pt-4">
                                <div class="flex items-baseline gap-1.5 border-t border-gray-800/60 pt-4">
                                    <span class="text-lg font-bold text-white">${{ number_format((float) $product->price_monthly, 2) }}</span>
                                    <span class="text-xs text-gray-600">/mo</span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-800/60 bg-gray-900/30 px-5 py-3">
                            <span class="flex items-center justify-center gap-2 text-xs font-semibold text-indigo-400 group-hover:text-indigo-300">
                                Open product
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Catalog Section --}}
    <section id="catalog" class="bg-gray-950 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Catalog</p>
                <h2 class="mt-2 text-2xl font-bold text-white">Browse by game family</h2>
            </div>

            {{-- Filters --}}
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                 x-data="{ showFilters: false }">
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Category pills --}}
                    <button wire:click="selectCategory('')"
                            class="rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $selectedCategory === '' ? 'bg-indigo-600 text-white' : 'border border-gray-800 bg-gray-900/50 text-gray-400 hover:border-gray-700 hover:text-white' }}">
                        All
                    </button>
                    @foreach($categories as $cat)
                        <button wire:click="selectCategory('{{ $cat->slug }}')"
                                class="rounded-lg px-3.5 py-2 text-xs font-semibold transition {{ $selectedCategory === $cat->slug ? 'bg-indigo-600 text-white' : 'border border-gray-800 bg-gray-900/50 text-gray-400 hover:border-gray-700 hover:text-white' }}">
                            {{ $cat->name }}
                            <span class="ml-1 text-[10px] opacity-60">{{ $cat->products_count }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    {{-- Search --}}
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="search"
                               type="text"
                               placeholder="Search configurations..."
                               class="w-56 rounded-lg border border-gray-800 bg-gray-900/50 py-2 pl-10 pr-4 text-sm text-white placeholder-gray-600 transition focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>

                    {{-- Sort --}}
                    <select wire:model.live="sortBy"
                            class="rounded-lg border border-gray-800 bg-gray-900/50 px-3 py-2 text-sm text-gray-300 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="price_asc">Price: Low → High</option>
                        <option value="price_desc">Price: High → Low</option>
                        <option value="name_asc">Name: A → Z</option>
                        <option value="name_desc">Name: Z → A</option>
                    </select>
                </div>
            </div>

            {{-- Product Count --}}
            <div class="mb-6">
                <p class="text-sm text-gray-500">
                    <span class="font-semibold text-gray-300">{{ $products->count() }}</span> configurations available
                </p>
            </div>

            {{-- Products Grid --}}
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                 wire:loading.class="opacity-50"
                 wire:target="search, selectedCategory, sortBy">
                @forelse($products as $product)
                    <a href="{{ route('store.product', $product->slug) }}"
                       wire:navigate
                       wire:key="product-{{ $product->id }}"
                       class="group flex flex-col overflow-hidden rounded-xl border border-gray-800/60 bg-gray-900/50 transition-all duration-200 hover:border-indigo-500/40 hover:bg-gray-900/80 hover:shadow-lg hover:shadow-indigo-600/5"
                       x-data="{ hover: false }"
                       @mouseenter="hover = true"
                       @mouseleave="hover = false">
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-3 flex items-center gap-2">
                                <span class="inline-flex rounded-md bg-indigo-500/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-indigo-400">
                                    {{ $product->category->name ?? 'Game' }}
                                </span>
                                @if($product->is_featured)
                                    <span class="rounded-md bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-400">Featured</span>
                                @endif
                                @unless($product->in_stock)
                                    <span class="rounded-md bg-red-500/10 px-2 py-0.5 text-[10px] font-bold uppercase text-red-400">Out of Stock</span>
                                @endunless
                            </div>

                            <h3 class="text-sm font-semibold text-white group-hover:text-indigo-300">{{ $product->name }}</h3>
                            <p class="mt-1.5 line-clamp-2 text-xs leading-relaxed text-gray-500">{{ $product->description }}</p>

                            {{-- Specs --}}
                            <div class="mt-4 space-y-2">
                                @if($product->cpu)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">CPU</span>
                                    <span class="text-gray-300">{{ $product->cpu }}</span>
                                </div>
                                @endif
                                @if($product->ram)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">RAM</span>
                                    <span class="text-gray-300">{{ $product->ram }}</span>
                                </div>
                                @endif
                                @if($product->storage)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium uppercase tracking-wider text-gray-600">Storage</span>
                                    <span class="text-gray-300">{{ $product->storage }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Price --}}
                            <div class="mt-auto pt-4">
                                <div class="flex items-baseline gap-1.5 border-t border-gray-800/60 pt-4">
                                    <span class="text-xs font-medium uppercase tracking-wider text-gray-600">Monthly</span>
                                    <span class="ml-auto text-lg font-bold text-white">${{ number_format((float) $product->price_monthly, 2) }}</span>
                                    <span class="text-xs text-gray-600">/mo</span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-800/60 bg-gray-900/30 px-5 py-3">
                            <span class="flex items-center justify-center gap-2 text-xs font-semibold text-indigo-400 group-hover:text-indigo-300"
                                  :class="{ 'translate-x-0.5': hover }">
                                View details
                                <svg class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full py-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <p class="mt-4 text-sm font-medium text-gray-500">No configurations found matching your filters.</p>
                        <button wire:click="clearFilters" class="mt-3 text-sm font-semibold text-indigo-400 hover:text-indigo-300">
                            Clear all filters
                        </button>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Why Switch Section --}}
    <section class="border-t border-gray-800/60 bg-gray-900/30 py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Why Teams Switch</p>
                <h2 class="mt-2 text-2xl font-bold text-white">Infrastructure that reads like it was built by people who actually host game servers.</h2>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @php
                $features = [
                    ['icon' => '⚡', 'title' => 'Fast Launch', 'stat' => 'Minutes', 'desc' => 'Provisioning and checkout are wired to minimize the gap between payment and playable server.'],
                    ['icon' => '💰', 'title' => 'Clean Pricing', 'stat' => 'No noise', 'desc' => 'Straightforward catalog, visible monthly costs, and consistent billing access.'],
                    ['icon' => '🎮', 'title' => 'Game Catalog', 'stat' => '200+', 'desc' => 'A large supported game list means breadth as well as performance for niche communities.'],
                    ['icon' => '🖥️', 'title' => 'Real Hardware', 'stat' => 'X3D', 'desc' => 'High-cache CPU allocation and NVMe storage tuned for titles that punish weak single-core performance.'],
                    ['icon' => '🛡️', 'title' => 'Protected by Default', 'stat' => 'DDoS', 'desc' => 'DDoS mitigation and hardened infrastructure are treated as the baseline, not an upsell.'],
                    ['icon' => '🔧', 'title' => 'Operational View', 'stat' => 'Unified', 'desc' => 'Cart, billing, payments, and post-purchase server management sit in one storefront path.'],
                ];
                @endphp

                @foreach($features as $feature)
                    <div class="rounded-xl border border-gray-800/60 bg-gray-900/50 p-6 transition hover:border-gray-700">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="text-2xl">{{ $feature['icon'] }}</span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ $feature['title'] }}</p>
                                <p class="text-xs font-bold text-indigo-400">{{ $feature['stat'] }}</p>
                            </div>
                        </div>
                        <p class="text-xs leading-relaxed text-gray-500">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
