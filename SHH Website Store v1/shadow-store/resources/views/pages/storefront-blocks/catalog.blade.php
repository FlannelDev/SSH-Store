@php($settings = $block['settings'])

<section id="catalog" class="store-section fade-up space-y-10 store-edit-target" data-homepage-block="{{ $block['id'] }}">
    @if($isStoreAdmin)
        <div class="store-edit-toolbar">
            <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
            <a href="{{ $storeProductsUrl }}" class="store-edit-link">Manage Catalog</a>
            <a href="{{ $storeMediaUrl }}" class="store-edit-link">Media</a>
        </div>
    @endif
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <div class="section-kicker">{{ $settings['kicker'] ?? '' }}</div>
            <h2 class="genesis-section-title mt-2 font-bold text-white">{{ $settings['title'] ?? '' }}</h2>
        </div>
        <p class="max-w-2xl text-sm leading-7 text-[#7f7f8b]">{{ $settings['body'] ?? '' }}</p>
    </div>

    @foreach($categoryOrder as $catKey => $catLabel)
        @if(isset($categorizedProducts[$catKey]) && $categorizedProducts[$catKey]->count() > 0)
            <section class="section-card rounded-[2rem] p-6 sm:p-8">
                <div class="mb-7 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="section-kicker">{{ $catLabel }}</div>
                        <h3 class="mt-2 text-2xl font-bold capitalize text-white">{{ $catLabel }}</h3>
                    </div>
                    <div class="text-sm text-[#7f7f8b]">{{ $categorizedProducts[$catKey]->count() }} configuration{{ $categorizedProducts[$catKey]->count() === 1 ? '' : 's' }} available</div>
                </div>
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($categorizedProducts[$catKey] as $product)
                        @php($pricing = $resolveMonthlyDisplay($product))
                        @php($specs = $resolveSpecSummary($product))
                        <article class="product-card h-full rounded-[1.5rem] group">
                            @if($isStoreAdmin)
                                <div class="store-edit-toolbar">
                                    <a href="{{ route('filament.admin.resources.products.edit', ['record' => $product]) }}" class="store-edit-link">Edit Product</a>
                                </div>
                            @endif
                            <div class="store-product-thumb{{ $product->resolved_image_url ? '' : ' is-empty' }}">
                                @if($product->resolved_image_url)
                                    <img src="{{ $product->resolved_image_url }}" alt="{{ $product->name }}">
                                @else
                                    <div class="store-product-thumb-placeholder">
                                        <div class="store-product-thumb-kicker">{{ ucfirst(str_replace('-', ' ', $product->game ?? $product->category)) }}</div>
                                        <div class="store-product-thumb-title">{{ $product->name }}</div>
                                    </div>
                                @endif
                            </div>
                            <div class="product-card-body">
                                <div class="product-card-head">
                                    <span class="inline-block rounded-full border border-[#1a1a20] bg-[#101014] px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#9a9aa5]">
                                        {{ ucfirst(str_replace('-', ' ', $product->game ?? $product->category)) }}
                                    </span>
                                    <h3 class="product-card-title mt-4 text-2xl font-bold leading-tight text-white transition group-hover:text-[#00e6b0]">{{ $product->name }}</h3>
                                </div>
                                <p class="product-card-description text-sm leading-7 text-[#7f7f8b]">{{ $product->description }}</p>
                                @if(!empty($specs))
                                    <div class="product-card-specs mt-5 grid grid-cols-2 gap-3 text-xs sm:grid-cols-3">
                                        @foreach($specs as $spec)
                                            <div class="rounded-2xl border border-[#1a1a20] bg-[#101014] px-3 py-2">
                                                <div class="text-[0.65rem] uppercase tracking-[0.2em] text-[#6b6b76]">{{ $spec['label'] }}</div>
                                                <div class="mt-1 font-semibold text-white">{{ $spec['value'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="product-card-specs mt-5"></div>
                                @endif
                                <div class="product-card-footer flex items-end justify-between gap-4 border-t border-[#1a1a20] pt-5">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.18em] text-[#6b6b76]">{{ $pricing['label'] }}</div>
                                        <div class="mt-1 flex items-end gap-1.5">
                                            @if($pricing['prefix'] !== '')
                                                <span class="pb-1 text-sm font-medium text-[#7f7f8b]">{{ $pricing['prefix'] }}</span>
                                            @endif
                                            <span class="inline-block font-display text-3xl font-bold text-[#00e6b0]">${{ number_format($pricing['amount'], 2) }}</span>
                                            <span class="pb-1 text-sm text-[#7f7f8b]">{{ $pricing['suffix'] }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('store.product', $product) }}" class="product-card-cta">Order Now</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</section>