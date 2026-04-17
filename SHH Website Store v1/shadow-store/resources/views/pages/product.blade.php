<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} - Shadow Haven Hosting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #050816; }
        .active-card { border-color: #22c55e !important; box-shadow: 0 0 0 1px rgba(34,197,94,0.25) inset; }
    </style>
</head>
<body class="text-white min-h-screen bg-gradient-to-b from-slate-950 via-blue-950/40 to-slate-950">
    <header class="border-b border-slate-800/80 bg-slate-950/90 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ $storeHeader['store_url'] }}" class="flex items-center gap-3">
                @include('shadow-store::pages.partials.store-logo', [
                    'sizeClass' => 'h-9 w-9',
                    'containerClass' => 'rounded-lg bg-emerald-500 p-1',
                    'imageClass' => 'h-full w-full object-contain',
                    'textClass' => 'font-black text-black',
                ])
                <div>
                    <div class="font-semibold">{{ $storeHeader['brand_name'] }}</div>
                    <div class="text-[10px] uppercase tracking-[0.18em] text-slate-500">{{ $storeHeader['brand_tagline'] }}</div>
                </div>
            </a>
            <nav class="flex items-center gap-5 text-sm">
                <a href="{{ $storeHeader['store_url'] }}" class="text-slate-300 hover:text-white">{{ $storeHeader['store_label'] }}</a>
                <a href="{{ $storeHeader['wiki_url'] }}" class="text-slate-300 hover:text-white">{{ $storeHeader['wiki_label'] }}</a>
                @auth
                    <a href="/" class="text-slate-300 hover:text-white">My Servers</a>
                    <a href="/store/cart" class="text-slate-300 hover:text-white">Cart</a>
                @else
                    <a href="/login" class="text-slate-300 hover:text-white">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="border-b border-slate-800/70 bg-slate-900/30">
        <p class="max-w-7xl mx-auto px-6 py-2.5 text-center text-sm text-slate-400">
            Pick your plan, location, and billing cycle. Save up to 17% with longer terms.
        </p>
    </div>

    <main class="max-w-7xl mx-auto px-6 py-8">
        <nav class="mb-6">
            <ol class="flex items-center gap-2 text-sm text-slate-500">
                <li><a href="/store" class="hover:text-white">Store</a></li>
                <li>/</li>
                <li class="text-slate-300">{{ $product->name }}</li>
            </ol>
        </nav>

        @if($product->billing_type === 'slots')
            @php
                $planData = $relatedProducts->map(function($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price_per_slot' => (float) $p->price_per_slot,
                        'disk' => (int) ($p->disk ?? 50000),
                        'memory_per_slot' => (int) ($p->memory_per_slot ?? 128),
                        'min_slots' => (int) ($p->min_slots ?? 1),
                        'default_slots' => (int) ($p->default_slots ?? 32),
                        'max_slots' => (int) ($p->max_slots ?? 128),
                        'slot_increment' => (int) ($p->slot_increment ?? 1),
                    ];
                })->values()->toArray();
            @endphp

            <form action="{{ route('store.cart.add') }}" method="POST" id="configure-form">
                @csrf
                <input type="hidden" name="product_id" id="form-product-id" value="{{ $product->id }}">
                <input type="hidden" name="slots" id="form-slots" value="{{ $product->default_slots ?? 32 }}">
                <input type="hidden" name="billing_cycle" id="form-billing-cycle" value="monthly">
                <input type="hidden" name="variables[MAX_PLAYERS]" id="form-max-players" value="{{ $product->default_slots ?? 32 }}">
                <input type="hidden" name="custom_monthly_price" id="form-custom-monthly-price" value="">
                <input type="hidden" name="tier_label" id="form-tier-label" value="">

                <div class="grid lg:grid-cols-12 gap-6 items-start">
                    <div class="lg:col-span-8 bg-slate-900/70 border border-slate-800 rounded-2xl p-6 md:p-8">
                        <h1 class="text-2xl md:text-3xl font-extrabold">Configure Your Server</h1>
                        <p class="text-slate-400 mt-1 mb-7">Customize resources to fit your needs</p>

                        <div class="mb-7 p-4 rounded-lg border border-blue-500/30 bg-blue-950/40">
                            <p class="text-sm text-blue-200">
                                <span class="font-semibold">Note:</span> Slot recommendations shown below represent what we suggest for comfortable performance on this hardware specification. You can manually configure servers up to <span class="font-semibold">128 players maximum</span> on any plan, but performance may be impacted beyond recommended levels depending on game type and configuration.
                            </p>
                        </div>

                        <section class="mb-7">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Choose Plan Type</h2>
                            <div id="plan-type-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
                        </section>

                        <section class="mb-7">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500">Server Location</h2>
                                <span class="text-xs text-emerald-400">Best route selected automatically</span>
                            </div>
                            <div id="location-grid" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button type="button" data-loc="us" class="location-btn active-card p-4 rounded-xl border-2 border-slate-700 bg-slate-800/40 text-left">
                                    <div class="font-semibold">United States</div>
                                    <div class="text-sm text-slate-400">New York</div>
                                </button>
                                <button type="button" data-loc="eu" class="location-btn p-4 rounded-xl border-2 border-slate-700 bg-slate-800/40 text-left">
                                    <div class="font-semibold">Europe</div>
                                    <div class="text-sm text-slate-400">Frankfurt</div>
                                </button>
                            </div>
                        </section>

                        <section class="mb-7">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Select Plan</h2>
                            <div id="tier-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
                        </section>

                        <section class="mb-7">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Choose Billing Cycle</h2>
                            <div id="cycle-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
                        </section>

                        <section class="rounded-xl border border-slate-800 bg-slate-950/40 p-4">
                            <h2 class="text-xs font-semibold uppercase tracking-widest text-slate-500 mb-3">Selected Plan Specifications</h2>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <div class="text-slate-500">CPU</div>
                                    <div class="font-semibold" id="spec-cpu">Ryzen 9 9950X3D</div>
                                </div>
                                <div>
                                    <div class="text-slate-500">RAM</div>
                                    <div class="font-semibold" id="spec-ram">12 GB DDR5</div>
                                </div>
                                <div>
                                    <div class="text-slate-500">Storage</div>
                                    <div class="font-semibold" id="spec-storage">300 GB NVMe</div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="lg:col-span-4">
                        <div class="sticky top-20 rounded-2xl overflow-hidden border border-slate-800 bg-slate-900/80 shadow-2xl">
                            <div class="bg-emerald-500 px-6 py-4">
                                <h2 class="text-black text-lg font-bold">Order Summary</h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-400">Arma Reforger Hosting</span>
                                    <span class="font-semibold" id="summary-plan-name">Standard</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-400">Server Location</span>
                                    <span id="summary-location">US New York</span>
                                </div>
                                <div class="border-y border-slate-800 py-3 space-y-2 text-sm">
                                    <div class="flex justify-between"><span class="text-slate-400" id="summary-cpu">4x CPU @ 4.8-6GHz</span><span class="text-emerald-400">Included</span></div>
                                    <div class="flex justify-between"><span class="text-slate-400"><span id="summary-ram">12 GB</span> DDR5 RAM</span><span class="text-emerald-400">Included</span></div>
                                    <div class="flex justify-between"><span class="text-slate-400"><span id="summary-storage">1000 GB</span> NVMe Storage</span><span class="text-emerald-400">Included</span></div>
                                    <div class="flex justify-between"><span class="text-slate-400">DDoS Protection</span><span class="text-emerald-400">FREE</span></div>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-400">Subtotal (<span id="summary-period">1 month</span>)</span>
                                    <span class="font-medium">$<span id="summary-subtotal">97.50</span> USD</span>
                                </div>
                                <div class="flex justify-between items-end pt-1">
                                    <span class="font-semibold">Total Due Today</span>
                                    <span class="text-4xl leading-none font-black text-emerald-400">$<span id="summary-total">97.50</span></span>
                                </div>
                                @auth
                                    <label class="flex items-start gap-3 text-xs text-slate-400 leading-relaxed mt-2">
                                        <input type="checkbox" name="accept_msa" value="1" required class="mt-0.5 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500">
                                        <span>
                                            I have read and understand the Shadow Haven Hosting Master Services Agreement.
                                            <a href="{{ route('store.msa') }}" target="_blank" rel="noopener noreferrer" class="text-emerald-400 hover:text-emerald-300 underline">Read Agreement</a>
                                        </span>
                                    </label>
                                    @error('accept_msa')
                                        <p class="text-xs text-red-400">{{ $message }}</p>
                                    @enderror
                                    <button type="submit" class="w-full mt-2 bg-emerald-500 hover:bg-emerald-400 text-black font-bold py-4 rounded-xl transition">Continue to Checkout</button>
                                @else
                                    <a href="/login?redirect={{ urlencode(request()->url()) }}" class="block w-full mt-2 bg-emerald-500 hover:bg-emerald-400 text-black font-bold py-4 rounded-xl transition text-center">Login to Order</a>
                                @endauth
                                <p class="text-center text-xs text-slate-500">Server deployed instantly after payment</p>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        @else
            <div class="grid lg:grid-cols-2 gap-12 max-w-5xl mx-auto">
                <div>
                    <div class="inline-block px-3 py-1 bg-emerald-500/20 text-emerald-400 text-sm rounded-full mb-4">
                        {{ ucfirst(str_replace('-', ' ', $product->game ?? $product->category)) }}
                    </div>
                    <h1 class="text-4xl font-bold mb-4">{{ $product->name }}</h1>
                    <p class="text-slate-400 text-lg mb-8">{{ $product->description }}</p>
                </div>
                <div>
                    <form action="{{ route('store.cart.add') }}" method="POST" class="bg-slate-900 rounded-2xl p-8 border border-slate-800 sticky top-24">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        @if(!empty($product->default_slots))
                            <input type="hidden" name="slots" value="{{ $product->default_slots }}">
                            @if($product->game === 'arma-reforger')
                                <input type="hidden" name="variables[MAX_PLAYERS]" value="{{ $product->default_slots }}">
                            @endif
                        @endif
                        <div class="bg-slate-950/50 rounded-xl p-6 mb-6 text-center">
                            <div class="text-4xl font-black text-emerald-400">${{ number_format($product->base_price, 2) }}</div>
                            <div class="text-slate-400 text-sm mt-1">per month</div>
                        </div>
                        @auth
                            <label class="flex items-start gap-3 text-xs text-slate-400 leading-relaxed mb-4">
                                <input type="checkbox" name="accept_msa" value="1" required class="mt-0.5 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500">
                                <span>
                                    I have read and understand the Shadow Haven Hosting Master Services Agreement.
                                    <a href="{{ route('store.msa') }}" target="_blank" rel="noopener noreferrer" class="text-emerald-400 hover:text-emerald-300 underline">Read Agreement</a>
                                </span>
                            </label>
                            @error('accept_msa')
                                <p class="text-xs text-red-400 mb-3">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-black font-bold py-4 rounded-xl transition">Add to Cart</button>
                        @else
                            <a href="/login?redirect={{ urlencode(request()->url()) }}" class="block w-full bg-emerald-500 hover:bg-emerald-400 text-black font-bold py-4 rounded-xl transition text-center">Login to Order</a>
                        @endauth
                    </form>
                </div>
            </div>
        @endif
    </main>

    <footer class="py-10 px-6 border-t border-slate-800/80 mt-16">
        <div class="max-w-7xl mx-auto text-center text-sm text-slate-500">
            Copyright {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
        </div>
        <div class="mt-8 mx-auto max-w-3xl rounded-xl bg-amber-100/10 border border-amber-300/30 px-6 py-5 text-center text-base text-amber-200 font-semibold">
            <span class="block text-lg font-bold text-amber-300 mb-1">Servers are in partnership with Thunder Buddies Studio</span>
            <span class="block text-amber-100">Support provided by Thunder Buddies Studio. Servers by Shadow Haven.</span>
        </div>
    </footer>

    @if($product->billing_type === 'slots')
    <script>
        const PLANS = @json($planData);
        const USE_PRESET_TIERS = {{ $product->game === 'arma-reforger' ? 'true' : 'false' }};
        const CYCLES = [
            { key: 'monthly', label: 'Monthly', months: 1, discount: 0, badge: '' },
            { key: 'quarterly', label: 'Quarterly', months: 3, discount: 0.05, badge: '5%' },
            { key: 'semi', label: 'Semi-Annual', months: 6, discount: 0.10, badge: '10%' },
            { key: 'annual', label: 'Annually', months: 12, discount: 0.17, badge: '17%' },
        ];
        const TIER_RAM_GB = [8, 16, 24, 32];
        const LOCS = { us: 'US New York', eu: 'EU Frankfurt' };
        const PLAN_META = [
            {
                display: 'Premium Shadow Box',
                cpu: '7950X3D/9950X',
                specCpu: '4x 4.8-6GHz',
                summaryCpu: '4x CPU @ 4.8-6GHz',
                memoryType: 'DDR5',
                fromPrefix: 'From'
            },
            {
                display: 'Shadow Box',
                cpu: '5950X',
                specCpu: '4x 3.4-4.9GHz',
                summaryCpu: '4x CPU @ 3.4-4.9GHz',
                memoryType: 'DDR4',
                fromPrefix: 'From'
            },
        ];
        const PRESET_TIERS = [
            { label: 'Shadow Box 1', slots: 24, players: '20-60 Slots', price: 5, ram: 4, storage: 100, disabled: true, disabledReason: 'Not enough RAM for Arma Reforger' },
            { label: 'Shadow Box 2', slots: 40, players: '20-60 Slots', price: 20, ram: 8, storage: 200 },
            { label: 'Shadow Box 3', slots: 72, players: '60-100 Slots', price: 35, ram: 12, storage: 300 },
            { label: 'Shadow Box 4', slots: 96, players: '100-128 Slots', price: 45, ram: 16, storage: 400 },
            { label: 'Shadow Box 5', slots: 104, players: '100-128 Slots', price: 55, ram: 20, storage: 400 },
            { label: 'Shadow Box 6', slots: 112, players: '100-128 Slots', price: 70, ram: 24, storage: 400 },
            { label: 'Shadow Box 7', slots: 120, players: '100-128 Slots', price: 130, ram: 32, storage: 400 },
            { label: 'Shadow Box 8', slots: 128, players: '100-128 Slots', price: 170, ram: 64, storage: 400 },
        ];

        let selPlan = Math.max(0, PLANS.findIndex(p => p.id === {{ $product->id }}));
        let selTier = 0;
        let selCycle = 0;
        let selLoc = 'us';
        let currentTiers = [];

        function buildTiers(plan) {
            if (USE_PRESET_TIERS) {
                return PRESET_TIERS.map(tier => ({ ...tier }));
            }

            const min = Number(plan.min_slots || 1);
            const max = Number(plan.max_slots || 128);
            const inc = Math.max(1, Number(plan.slot_increment || 1));
            const caps = [31, 64, 96, 128];

            const snap = (v) => {
                const raw = Math.round(v / inc) * inc;
                return Math.min(max, Math.max(min, raw));
            };

            const unique = [];
            caps.map(snap).forEach((v) => {
                if (!unique.includes(v)) unique.push(v);
            });

            while (unique.length < 4) {
                const last = unique[unique.length - 1] ?? min;
                const next = snap(last + inc);
                if (unique.includes(next)) break;
                unique.push(next);
            }

            return unique.slice(0, 4).map((slots, i) => ({
                slots,
                players: `Up to ${slots} Slots`,
                label: `Plan ${i + 1}`,
            }));
        }

        function setDefaultTierForPlan() {
            const plan = PLANS[selPlan];
            const def = Number(plan.default_slots || currentTiers[0]?.slots || 1);
            let best = 0;
            for (let i = 1; i < currentTiers.length; i++) {
                if (Math.abs(currentTiers[i].slots - def) < Math.abs(currentTiers[best].slots - def)) {
                    best = i;
                }
            }

            if (currentTiers[best]?.disabled) {
                best = currentTiers.findIndex(tier => !tier.disabled);
                if (best === -1) best = 0;
            }

            selTier = best;
        }

        function calcRam(memoryPerSlot, slots) {
            const gb = (memoryPerSlot * slots) / 1024;
            const snap = [4, 6, 8, 10, 12, 14, 16, 20, 24, 32, 48, 64];
            for (const n of snap) if (n >= gb) return n;
            return Math.ceil(gb);
        }

        function tierRamGb(tierIndex, memoryPerSlot, slots) {
            if (USE_PRESET_TIERS && typeof currentTiers[tierIndex]?.ram !== 'undefined') {
                return currentTiers[tierIndex].ram;
            }

            if (typeof TIER_RAM_GB[tierIndex] !== 'undefined') {
                return TIER_RAM_GB[tierIndex];
            }

            return calcRam(memoryPerSlot, slots);
        }

        function calcTotal(planIdx, tierIdx, cycleIdx) {
            const p = PLANS[planIdx];
            const t = currentTiers[tierIdx];
            const c = CYCLES[cycleIdx];
            const monthly = USE_PRESET_TIERS ? Number(t.price) : p.price_per_slot * t.slots;
            return monthly * c.months * (1 - c.discount);
        }

        function fmt(v) { return Number(v).toFixed(2); }

        function shortPlan(name) {
            return name.replace('Arma Reforger - ', '').trim();
        }

        function renderPlanTypes() {
            const grid = document.getElementById('plan-type-grid');
            grid.innerHTML = PLANS.map((p, i) => {
                const active = i === selPlan ? 'active-card' : '';
                const meta = PLAN_META[i] || { display: shortPlan(p.name), cpu: 'Ryzen 9 9950X3D', memoryType: 'DDR5', fromPrefix: 'From' };
                const firstTier = buildTiers(p)[0];
                const from = USE_PRESET_TIERS ? Number(firstTier?.price || 0) : p.price_per_slot * firstTier.slots;
                return `<button type="button" onclick="selectPlanType(${i})" class="${active} rounded-xl border-2 border-slate-700 bg-slate-800/40 p-4 text-left">
                    <div class="font-bold text-base">${meta.display}</div>
                    <div class="text-xs text-slate-400 mt-1">${meta.cpu} - ${meta.memoryType}</div>
                    <div class="mt-3 text-sm"><span class="text-slate-400">${meta.fromPrefix}</span> <span class="text-emerald-400 font-bold">$${fmt(from)}</span><span class="text-slate-500">/mo</span></div>
                </button>`;
            }).join('');
        }

        function renderTiers() {
            const p = PLANS[selPlan];
            document.getElementById('tier-grid').innerHTML = currentTiers.map((t, i) => {
                const active = i === selTier ? 'active-card' : '';
                const monthly = USE_PRESET_TIERS ? Number(t.price) : p.price_per_slot * t.slots;
                const ram = tierRamGb(i, p.memory_per_slot, t.slots);
                const disabled = !!t.disabled;
                const stateClass = disabled
                    ? 'border-slate-800 bg-slate-900/60 opacity-50 cursor-not-allowed'
                    : `${active} border-slate-700 bg-slate-800/40`;
                const badge = disabled
                    ? `<div class="mb-2 inline-flex rounded-full bg-red-500/15 px-2 py-0.5 text-[10px] font-semibold text-red-300">${t.disabledReason || 'Unavailable'}</div>`
                    : '';
                return `<button type="button" ${disabled ? 'disabled aria-disabled="true"' : `onclick="selectTier(${i})"`} class="rounded-xl border-2 ${stateClass} p-3 text-left">
                    ${badge}
                    <div class="text-xs text-slate-500">${t.players}</div>
                    <div class="font-bold mt-0.5">${t.label}</div>
                    <div class="text-emerald-400 text-xl leading-tight font-bold mt-2">$${fmt(monthly)}</div>
                    <div class="text-xs text-slate-500">/mo - ${ram}GB RAM</div>
                </button>`;
            }).join('');
        }

        function renderCycles() {
            const p = PLANS[selPlan];
            const t = currentTiers[selTier];
            const monthly = USE_PRESET_TIERS ? Number(t.price) : p.price_per_slot * t.slots;
            document.getElementById('cycle-grid').innerHTML = CYCLES.map((c, i) => {
                const active = i === selCycle ? 'active-card' : '';
                const total = monthly * c.months * (1 - c.discount);
                const badge = c.badge ? `<span class="text-[10px] bg-emerald-500 text-black font-bold px-1.5 py-0.5 rounded">-${c.badge}</span>` : '';
                return `<button type="button" onclick="selectCycle(${i})" class="${active} rounded-xl border-2 border-slate-700 bg-slate-800/40 p-3 text-left">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-sm">${c.label}</span>${badge}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">$${fmt(total)} USD</div>
                </button>`;
            }).join('');
        }

        function updateSummary() {
            const p = PLANS[selPlan];
            const t = currentTiers[selTier];
            const c = CYCLES[selCycle];
            const ram = tierRamGb(selTier, p.memory_per_slot, t.slots);
            const diskGb = USE_PRESET_TIERS ? Number(t.storage || 400) : 1000;
            const total = calcTotal(selPlan, selTier, selCycle);
            const meta = PLAN_META[selPlan] || { display: shortPlan(p.name), cpu: '7950X3D/9950X', specCpu: '4x 4.8-6GHz', summaryCpu: '4x CPU @ 4.8-6GHz', memoryType: 'DDR5' };

            document.getElementById('summary-plan-name').textContent = t.label;
            document.getElementById('summary-location').textContent = LOCS[selLoc];
            document.getElementById('summary-cpu').textContent = meta.summaryCpu || meta.specCpu || meta.cpu;
            document.getElementById('summary-ram').textContent = ram + ' GB';
            document.getElementById('summary-storage').textContent = diskGb + ' GB';
            document.getElementById('summary-period').textContent = c.months === 1 ? '1 month' : (c.months + ' months');
            document.getElementById('summary-subtotal').textContent = fmt(total);
            document.getElementById('summary-total').textContent = fmt(total);

            document.getElementById('spec-cpu').textContent = meta.specCpu || meta.cpu;
            document.getElementById('spec-ram').textContent = ram + ' GB ' + meta.memoryType;
            document.getElementById('spec-storage').textContent = diskGb + ' GB NVMe';

            document.getElementById('form-product-id').value = p.id;
            document.getElementById('form-slots').value = t.slots;
            document.getElementById('form-billing-cycle').value = c.key;
            document.getElementById('form-max-players').value = t.slots;
            document.getElementById('form-custom-monthly-price').value = USE_PRESET_TIERS ? Number(t.price).toFixed(2) : '';
            document.getElementById('form-tier-label').value = t.label;
        }

        function selectPlanType(i) {
            selPlan = i;
            currentTiers = buildTiers(PLANS[selPlan]);
            setDefaultTierForPlan();
            renderPlanTypes();
            renderTiers();
            renderCycles();
            updateSummary();
        }

        function selectTier(i) {
            if (currentTiers[i]?.disabled) {
                return;
            }

            selTier = i;
            renderTiers();
            renderCycles();
            updateSummary();
        }

        function selectCycle(i) {
            selCycle = i;
            renderCycles();
            updateSummary();
        }

        document.getElementById('location-grid').addEventListener('click', function(e) {
            const btn = e.target.closest('.location-btn');
            if (!btn) return;
            selLoc = btn.dataset.loc;
            document.querySelectorAll('.location-btn').forEach(b => b.classList.remove('active-card'));
            btn.classList.add('active-card');
            updateSummary();
        });

        currentTiers = buildTiers(PLANS[selPlan]);
        setDefaultTierForPlan();
        renderPlanTypes();
        renderTiers();
        renderCycles();
        updateSummary();
    </script>
    @endif
</body>
</html>
