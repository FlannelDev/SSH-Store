<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Store - Shadow Haven Hosting</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @if(auth()->check() && auth()->user()?->isAdmin())
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    @endif
    @php
        $isStoreAdmin = auth()->check() && auth()->user()?->isAdmin();
        $storeProductsUrl = route('filament.admin.resources.products.index');
        $storeMediaUrl = route('filament.admin.resources.media-assets.index');
        $editorSectionBaseUrl = url('/store/admin/editor');
        $editorBlockBaseUrl = url('/store/admin/editor/block');
        $editorReorderUrl = route('store.admin.editor.reorder');
        $editorUploadUrl = route('store.admin.media.upload');
        $armaPresetStartingPrice = 20.00;
        $resolveMonthlyDisplay = function ($product) use ($armaPresetStartingPrice) {
            if ($product->billing_type === 'slots') {
                if (($product->game ?? null) === 'arma-reforger') {
                    return [
                        'amount' => $armaPresetStartingPrice,
                        'prefix' => 'From',
                        'suffix' => '/mo',
                        'label' => 'Starting monthly',
                    ];
                }

                $minSlots = (int) ($product->min_slots ?? 1);
                $minSlots = max(1, $minSlots);

                return [
                    'amount' => (float) $product->price_per_slot * $minSlots,
                    'prefix' => 'From',
                    'suffix' => '/mo',
                    'label' => 'Starting monthly',
                ];
            }

            return [
                'amount' => (float) $product->base_price,
                'prefix' => '',
                'suffix' => '/mo',
                'label' => 'Monthly pricing',
            ];
        };

        // Filter out inactive/legacy Arma Reforger products (only show real box products)
        $filteredProducts = collect($products)->map(function ($categoryProducts, $category) {
            // Only filter for Arma Reforger
            if (str_contains($category, 'arma-reforger')) {
                return collect($categoryProducts)->filter(function ($product) {
                    // Only show active box products (not legacy templates)
                    return $product->active && (str_contains($product->slug, 'box-') || str_contains($product->name, 'Box'));
                });
            }
            return $categoryProducts;
        });
        $allProducts = $filteredProducts->flatten(1);
        $startingPrice = $allProducts
            ->map(fn ($product) => $resolveMonthlyDisplay($product)['amount'])
            ->filter(fn ($price) => $price > 0)
            ->min() ?? 0;

        $gameCategoryCount = $filteredProducts->keys()->count();

        $categoryOrder = [
            'arma-reforger' => 'Arma Reforger',
            'survival' => 'Survival Games',
            'fps' => 'FPS & Tactical',
            'sandbox' => 'Sandbox & Creative',
            'strategy' => 'Strategy',
            'other' => 'Other Games',
        ];

        $categorizedProducts = collect($filteredProducts)->mapWithKeys(function ($products, $category) use ($categoryOrder) {
            foreach ($categoryOrder as $key => $label) {
                if (str_contains($category, $key)) {
                    return [$key => $products];
                }
            }
            return ['other' => $products];
        })->filter(fn ($products) => $products->count() > 0);

        $formatCapacity = function (?int $valueMb): ?string {
            if (!$valueMb || $valueMb <= 0) {
                return null;
            }

            $gb = $valueMb / 1024;

            $trimFormattedNumber = function (float $value, int $precision): string {
                $formatted = number_format($value, $precision);

                return $precision > 0
                    ? rtrim(rtrim($formatted, '0'), '.')
                    : $formatted;
            };

            if ($gb >= 1024) {
                $tb = $gb / 1024;
                $precision = $tb < 10 ? 1 : 0;
                return $trimFormattedNumber($tb, $precision) . ' TB';
            }

            $precision = $gb < 10 ? 1 : 0;
            return $trimFormattedNumber($gb, $precision) . ' GB';
        };

        $formatCpu = function (?int $value): ?string {
            if (!$value || $value <= 0) {
                return null;
            }

            $cores = $value / 100;

            if ($cores >= 0.5) {
                $precision = ($cores < 10 && floor($cores) !== $cores) ? 1 : 0;
                $display = rtrim(rtrim(number_format($cores, $precision), '0'), '.');
                return $display . ' vCPU';
            }

            return $value . '% CPU';
        };

        $resolveSpecSummary = function ($product) use ($formatCapacity, $formatCpu) {
            $slotBasis = max(1, (int) ($product->default_slots ?? $product->min_slots ?? 1));

            $memoryMb = $product->memory ?? ($product->memory_per_slot ? $product->memory_per_slot * $slotBasis : null);
            $diskMb = $product->disk ?? ($product->disk_per_slot ? $product->disk_per_slot * $slotBasis : null);
            $cpuLimit = $product->cpu ?? ($product->cpu_per_slot ? $product->cpu_per_slot * $slotBasis : null);

            $isArmaReforger = ($product->game ?? null) === 'arma-reforger';

            if ($isArmaReforger) {
                $cpuCount = $cpuLimit ? max(1, (int) round($cpuLimit / 100)) : null;
                $memoryDisplay = $formatCapacity($memoryMb);
                $diskDisplay = $formatCapacity($diskMb);

                $armaSpecs = [
                    ['label' => 'CPU', 'value' => $cpuCount ? $cpuCount . 'x CPU @ 4.8-6GHz' : null],
                    ['label' => 'RAM', 'value' => $memoryDisplay ? $memoryDisplay . ' DDR5' : null],
                    ['label' => 'Storage', 'value' => $diskDisplay ? $diskDisplay . ' NVMe' : null],
                ];

                return array_values(array_filter($armaSpecs, fn ($spec) => !empty($spec['value'])));
            }

            $specs = array_filter([
                ['label' => 'RAM', 'value' => $formatCapacity($memoryMb)],
                ['label' => 'CPU', 'value' => $formatCpu($cpuLimit)],
                ['label' => 'Storage', 'value' => $formatCapacity($diskMb)],
                !empty($product->allocations) ? ['label' => 'Ports', 'value' => $product->allocations . 'x'] : null,
            ], fn ($spec) => is_array($spec) && !empty($spec['value']));

            return array_slice(array_values($specs), 0, 3);
        };

        $navShellClasses = match ($storeHeader['nav_style'] ?? 'soft') {
            'outline' => 'hidden items-center gap-1 rounded-full border border-[#1a1a20]/80 bg-[#0c0c0f]/70 px-2 py-1.5 text-sm text-[#9a9aa5] shadow-[0_8px_40px_rgba(0,0,0,0.45)] backdrop-blur-2xl lg:flex',
            'solid' => 'hidden items-center gap-1 rounded-full border border-[#1a1a20]/90 bg-[#0c0c0f]/90 px-2 py-1.5 text-sm text-[#9a9aa5] shadow-[0_8px_40px_rgba(0,0,0,0.52)] backdrop-blur-2xl lg:flex',
            default => 'hidden items-center gap-1 rounded-full border border-[#1a1a20]/80 bg-[#0c0c0f]/80 px-2 py-1.5 text-sm text-[#9a9aa5] shadow-[0_8px_40px_rgba(0,0,0,0.48)] backdrop-blur-2xl lg:flex',
        };

        $navActiveClasses = match ($storeHeader['nav_style'] ?? 'soft') {
            'outline' => 'rounded-full px-4 py-2 text-[13px] font-semibold text-[#e8e8ed] transition-all',
            'solid' => 'rounded-full px-4 py-2 text-[13px] font-semibold text-[#e8e8ed] transition-all',
            default => 'rounded-full px-4 py-2 text-[13px] font-semibold text-[#e8e8ed] transition-all',
        };

        $navLinkClasses = match ($storeHeader['nav_style'] ?? 'soft') {
            'outline' => 'rounded-full px-4 py-2 text-[13px] font-medium transition hover:text-[#e8e8ed]',
            'solid' => 'rounded-full px-4 py-2 text-[13px] font-medium transition hover:text-[#e8e8ed]',
            default => 'rounded-full px-4 py-2 text-[13px] font-medium transition hover:text-[#e8e8ed]',
        };

        $announcementClasses = match ($storeAnnouncement['style'] ?? 'accent') {
            'warm' => 'border-b border-amber-300/16 bg-amber-300/12 text-amber-100',
            'danger' => 'border-b border-rose-300/18 bg-rose-400/12 text-rose-100',
            default => 'border-b border-cyan-300/18 bg-cyan-300/10 text-cyan-100',
        };

        $promoClasses = match ($storePromo['style'] ?? 'cyan') {
            'warm' => 'border-b border-amber-300/16 bg-[rgba(32,18,7,0.88)] text-amber-100',
            'emerald' => 'border-b border-emerald-300/16 bg-[rgba(7,27,22,0.88)] text-emerald-100',
            default => 'border-b border-cyan-300/16 bg-[rgba(8,20,34,0.88)] text-cyan-100',
        };

        $backgroundLayers = [];
        if (!empty($storeBackground['resolved_image_url'])) {
            $backgroundLayers[] = "linear-gradient(rgba(4,10,18,{$storeBackground['overlay_opacity']}), rgba(4,10,18,{$storeBackground['overlay_opacity']})), url('{$storeBackground['resolved_image_url']}') center/cover no-repeat";
        }
        $backgroundLayers[] = 'radial-gradient(circle at top, rgba(58, 182, 255, 0.12), transparent 34%)';
        $backgroundLayers[] = 'radial-gradient(circle at 85% 12%, rgba(244, 193, 93, 0.10), transparent 26%)';
        $backgroundLayers[] = "linear-gradient(180deg, {$storeBackground['color_start']} 0%, {$storeBackground['color_end']} 100%)";
        $storeBackgroundStyle = 'background: ' . implode(', ', $backgroundLayers) . ';';

        $terminalHighlights = [
            ['label' => 'Plans', 'value' => $featuredProducts->count() . ' featured'],
            ['label' => 'Deploy', 'value' => '< 60 sec'],
            ['label' => 'Coverage', 'value' => $gameCategoryCount . ' families'],
            ['label' => 'Billing', 'value' => 'Monthly live'],
        ];

        $testimonialCards = [
            ['quote' => 'Checkout and deploy now feel like one product instead of three glued together.', 'name' => 'Ops Lead', 'role' => 'Community Cluster'],
            ['quote' => 'The storefront finally sells performance clearly, without generic host filler.', 'name' => 'Server Admin', 'role' => 'Arma Reforger Group'],
            ['quote' => 'Users can understand the tiers fast and get from browse to live order without friction.', 'name' => 'Project Owner', 'role' => 'Modded Survival Network'],
        ];
    @endphp
    <style>
        :root {
            --bg: #080809;
            --bg-soft: rgba(12, 12, 15, 0.86);
            --surface: rgba(12, 12, 15, 0.9);
            --surface-strong: rgba(17, 17, 21, 0.96);
            --surface-muted: rgba(19, 19, 22, 0.82);
            --border: rgba(30, 30, 36, 0.92);
            --border-strong: rgba(0, 230, 176, 0.16);
            --text: #e8e8ed;
            --muted: #6b6b76;
            --accent: #00e6b0;
            --accent-strong: #00b4d8;
            --accent-warm: #00b4d8;
            --success: #00e6b0;
            --pointer-x: 50%;
            --pointer-y: 50%;
            --hero-shift-x: 0px;
            --hero-shift-y: 0px;
            --hero-tilt-x: 0deg;
            --hero-tilt-y: 0deg;
            --scene-shift-x: 0px;
            --scene-shift-y: 0px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            color: var(--text);
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        .font-display {
            font-family: 'Orbitron', sans-serif;
        }

        .page-shell {
            position: relative;
        }

        .store-global-backdrop {
            position: fixed;
            inset: 0;
            z-index: -3;
            overflow: hidden;
            pointer-events: none;
            contain: layout paint style;
            background:
                radial-gradient(circle at var(--pointer-x) var(--pointer-y), rgba(0, 230, 176, 0.05), transparent 18%),
                linear-gradient(180deg, rgba(3, 5, 7, 0.94) 0%, rgba(5, 7, 9, 0.98) 52%, rgba(2, 3, 4, 1) 100%);
        }

        .store-global-backdrop::before,
        .store-global-backdrop::after {
            content: '';
            position: absolute;
            inset: 0;
        }

        .store-global-backdrop::before {
            background:
                radial-gradient(circle at calc(var(--pointer-x) + 8%) calc(var(--pointer-y) - 14%), rgba(0, 230, 176, 0.08), transparent 18%),
                radial-gradient(circle at calc(var(--pointer-x) - 18%) calc(var(--pointer-y) + 6%), rgba(0, 180, 216, 0.06), transparent 24%),
                radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.02), transparent 38%);
            transform: translate3d(calc(var(--scene-shift-x) * -0.12), calc(var(--scene-shift-y) * -0.12), 0);
        }

        .store-global-backdrop::after {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.026) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.026) 1px, transparent 1px);
            background-size: 68px 68px;
            opacity: 0.26;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.12) 60%, transparent 100%);
        }

        .store-global-glow,
        .store-global-orb,
        .store-global-wireframe,
        .store-global-noise {
            position: absolute;
            will-change: transform;
            transition: transform 180ms ease-out;
        }

        .store-global-glow {
            inset: 0;
            background:
                radial-gradient(circle at 18% 68%, rgba(0, 230, 176, 0.10), transparent 20%),
                radial-gradient(circle at 80% 32%, rgba(0, 180, 216, 0.06), transparent 16%);
            opacity: 0.42;
            transform: translate3d(calc(var(--scene-shift-x) * -0.1), calc(var(--scene-shift-y) * -0.1), 0);
        }

        .store-global-noise {
            inset: 0;
            opacity: 0.18;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.52) 0 1px, transparent 1px),
                radial-gradient(circle at 80% 15%, rgba(255, 255, 255, 0.4) 0 1px, transparent 1px),
                radial-gradient(circle at 72% 82%, rgba(255, 255, 255, 0.35) 0 1px, transparent 1px),
                radial-gradient(circle at 28% 78%, rgba(255, 255, 255, 0.32) 0 1px, transparent 1px);
            background-size: 16rem 16rem, 20rem 20rem, 24rem 24rem, 18rem 18rem;
            transform: translate3d(calc(var(--scene-shift-x) * -0.04), calc(var(--scene-shift-y) * -0.04), 0);
        }

        .store-global-orb {
            top: 3.5rem;
            right: 11%;
            height: 15rem;
            width: 15rem;
            border-radius: 999px;
            border: 1px solid rgba(40, 62, 66, 0.6);
            background:
                radial-gradient(circle at 35% 30%, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.02) 28%, transparent 58%),
                radial-gradient(circle at 50% 50%, rgba(8, 24, 22, 0.64), rgba(7, 8, 10, 0.16) 72%, transparent 100%);
            opacity: 0.36;
            transform: translate3d(calc(var(--scene-shift-x) * -0.14), calc(var(--scene-shift-y) * -0.12), 0);
        }

        .store-global-orb::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background-image:
                linear-gradient(rgba(0, 230, 176, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 230, 176, 0.12) 1px, transparent 1px);
            background-size: 1.2rem 1.2rem;
            mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 1), transparent 78%);
        }

        .store-global-wireframe {
            border-radius: 50%;
            background-image:
                linear-gradient(rgba(0, 230, 176, 0.32) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 230, 176, 0.32) 1px, transparent 1px);
            background-size: 2rem 2rem;
            box-shadow:
                inset 0 0 0 1px rgba(0, 230, 176, 0.04),
                0 0 42px rgba(0, 230, 176, 0.05);
            opacity: 0.7;
            mix-blend-mode: screen;
            transform-origin: center;
        }

        .store-global-wireframe::before,
        .store-global-wireframe::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
        }

        .store-global-wireframe::before {
            background: radial-gradient(circle at 50% 26%, rgba(0, 230, 176, 0.08), transparent 34%);
        }

        .store-global-wireframe::after {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.04) 0%, rgba(0, 0, 0, 0.36) 55%, rgba(0, 0, 0, 0.9) 100%);
        }

        .store-global-wireframe--left {
            left: -34rem;
            bottom: -12rem;
            width: 110rem;
            height: 46rem;
            border-radius: 46% 54% 52% 48% / 42% 58% 42% 58%;
            clip-path: polygon(0% 84%, 6% 76%, 11% 67%, 18% 60%, 26% 54%, 35% 50%, 43% 48%, 52% 43%, 60% 40%, 68% 42%, 76% 48%, 84% 54%, 91% 61%, 96% 69%, 100% 80%, 100% 100%, 0% 100%);
            transform: translate3d(calc(var(--scene-shift-x) * -0.12), calc(var(--scene-shift-y) * 0.1), 0) rotateX(76deg) rotateZ(-11deg) skewX(-4deg);
        }

        .store-global-wireframe--center {
            left: 18%;
            bottom: -15rem;
            width: 82rem;
            height: 34rem;
            opacity: 0.56;
            border-radius: 51% 49% 58% 42% / 38% 62% 38% 62%;
            clip-path: polygon(0% 88%, 8% 80%, 17% 71%, 26% 63%, 37% 56%, 48% 52%, 58% 50%, 67% 46%, 76% 44%, 84% 47%, 91% 53%, 96% 61%, 100% 74%, 100% 100%, 0% 100%);
            transform: translate3d(calc(var(--scene-shift-x) * -0.07), calc(var(--scene-shift-y) * 0.1), 0) rotateX(78deg) rotateZ(-2deg) skewX(2deg);
        }

        .store-global-wireframe--right {
            right: -30rem;
            top: 4.5rem;
            width: 104rem;
            height: 40rem;
            opacity: 0.58;
            border-radius: 58% 42% 48% 52% / 48% 52% 40% 60%;
            clip-path: polygon(0% 82%, 7% 73%, 15% 64%, 24% 56%, 33% 50%, 42% 47%, 50% 45%, 60% 42%, 69% 40%, 78% 44%, 86% 50%, 92% 58%, 97% 68%, 100% 79%, 100% 100%, 0% 100%);
            transform: translate3d(calc(var(--scene-shift-x) * -0.16), calc(var(--scene-shift-y) * 0.08), 0) rotateX(76deg) rotateZ(10deg) skewX(5deg);
        }

        .page-shell::before,
        .page-shell::after {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: -1;
        }

        .page-shell::before {
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
            background-size: 60px 60px;
            opacity: 0.08;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.36), transparent 95%);
        }

        .page-shell::after {
            background:
                radial-gradient(circle at 20% 16%, rgba(0, 230, 176, 0.09), transparent 24%),
                radial-gradient(circle at 82% 24%, rgba(0, 180, 216, 0.08), transparent 18%);
            opacity: 0.22;
        }

        .glass-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(18px);
        }

        .hero-card {
            background:
                linear-gradient(145deg, rgba(12, 12, 15, 0.95), rgba(8, 8, 9, 0.96)),
                radial-gradient(circle at top right, rgba(0, 230, 176, 0.08), transparent 38%);
        }

        .section-card {
            background: linear-gradient(180deg, rgba(12, 12, 15, 0.95), rgba(10, 10, 13, 0.98));
            border: 1px solid rgba(30, 30, 36, 0.8);
        }

        .genesis-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(26, 26, 32, 0.75);
            background: rgba(14, 14, 18, 0.72);
            padding: 0.55rem 0.95rem;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            color: #9a9aa5;
            backdrop-filter: blur(12px);
        }

        .genesis-dot {
            position: relative;
            display: inline-flex;
            height: 0.5rem;
            width: 0.5rem;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 0 rgba(0, 230, 176, 0.35);
        }

        .genesis-hero {
            position: relative;
            overflow: hidden;
            min-height: 44rem;
            padding-top: 3.5rem;
            padding-bottom: 5rem;
            text-align: center;
            perspective: 1400px;
        }

        .genesis-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at var(--pointer-x) var(--pointer-y), rgba(0, 230, 176, 0.08), transparent 18%),
                radial-gradient(ellipse 80% 50% at 50% -10%, rgba(0, 230, 176, 0.05), transparent 60%);
            pointer-events: none;
        }

        .genesis-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 75%, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.48) 72%, rgba(0, 0, 0, 0.86) 100%);
            pointer-events: none;
        }

        .genesis-motion-field {
            position: absolute;
            inset: -8% -8% 0;
            overflow: hidden;
            pointer-events: none;
            transform-style: preserve-3d;
            opacity: 0.55;
        }

        .genesis-motion-glow,
        .genesis-motion-orb,
        .genesis-wireframe {
            position: absolute;
            will-change: transform;
            transition: transform 180ms ease-out;
        }

        .genesis-motion-glow {
            inset: 0;
            background:
                radial-gradient(circle at calc(var(--pointer-x) + 4%) calc(var(--pointer-y) - 10%), rgba(0, 230, 176, 0.10), transparent 18%),
                radial-gradient(circle at calc(var(--pointer-x) - 14%) calc(var(--pointer-y) + 6%), rgba(0, 180, 216, 0.06), transparent 20%);
            transform: translate3d(calc(var(--hero-shift-x) * -0.08), calc(var(--hero-shift-y) * -0.08), 0);
        }

        .genesis-motion-orb {
            top: 1rem;
            right: 14%;
            height: 9rem;
            width: 9rem;
            border-radius: 999px;
            border: 1px solid rgba(52, 72, 77, 0.6);
            background:
                radial-gradient(circle at 35% 30%, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.02) 28%, transparent 58%),
                radial-gradient(circle at 50% 50%, rgba(10, 26, 24, 0.65), rgba(8, 8, 9, 0.12) 72%, transparent 100%);
            box-shadow: inset 0 0 0 1px rgba(0, 230, 176, 0.06);
            opacity: 0.5;
            transform: translate3d(calc(var(--hero-shift-x) * -0.18), calc(var(--hero-shift-y) * -0.16), 20px);
        }

        .genesis-motion-orb::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background-image:
                linear-gradient(rgba(0, 230, 176, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 230, 176, 0.12) 1px, transparent 1px);
            background-size: 1.15rem 1.15rem;
            mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 1), transparent 78%);
        }

        .genesis-wireframe {
            bottom: -10rem;
            width: 92rem;
            height: 38rem;
            border-radius: 50%;
            background-image:
                linear-gradient(rgba(0, 230, 176, 0.28) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 230, 176, 0.28) 1px, transparent 1px);
            background-size: 2rem 2rem;
            box-shadow:
                inset 0 0 0 1px rgba(0, 230, 176, 0.04),
                0 0 36px rgba(0, 230, 176, 0.04);
            opacity: 0.52;
            mix-blend-mode: screen;
        }

        .genesis-wireframe::before,
        .genesis-wireframe::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
        }

        .genesis-wireframe::before {
            background: radial-gradient(circle at 50% 32%, rgba(0, 230, 176, 0.08), transparent 34%);
        }

        .genesis-wireframe::after {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.02) 0%, rgba(0, 0, 0, 0.34) 58%, rgba(0, 0, 0, 0.84) 100%);
        }

        .genesis-wireframe--left {
            left: -34rem;
            border-radius: 46% 54% 52% 48% / 42% 58% 42% 58%;
            clip-path: polygon(0% 85%, 6% 77%, 13% 68%, 21% 61%, 30% 55%, 39% 50%, 47% 47%, 56% 43%, 64% 41%, 72% 43%, 80% 48%, 88% 55%, 94% 64%, 100% 76%, 100% 100%, 0% 100%);
            transform: translate3d(calc(var(--hero-shift-x) * -0.22), calc(var(--hero-shift-y) * 0.14), 0) rotateX(72deg) rotateZ(-13deg) skewX(-4deg);
        }

        .genesis-wireframe--right {
            right: -30rem;
            width: 84rem;
            height: 34rem;
            opacity: 0.82;
            border-radius: 58% 42% 48% 52% / 48% 52% 40% 60%;
            clip-path: polygon(0% 83%, 7% 74%, 16% 64%, 26% 57%, 37% 51%, 47% 47%, 58% 43%, 68% 41%, 78% 44%, 87% 50%, 94% 60%, 100% 74%, 100% 100%, 0% 100%);
            transform: translate3d(calc(var(--hero-shift-x) * -0.35), calc(var(--hero-shift-y) * 0.18), 60px) rotateX(74deg) rotateZ(11deg) skewX(4deg);
        }

        .genesis-wireframe--back {
            top: 3rem;
            left: 50%;
            width: 26rem;
            height: 12rem;
            opacity: 0.28;
            border-radius: 52% 48% 60% 40% / 40% 60% 34% 66%;
            clip-path: polygon(0% 86%, 11% 75%, 24% 66%, 38% 59%, 52% 54%, 66% 50%, 79% 53%, 90% 61%, 100% 76%, 100% 100%, 0% 100%);
            transform: translate3d(calc(-50% + (var(--hero-shift-x) * -0.08)), calc(var(--hero-shift-y) * -0.08), -40px) rotateX(68deg) skewX(-2deg);
        }

        .genesis-hero-shell {
            position: relative;
            z-index: 2;
            margin: 0 auto;
            max-width: 72rem;
            transform: rotateX(var(--hero-tilt-x)) rotateY(var(--hero-tilt-y));
            transition: transform 180ms ease-out;
        }

        .genesis-heading {
            text-wrap: balance;
            font-size: clamp(2.8rem, 7vw, 5.4rem);
            line-height: 1.02;
            letter-spacing: -0.03em;
        }

        .genesis-gradient {
            background: linear-gradient(90deg, #00e6b0 0%, #00b4d8 50%, #0088cc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .genesis-subtitle {
            margin: 1.75rem auto 0;
            max-width: 46rem;
            font-size: 1.15rem;
            line-height: 1.8rem;
            color: #7f7f8b;
        }

        .genesis-cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 999px;
            background: linear-gradient(90deg, #00e6b0 0%, #00a8cc 55%, #0088cc 100%);
            padding: 0.95rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: #050505;
            box-shadow: 0 0 40px rgba(0, 230, 176, 0.18);
        }

        .genesis-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 999px;
            border: 1px solid rgba(26, 26, 32, 0.8);
            padding: 0.95rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: #9a9aa5;
        }

        .genesis-stat-grid {
            margin-top: 4rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.5rem;
        }

        .genesis-stat {
            min-width: 8rem;
            text-align: center;
        }

        .genesis-hero-noise {
            position: absolute;
            inset: 0;
            z-index: 1;
            opacity: 0.18;
            background-image:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.5) 0 1px, transparent 1px),
                radial-gradient(circle at 80% 15%, rgba(255, 255, 255, 0.35) 0 1px, transparent 1px),
                radial-gradient(circle at 72% 82%, rgba(255, 255, 255, 0.4) 0 1px, transparent 1px);
            background-size: 14rem 14rem, 18rem 18rem, 22rem 22rem;
            transform: translate3d(calc(var(--hero-shift-x) * -0.05), calc(var(--hero-shift-y) * -0.05), 0);
            pointer-events: none;
        }

        .genesis-terminal {
            position: relative;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 1px solid rgba(26, 26, 32, 0.8);
            background: rgba(12, 12, 15, 0.88);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.26);
        }

        .genesis-terminal::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(0, 230, 176, 0.05), transparent 28%);
            pointer-events: none;
        }

        .genesis-terminal-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(26, 26, 32, 0.9);
            padding: 1rem 1.25rem;
        }

        .genesis-terminal-dots {
            display: flex;
            gap: 0.5rem;
        }

        .genesis-terminal-dots span {
            display: inline-flex;
            height: 0.7rem;
            width: 0.7rem;
            border-radius: 999px;
            background: #1f2937;
        }

        .genesis-terminal-body {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 1.25rem;
            padding: 1.5rem;
        }

        .genesis-terminal-line {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            font-size: 0.95rem;
            color: #9a9aa5;
        }

        .genesis-terminal-line strong {
            color: var(--accent);
        }

        .genesis-terminal-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .genesis-mini-card {
            border-radius: 1rem;
            border: 1px solid rgba(30, 30, 36, 0.9);
            background: rgba(10, 10, 13, 0.95);
            padding: 1rem;
        }

        .product-card {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 1.5rem;
            background: linear-gradient(180deg, rgba(11, 24, 41, 0.97), rgba(7, 15, 28, 0.98));
            border: 1px solid rgba(123, 169, 255, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04), 0 18px 42px rgba(2, 10, 18, 0.28);
            transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
        }

        .product-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 0%, rgba(110, 231, 255, 0.08) 45%, transparent 100%);
            transform: translateX(-120%);
            transition: transform 320ms ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(110, 231, 255, 0.38);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05), 0 22px 48px rgba(2, 10, 18, 0.38);
        }

        .product-card:hover::before {
            transform: translateX(120%);
        }

        .hero-stat,
        .signal-chip,
        .feature-tile,
        .category-pill {
            background: rgba(12, 12, 15, 0.92);
            border: 1px solid rgba(30, 30, 36, 0.8);
        }

        .hero-glow {
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 0 0 1px rgba(110, 231, 255, 0.06);
        }

        .section-kicker {
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #6b6b76;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .genesis-section-title {
            font-size: clamp(2rem, 4vw, 3.4rem);
            line-height: 1.08;
            letter-spacing: -0.03em;
            color: #e8e8ed;
        }

        .fade-up {
            animation: fadeUp 0.6s ease both;
        }

        .fade-up-delay {
            animation: fadeUp 0.8s ease both;
        }

        .editor-content {
            color: #d9e6fb;
        }

        .editor-content > * + * {
            margin-top: 1rem;
        }

        .editor-content h1,
        .editor-content h2,
        .editor-content h3,
        .editor-content h4 {
            font-family: 'Space Grotesk', sans-serif;
            color: #f8fbff;
            line-height: 1.15;
        }

        .editor-content a {
            color: #6ee7ff;
        }

        .editor-content ul,
        .editor-content ol {
            padding-left: 1.25rem;
        }

        .editor-content img {
            border-radius: 1rem;
            border: 1px solid rgba(123, 169, 255, 0.22);
        }

        .store-section {
            position: relative;
        }

        .store-product-thumb {
            position: relative;
            margin: 0 0 1.25rem;
            height: 9.5rem;
            width: 100%;
            overflow: hidden;
            border: 1px solid rgba(123, 169, 255, 0.12);
            border-radius: 1.25rem;
            background: linear-gradient(135deg, rgba(21, 35, 56, 0.95), rgba(8, 16, 30, 0.98));
            flex-shrink: 0;
        }

        .store-product-thumb img {
            height: 100%;
            width: 100%;
            object-fit: contain;
            object-position: center;
            padding: 0.75rem;
            transform: scale(1.01);
            transition: transform 220ms ease;
        }

        .store-product-thumb.is-empty {
            background:
                radial-gradient(circle at top, rgba(110, 231, 255, 0.14), transparent 52%),
                linear-gradient(135deg, rgba(21, 35, 56, 0.95), rgba(8, 16, 30, 0.98));
        }

        .store-product-thumb-placeholder {
            display: flex;
            height: 100%;
            width: 100%;
            flex-direction: column;
            justify-content: flex-end;
            padding: 1rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0)),
                radial-gradient(circle at 15% 20%, rgba(110, 231, 255, 0.12), transparent 24%);
        }

        .store-product-thumb-placeholder::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(110, 231, 255, 0.04) 100%);
            pointer-events: none;
        }

        .store-product-thumb-kicker {
            position: relative;
            z-index: 1;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(110, 231, 255, 0.82);
        }

        .store-product-thumb-title {
            position: relative;
            z-index: 1;
            margin-top: 0.45rem;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            line-height: 1.1;
            color: rgba(243, 247, 255, 0.9);
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .product-card-body {
            position: relative;
            z-index: 10;
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .product-card-head {
            min-height: 7.5rem;
        }

        .product-card-title {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            min-height: 3.75rem;
        }

        .product-card-description {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            min-height: 5.25rem;
        }

        .product-card-specs {
            min-height: 5.75rem;
            align-content: start;
        }

        .product-card-footer {
            margin-top: auto;
        }

        .product-card-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(110, 231, 255, 0.26);
            background: rgba(110, 231, 255, 0.12);
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #c9f8ff;
            transition: background 180ms ease, border-color 180ms ease, transform 180ms ease;
        }

        .product-card-cta:hover {
            background: rgba(110, 231, 255, 0.18);
            border-color: rgba(110, 231, 255, 0.4);
            transform: translateY(-1px);
        }

        .product-card:hover .store-product-thumb img {
            transform: scale(1.06);
        }

        .store-admin-shell {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 70;
            width: min(24rem, calc(100vw - 2rem));
        }

        .store-admin-panel {
            border: 1px solid rgba(110, 231, 255, 0.24);
            background: rgba(7, 14, 26, 0.92);
            box-shadow: 0 24px 70px rgba(2, 8, 18, 0.46);
        }

        .store-admin-list {
            display: grid;
            gap: 0.55rem;
        }

        .store-admin-sort-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
            padding: 0.75rem 0.85rem;
        }

        .store-sort-handle {
            cursor: grab;
            color: #9cb2d3;
            font-size: 1rem;
            letter-spacing: 0.2em;
        }

        .store-admin-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(110, 231, 255, 0.18);
            background: rgba(110, 231, 255, 0.08);
            padding: 0.5rem 0.85rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #d8f7ff;
        }

        .store-edit-toolbar {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 40;
            display: none;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .store-edit-target {
            position: relative;
        }

        body.store-edit-enabled .store-edit-target {
            outline: 1px dashed rgba(110, 231, 255, 0.5);
            outline-offset: 10px;
            border-radius: 1.6rem;
        }

        body.store-edit-enabled .store-edit-toolbar {
            display: flex;
        }

        .store-edit-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(5, 10, 20, 0.88);
            padding: 0.45rem 0.8rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #f4fbff;
        }

        .store-edit-link:hover {
            border-color: rgba(110, 231, 255, 0.36);
            color: #b7f4ff;
        }

        .store-editor-overlay {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(2, 8, 18, 0.72);
        }

        .store-editor-overlay.is-open {
            display: flex;
        }

        .store-editor-dialog {
            width: min(66rem, 100%);
            max-height: calc(100vh - 3rem);
            overflow: auto;
            border: 1px solid rgba(110, 231, 255, 0.18);
            background: rgba(7, 14, 26, 0.96);
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.45);
        }

        .store-editor-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .store-editor-field,
        .store-editor-repeater-item {
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
            padding: 0.85rem;
        }

        .store-editor-field label,
        .store-editor-repeater-item label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #a8bfdc;
        }

        .store-editor-field input,
        .store-editor-field textarea,
        .store-editor-field select,
        .store-editor-repeater-item input,
        .store-editor-repeater-item textarea,
        .store-editor-repeater-item select {
            margin-top: 0.55rem;
            width: 100%;
            border-radius: 0.85rem;
            border: 1px solid rgba(123, 169, 255, 0.12);
            background: rgba(5, 10, 20, 0.88);
            color: #f3f7ff;
            padding: 0.8rem 0.9rem;
        }

        .store-editor-field textarea,
        .store-editor-repeater-item textarea {
            min-height: 8rem;
        }

        .store-editor-field.is-full {
            grid-column: 1 / -1;
        }

        .store-editor-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .store-editor-repeater-item + .store-editor-repeater-item {
            margin-top: 0.75rem;
        }

        .store-editor-help {
            margin-top: 0.45rem;
            font-size: 0.78rem;
            color: #8fa3c2;
        }

        .store-editor-upload-row {
            margin-top: 0.75rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.6rem;
        }

        .store-editor-toast {
            position: fixed;
            left: 50%;
            bottom: 1.5rem;
            z-index: 90;
            transform: translateX(-50%);
            display: none;
            border-radius: 999px;
            border: 1px solid rgba(110, 231, 255, 0.2);
            background: rgba(7, 14, 26, 0.96);
            padding: 0.8rem 1rem;
            color: #f2fbff;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
        }

        .store-editor-toast.is-visible {
            display: inline-flex;
        }

        body.store-motion-lite .store-global-glow,
        body.store-motion-lite .genesis-motion-glow,
        body.store-motion-lite .genesis-motion-orb,
        body.store-motion-lite .genesis-wireframe--back,
        body.store-motion-lite .store-global-wireframe--center,
        body.store-motion-lite .store-global-orb {
            display: none;
        }

        body.store-motion-lite .genesis-motion-field {
            opacity: 0.28;
        }

        body.store-motion-lite .genesis-hero-shell {
            transition-duration: 120ms;
        }

        @media (max-width: 768px) {
            .genesis-motion-field {
                display: none;
            }

            .store-global-backdrop::after {
                background-size: 44px 44px;
            }

            .store-global-orb {
                top: 4rem;
                right: 4%;
                height: 9rem;
                width: 9rem;
            }

            .store-global-wireframe {
                background-size: 1.35rem 1.35rem;
            }

            .store-global-wireframe--left {
                left: -30rem;
                width: 76rem;
                height: 28rem;
            }

            .store-global-wireframe--center {
                left: -2rem;
                width: 66rem;
                height: 24rem;
            }

            .store-global-wireframe--right {
                right: -24rem;
                top: 10rem;
                width: 72rem;
                height: 28rem;
            }

            .genesis-hero {
                min-height: 34rem;
                padding-top: 2rem;
                padding-bottom: 3rem;
            }

            .genesis-motion-orb {
                right: 8%;
                height: 6rem;
                width: 6rem;
            }

            .genesis-wireframe {
                width: 62rem;
                height: 24rem;
                background-size: 1.4rem 1.4rem;
            }

            .genesis-wireframe--left {
                left: -26rem;
            }

            .genesis-wireframe--right {
                right: -22rem;
            }

            .store-editor-grid {
                grid-template-columns: 1fr;
            }

            .genesis-terminal-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .store-global-backdrop::before,
            .page-shell::after,
            .store-global-glow,
            .store-global-orb,
            .store-global-wireframe,
            .store-global-noise,
            .genesis-motion-glow,
            .genesis-motion-orb,
            .genesis-wireframe,
            .genesis-hero-shell,
            .genesis-hero-noise {
                transition: none;
                transform: none !important;
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="page-shell min-h-screen text-white" style="{{ $storeBackgroundStyle }}">
    <svg aria-hidden="true" width="0" height="0" style="position:absolute;left:-9999px;top:-9999px;overflow:hidden">
        <defs>
        </defs>
    </svg>
    <div class="store-global-backdrop" aria-hidden="true">
        <div class="store-global-noise"></div>
        <div class="store-global-glow"></div>
        <div class="store-global-wireframe store-global-wireframe--left"></div>
        <div class="store-global-wireframe store-global-wireframe--center"></div>
        <div class="store-global-wireframe store-global-wireframe--right"></div>
        <div class="store-global-orb"></div>
    </div>
    @if($isStoreAdmin)
        <div class="store-admin-shell">
            <div class="store-admin-panel rounded-[1.5rem] p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="store-admin-chip">Store Admin</div>
                        <div class="mt-2 text-sm text-slate-300">Visual edit mode for the live storefront.</div>
                    </div>
                    <button type="button" id="store-edit-toggle" class="store-edit-link">Enable Edit Mode</button>
                </div>
                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    <button type="button" class="store-edit-link" data-editor-section="header">Header</button>
                    <button type="button" class="store-edit-link" data-editor-section="background">Background</button>
                    <button type="button" class="store-edit-link" data-editor-section="hero">Hero</button>
                    <button type="button" class="store-edit-link" data-editor-section="footer_notice">Footer</button>
                    <a href="{{ $storeProductsUrl }}" class="store-edit-link">Products</a>
                    <a href="{{ $storeMediaUrl }}" class="store-edit-link">Media</a>
                </div>
                <div class="mt-4">
                    <div class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Homepage Order</div>
                    <div class="store-admin-list" id="store-block-sort-list">
                        @foreach($homepageBlocks as $block)
                            <div class="store-admin-sort-item" data-block-id="{{ $block['id'] }}">
                                <div class="flex items-center gap-3">
                                    <span class="store-sort-handle">::</span>
                                    <div>
                                        <div class="text-sm font-semibold text-white">{{ $block['label'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $block['enabled'] ? 'Visible' : 'Hidden' }}</div>
                                    </div>
                                </div>
                                <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($storeAnnouncement['enabled'] && filled($storeAnnouncement['text']))
        <div class="store-section {{ $announcementClasses }} store-edit-target">
            @if($isStoreAdmin)
                <div class="store-edit-toolbar">
                    <button type="button" class="store-edit-link" data-editor-section="header">Edit Announcement</button>
                </div>
            @endif
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-3 px-6 py-3 text-center text-sm lg:px-8">
                <span>{{ $storeAnnouncement['text'] }}</span>
                @if(filled($storeAnnouncement['link_label']) && filled($storeAnnouncement['link_url']))
                    <a href="{{ $storeAnnouncement['link_url'] }}" class="font-semibold underline underline-offset-4">{{ $storeAnnouncement['link_label'] }}</a>
                @endif
            </div>
        </div>
    @endif

    <header class="sticky top-0 z-50 border-b border-white/5 bg-[rgba(4,10,18,0.82)] backdrop-blur-xl store-edit-target">
        @if($isStoreAdmin)
            <div class="store-edit-toolbar">
                <button type="button" class="store-edit-link" data-editor-section="header">Edit Header</button>
            </div>
        @endif
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="{{ $storeHeader['store_url'] }}" class="flex items-center gap-3">
                @include('shadow-store::pages.partials.store-logo', [
                    'sizeClass' => 'h-11 w-11',
                    'containerClass' => 'rounded-2xl border border-cyan-300/20 bg-cyan-400/10 p-1.5 hero-glow',
                    'imageClass' => 'h-full w-full object-contain',
                    'textClass' => 'font-display text-base font-bold text-cyan-200',
                    'logoWrapperClass' => 'h-11 w-auto max-w-[10rem] shrink-0',
                    'logoContainerClass' => 'hero-glow',
                    'logoImageClass' => 'block h-full w-auto max-w-full object-contain drop-shadow-[0_0_18px_rgba(34,211,238,0.18)]',
                ])
                <div>
                    <div class="font-display text-lg font-bold tracking-tight">{{ $storeHeader['brand_name'] }}</div>
                    <div class="text-xs uppercase tracking-[0.24em] text-slate-400">{{ $storeHeader['brand_tagline'] }}</div>
                </div>
            </a>
            <nav class="{{ $navShellClasses }}">
                <a href="{{ $storeHeader['store_url'] }}" class="{{ $navActiveClasses }}">{{ $storeHeader['store_label'] }}</a>
                <a href="{{ $storeHeader['dedicated_url'] }}" class="{{ $navLinkClasses }}">{{ $storeHeader['dedicated_label'] }}</a>
                <a href="{{ $storeHeader['msa_url'] }}" class="{{ $navLinkClasses }}">{{ $storeHeader['msa_label'] }}</a>
                <a href="{{ $storeHeader['wiki_url'] }}" class="{{ $navLinkClasses }}">{{ $storeHeader['wiki_label'] }}</a>
                <a href="/store/cart" class="{{ $navLinkClasses }}">Cart</a>
                <a href="{{ $storeHeader['discord_url'] }}" target="_blank" rel="noopener noreferrer" class="{{ $navLinkClasses }}">{{ $storeHeader['discord_label'] }}</a>
                @auth
                    <a href="{{ route('store.billing') }}" class="{{ $navLinkClasses }}">Billing</a>
                    <a href="/" class="{{ $navLinkClasses }}">My Servers</a>
                @else
                    <a href="/login" class="rounded-full border border-cyan-300/20 bg-cyan-400/12 px-5 py-2.5 font-semibold text-cyan-100 transition hover:bg-cyan-400/18">Login</a>
                @endauth
            </nav>
        </div>

        @if($storePromo['enabled'] && filled($storePromo['text']))
            <div class="{{ $promoClasses }} store-edit-target">
                @if($isStoreAdmin)
                    <div class="store-edit-toolbar">
                        <button type="button" class="store-edit-link" data-editor-section="header">Edit Promo</button>
                    </div>
                @endif
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-3 text-sm lg:px-8">
                    <div class="font-medium">{{ $storePromo['text'] }}</div>
                    @if(filled($storePromo['button_label']) && filled($storePromo['button_url']))
                        <a href="{{ $storePromo['button_url'] }}" class="rounded-full border border-white/10 bg-white/8 px-4 py-2 font-semibold text-white">{{ $storePromo['button_label'] }}</a>
                    @endif
                </div>
            </div>
        @endif
    </header>

    <main class="mx-auto flex w-full max-w-7xl flex-col gap-20 px-6 py-10 lg:px-8 lg:py-14">
        <section class="store-section genesis-hero fade-up store-edit-target">
            @if($isStoreAdmin)
                <div class="store-edit-toolbar">
                    <button type="button" class="store-edit-link" data-editor-section="hero">Edit Hero</button>
                    <button type="button" class="store-edit-link" data-editor-section="background">Edit Background</button>
                </div>
            @endif
            <div class="genesis-motion-field" data-motion-root>
                <div class="genesis-hero-noise"></div>
                <div class="genesis-motion-glow"></div>
                <div class="genesis-wireframe genesis-wireframe--left"></div>
                <div class="genesis-wireframe genesis-wireframe--right"></div>
                <div class="genesis-wireframe genesis-wireframe--back"></div>
                <div class="genesis-motion-orb"></div>
            </div>
            <div class="genesis-hero-shell">
                <div class="genesis-pill mx-auto w-fit">
                    <span class="genesis-dot"></span>
                    <span>{{ $storeHome['kicker'] ?: 'Deploy game servers in under 60 seconds' }}</span>
                </div>
                <h1 class="genesis-heading mx-auto mt-8 max-w-5xl font-bold text-white">
                    {{ $storeHome['title'] }}
                    <span class="genesis-gradient"> without generic host clutter.</span>
                </h1>
                <p class="genesis-subtitle">{{ $storeHome['subtitle'] }}</p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ $storeHome['primary_cta_url'] ?: '#catalog' }}" class="genesis-cta-primary">{{ $storeHome['primary_cta_label'] ?: 'Browse Game Servers' }}</a>
                    <a href="{{ $storeHome['secondary_cta_url'] ?: '/store/dedicated' }}" class="genesis-cta-secondary">{{ $storeHome['secondary_cta_label'] ?: 'Explore Dedicated Machines' }}</a>
                </div>

                <div class="genesis-stat-grid">
                    <div class="genesis-stat">
                        <div class="font-display text-3xl font-bold text-white">${{ number_format($startingPrice, 2) }}</div>
                        <div class="mt-1 text-sm text-[#6b6b76]">Starting monthly</div>
                    </div>
                    <div class="genesis-stat">
                        <div class="font-display text-3xl font-bold text-white">{{ $featuredProducts->count() }}</div>
                        <div class="mt-1 text-sm text-[#6b6b76]">Featured builds</div>
                    </div>
                    <div class="genesis-stat">
                        <div class="font-display text-3xl font-bold text-white">{{ $gameCategoryCount }}</div>
                        <div class="mt-1 text-sm text-[#6b6b76]">Game families</div>
                    </div>
                    <div class="genesis-stat">
                        <div class="font-display text-3xl font-bold text-white">200+</div>
                        <div class="mt-1 text-sm text-[#6b6b76]">Supported titles</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="store-section fade-up store-edit-target">
            <div class="genesis-terminal">
                <div class="genesis-terminal-top">
                    <div class="genesis-terminal-dots"><span></span><span></span><span></span></div>
                    <div class="text-xs uppercase tracking-[0.16em] text-[#6b6b76]">store-terminal</div>
                </div>
                <div class="genesis-terminal-body">
                    <div class="genesis-terminal-line"><span class="text-[#6b6b76]">$</span> <strong>help</strong> <span>Available storefront operations</span></div>
                    <div class="genesis-terminal-line"><span class="text-[#6b6b76]">></span> <span>Featured plans live: <strong>{{ $featuredProducts->count() }}</strong></span></div>
                    <div class="genesis-terminal-line"><span class="text-[#6b6b76]">></span> <span>Catalog families online: <strong>{{ $gameCategoryCount }}</strong></span></div>
                    <div class="genesis-terminal-line"><span class="text-[#6b6b76]">></span> <span>Primary hardware lane: <strong>Ryzen 9 9950X3D + NVMe</strong></span></div>
                    <div class="genesis-terminal-line"><span class="text-[#6b6b76]">></span> <span>Billing path: <strong>cart, checkout, billing, and orders unified</strong></span></div>
                    <div class="genesis-terminal-grid">
                        @foreach($terminalHighlights as $item)
                            <div class="genesis-mini-card">
                                <div class="text-[0.7rem] uppercase tracking-[0.18em] text-[#6b6b76]">{{ $item['label'] }}</div>
                                <div class="mt-2 text-lg font-semibold text-white">{{ $item['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="store-section fade-up grid gap-5 lg:grid-cols-3">
            @foreach($testimonialCards as $card)
                <div class="section-card rounded-[1.5rem] p-6">
                    <div class="text-sm leading-7 text-[#b0b0ba]">"{{ $card['quote'] }}"</div>
                    <div class="mt-6 border-t border-[#1a1a20] pt-4">
                        <div class="font-semibold text-white">{{ $card['name'] }}</div>
                        <div class="text-xs uppercase tracking-[0.18em] text-[#6b6b76]">{{ $card['role'] }}</div>
                    </div>
                </div>
            @endforeach
        </section>

        @foreach($homepageBlocks as $block)
            @if($block['enabled'])
                @include('shadow-store::pages.storefront-blocks.' . $block['id'], ['block' => $block])
            @endif
        @endforeach
    </main>

    <footer class="store-section mt-10 border-t border-white/6 px-6 py-10 lg:px-8 store-edit-target">
        @if($isStoreAdmin)
            <div class="store-edit-toolbar">
                <button type="button" class="store-edit-link" data-editor-section="footer_notice">Edit Footer Notice</button>
            </div>
        @endif
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
            <div class="font-display text-base text-slate-300">Shadow Haven Hosting</div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-6">
                <a href="https://discord.gg/AqCVPtpgYQ" target="_blank" rel="noopener noreferrer" class="text-slate-400 transition hover:text-cyan-200">Join the Shadow Haven Hosting Discord</a>
                <div>
                    © {{ date('Y') }} Shadow Haven Hosting. All rights reserved.
                </div>
            </div>
        </div>
        @if(filled($footerNotice['title']) || filled($footerNotice['body']))
            <div class="mt-8 mx-auto max-w-3xl rounded-xl bg-amber-100/10 border border-amber-300/30 px-6 py-5 text-center text-base text-amber-200 font-semibold">
                @if(filled($footerNotice['title']))
                    <span class="block text-lg font-bold text-amber-300 mb-1">{{ $footerNotice['title'] }}</span>
                @endif
                @if(filled($footerNotice['body']))
                    <span class="block text-amber-100">{{ $footerNotice['body'] }}</span>
                @endif
            </div>
        @endif
    </footer>

    @if($isStoreAdmin)
        <div class="store-editor-overlay" id="store-editor-overlay">
            <div class="store-editor-dialog rounded-[1.75rem] p-5 sm:p-6">
                <div class="flex items-center justify-between gap-4 border-b border-white/8 pb-4">
                    <div>
                        <div class="store-admin-chip">Inline Editor</div>
                        <h2 class="mt-3 text-2xl font-bold text-white" id="store-editor-title">Edit Store</h2>
                    </div>
                    <button type="button" class="store-edit-link" id="store-editor-close">Close</button>
                </div>
                <div class="mt-5" id="store-editor-body"></div>
                <div class="mt-5 store-editor-actions">
                    <button type="button" class="store-edit-link" id="store-editor-cancel">Cancel</button>
                    <button type="button" class="store-edit-link" id="store-editor-save">Save Changes</button>
                </div>
            </div>
        </div>
        <div class="store-editor-toast" id="store-editor-toast"></div>
    @endif
    @if($isStoreAdmin)
        <script>
            (() => {
                const toggle = document.getElementById('store-edit-toggle');
                const key = 'shadow-store-edit-mode';
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
                const overlay = document.getElementById('store-editor-overlay');
                const editorTitle = document.getElementById('store-editor-title');
                const editorBody = document.getElementById('store-editor-body');
                const saveButton = document.getElementById('store-editor-save');
                const closeButton = document.getElementById('store-editor-close');
                const cancelButton = document.getElementById('store-editor-cancel');
                const toast = document.getElementById('store-editor-toast');
                const state = JSON.parse(JSON.stringify(@json($storeEditorState)));
                let mediaAssets = JSON.parse(JSON.stringify(@json($mediaAssets)));
                let currentEditor = null;

                const sectionTitles = {
                    header: 'Header, Announcement, and Promo',
                    background: 'Store Background',
                    hero: 'Hero Section',
                    footer_notice: 'Footer Notice',
                };

                const clone = (value) => JSON.parse(JSON.stringify(value));

                const requestJson = async (url, options = {}) => {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        ...options,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(options.headers || {}),
                        },
                    });

                    const raw = await response.text();
                    let data = null;

                    try {
                        data = raw ? JSON.parse(raw) : {};
                    } catch (error) {
                        if (!response.ok) {
                            const reason = response.status === 419
                                ? 'Your session expired. Refresh the page and try again.'
                                : `Request failed with status ${response.status}.`;

                            throw new Error(reason);
                        }

                        throw new Error('The server returned an unexpected response. Refresh the page and try again.');
                    }

                    if (!response.ok) {
                        const validationMessage = data?.errors
                            ? Object.values(data.errors).flat().find(Boolean)
                            : null;

                        throw new Error(validationMessage || data?.message || `Request failed with status ${response.status}.`);
                    }

                    return data;
                };

                const showToast = (message, isError = false) => {
                    if (!toast) {
                        return;
                    }

                    toast.textContent = message;
                    toast.classList.add('is-visible');
                    toast.style.borderColor = isError ? 'rgba(251, 113, 133, 0.28)' : 'rgba(110, 231, 255, 0.2)';

                    window.clearTimeout(showToast.timeout);
                    showToast.timeout = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
                };

                const getByPath = (obj, path) => path.split('.').reduce((carry, key) => carry?.[key], obj);

                const setByPath = (obj, path, value) => {
                    const parts = path.split('.');
                    let cursor = obj;

                    for (let index = 0; index < parts.length - 1; index++) {
                        const key = parts[index];
                        const nextKey = parts[index + 1];

                        if (!(key in cursor) || cursor[key] === null) {
                            cursor[key] = Number.isInteger(Number(nextKey)) ? [] : {};
                        }

                        cursor = cursor[key];
                    }

                    cursor[parts[parts.length - 1]] = value;
                };

                const fieldSchemas = {
                    header: [
                        { path: 'logo_asset_id', label: 'Logo Image', type: 'media', full: true, help: 'Pick a shared media item for the brand mark.' },
                        { path: 'logo_url', label: 'Logo URL Fallback', type: 'url', full: true },
                        { path: 'badge_text', label: 'Badge Text Fallback' },
                        { path: 'brand_name', label: 'Brand Name' },
                        { path: 'brand_tagline', label: 'Brand Tagline' },
                        { path: 'nav_style', label: 'Top Nav Style', type: 'select', options: [
                            { value: 'soft', label: 'Soft Pill' },
                            { value: 'outline', label: 'Outline' },
                            { value: 'solid', label: 'Solid' },
                        ] },
                        { path: 'store_label', label: 'Store Label' },
                        { path: 'store_url', label: 'Store URL', type: 'url' },
                        { path: 'dedicated_label', label: 'Dedicated Label' },
                        { path: 'dedicated_url', label: 'Dedicated URL', type: 'url' },
                        { path: 'msa_label', label: 'MSA Label' },
                        { path: 'msa_url', label: 'MSA URL', type: 'url' },
                        { path: 'wiki_label', label: 'Wiki Label' },
                        { path: 'wiki_url', label: 'Wiki URL', type: 'url' },
                        { path: 'discord_label', label: 'Discord Label' },
                        { path: 'discord_url', label: 'Discord URL', type: 'url' },
                        { path: 'announcement.enabled', label: 'Show Announcement Bar', type: 'checkbox', full: true },
                        { path: 'announcement.text', label: 'Announcement Text', type: 'textarea', full: true },
                        { path: 'announcement.link_label', label: 'Announcement Link Label' },
                        { path: 'announcement.link_url', label: 'Announcement Link URL', type: 'url' },
                        { path: 'announcement.style', label: 'Announcement Style', type: 'select', options: [
                            { value: 'accent', label: 'Accent' },
                            { value: 'warm', label: 'Warm' },
                            { value: 'danger', label: 'Alert' },
                        ] },
                        { path: 'promo.enabled', label: 'Show Promo Banner', type: 'checkbox', full: true },
                        { path: 'promo.text', label: 'Promo Text', type: 'textarea', full: true },
                        { path: 'promo.button_label', label: 'Promo Button Label' },
                        { path: 'promo.button_url', label: 'Promo Button URL', type: 'url' },
                        { path: 'promo.style', label: 'Promo Style', type: 'select', options: [
                            { value: 'cyan', label: 'Cyan' },
                            { value: 'warm', label: 'Warm' },
                            { value: 'emerald', label: 'Emerald' },
                        ] },
                    ],
                    background: [
                        { path: 'color_start', label: 'Gradient Start', type: 'color' },
                        { path: 'color_end', label: 'Gradient End', type: 'color' },
                        { path: 'overlay_opacity', label: 'Overlay Opacity', type: 'number', step: '0.05', min: '0', max: '1' },
                        { path: 'image_asset_id', label: 'Background Image', type: 'media', full: true, help: 'Choose an existing media asset or upload a new one below.' },
                        { path: 'image_url', label: 'Fallback Image URL', type: 'url', full: true },
                    ],
                    hero: [
                        { path: 'kicker', label: 'Kicker' },
                        { path: 'title', label: 'Headline', full: true },
                        { path: 'subtitle', label: 'Subtitle', type: 'textarea', full: true },
                        { path: 'primary_cta_label', label: 'Primary CTA Label' },
                        { path: 'primary_cta_url', label: 'Primary CTA URL', type: 'url' },
                        { path: 'secondary_cta_label', label: 'Secondary CTA Label' },
                        { path: 'secondary_cta_url', label: 'Secondary CTA URL', type: 'url' },
                        { path: 'media_asset_id', label: 'Hero Image', type: 'media', full: true },
                    ],
                    footer_notice: [
                        { path: 'title', label: 'Notice Title', full: true },
                        { path: 'body', label: 'Notice Body', type: 'textarea', full: true },
                    ],
                };

                const blockSchemas = {
                    home_rich_content: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.content', label: 'Content HTML', type: 'textarea', full: true },
                    ],
                    value_props: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.cards', label: 'Cards', type: 'repeater', full: true, fields: [
                            { path: 'kicker', label: 'Kicker' },
                            { path: 'title', label: 'Title' },
                            { path: 'body', label: 'Body', type: 'textarea', full: true },
                        ], emptyItem: { kicker: '', title: '', body: '' } },
                    ],
                    catalog_access: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.kicker', label: 'Kicker' },
                        { path: 'settings.body', label: 'Body', type: 'textarea', full: true },
                        { path: 'settings.primary_label', label: 'Primary Label' },
                        { path: 'settings.primary_url', label: 'Primary URL', type: 'url' },
                        { path: 'settings.secondary_label', label: 'Secondary Label' },
                        { path: 'settings.secondary_url', label: 'Secondary URL', type: 'url' },
                    ],
                    featured_products: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.kicker', label: 'Kicker' },
                        { path: 'settings.title', label: 'Title', full: true },
                        { path: 'settings.body', label: 'Body', type: 'textarea', full: true },
                    ],
                    catalog: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.kicker', label: 'Kicker' },
                        { path: 'settings.title', label: 'Title', full: true },
                        { path: 'settings.body', label: 'Body', type: 'textarea', full: true },
                    ],
                    dedicated_cta: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.kicker', label: 'Kicker' },
                        { path: 'settings.title', label: 'Title', full: true },
                        { path: 'settings.body', label: 'Body', type: 'textarea', full: true },
                        { path: 'settings.button_label', label: 'Button Label' },
                        { path: 'settings.button_url', label: 'Button URL', type: 'url' },
                        { path: 'settings.stats', label: 'Stats', type: 'repeater', full: true, fields: [
                            { path: 'label', label: 'Label' },
                            { path: 'value', label: 'Value' },
                            { path: 'body', label: 'Body', type: 'textarea', full: true },
                        ], emptyItem: { label: '', value: '', body: '' } },
                    ],
                    trust_cards: [
                        { path: 'enabled', label: 'Visible', type: 'checkbox', full: true },
                        { path: 'settings.cards', label: 'Cards', type: 'repeater', full: true, fields: [
                            { path: 'icon', label: 'Icon', type: 'select', options: [
                                { value: 'bolt', label: 'Bolt' },
                                { value: 'shield', label: 'Shield' },
                                { value: 'star', label: 'Star' },
                            ] },
                            { path: 'title', label: 'Title' },
                            { path: 'body', label: 'Body', type: 'textarea', full: true },
                        ], emptyItem: { icon: 'bolt', title: '', body: '' } },
                    ],
                };

                if (!toggle) {
                    return;
                }

                const applyState = (enabled) => {
                    document.body.classList.toggle('store-edit-enabled', enabled);
                    toggle.textContent = enabled ? 'Disable Edit Mode' : 'Enable Edit Mode';
                };

                const initial = localStorage.getItem(key) === '1';
                applyState(initial);

                toggle.addEventListener('click', () => {
                    const next = !document.body.classList.contains('store-edit-enabled');
                    localStorage.setItem(key, next ? '1' : '0');
                    applyState(next);
                });

                const closeEditor = () => {
                    currentEditor = null;
                    overlay?.classList.remove('is-open');
                    editorBody.innerHTML = '';
                };

                closeButton?.addEventListener('click', closeEditor);
                cancelButton?.addEventListener('click', closeEditor);
                overlay?.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeEditor();
                    }
                });

                const coerceValue = (field, rawValue) => {
                    if (field.type === 'checkbox') {
                        return Boolean(rawValue);
                    }

                    if (field.type === 'number') {
                        return rawValue === '' ? '' : Number(rawValue);
                    }

                    return rawValue;
                };

                const createField = (field, data, basePath = '') => {
                    const path = basePath ? `${basePath}.${field.path}` : field.path;
                    const wrapper = document.createElement('div');
                    wrapper.className = `store-editor-field${field.full ? ' is-full' : ''}`;

                    if (field.type === 'checkbox') {
                        wrapper.innerHTML = `<label><input type="checkbox" class="mr-2"> ${field.label}</label>`;
                        const input = wrapper.querySelector('input');
                        input.checked = Boolean(getByPath(data, path));
                        input.addEventListener('change', () => setByPath(data, path, input.checked));

                        return wrapper;
                    }

                    const label = document.createElement('label');
                    label.textContent = field.label;
                    wrapper.appendChild(label);

                    if (field.type === 'textarea') {
                        const textarea = document.createElement('textarea');
                        textarea.value = getByPath(data, path) ?? '';
                        textarea.addEventListener('input', () => setByPath(data, path, textarea.value));
                        wrapper.appendChild(textarea);
                    } else if (field.type === 'select') {
                        const select = document.createElement('select');
                        (field.options ?? []).forEach((option) => {
                            const item = document.createElement('option');
                            item.value = option.value;
                            item.textContent = option.label;
                            select.appendChild(item);
                        });
                        select.value = getByPath(data, path) ?? field.options?.[0]?.value ?? '';
                        select.addEventListener('change', () => setByPath(data, path, select.value));
                        wrapper.appendChild(select);
                    } else if (field.type === 'repeater') {
                        const list = document.createElement('div');
                        const renderRepeater = () => {
                            list.innerHTML = '';
                            const items = getByPath(data, path) ?? [];

                            items.forEach((item, index) => {
                                const itemWrapper = document.createElement('div');
                                itemWrapper.className = 'store-editor-repeater-item';
                                const actions = document.createElement('div');
                                actions.className = 'mb-3 flex items-center justify-between';
                                actions.innerHTML = `<div class="text-sm font-semibold text-white">Item ${index + 1}</div><button type="button" class="store-edit-link">Remove</button>`;
                                actions.querySelector('button').addEventListener('click', () => {
                                    items.splice(index, 1);
                                    setByPath(data, path, items);
                                    renderRepeater();
                                });
                                itemWrapper.appendChild(actions);

                                const grid = document.createElement('div');
                                grid.className = 'store-editor-grid';
                                field.fields.forEach((subField) => {
                                    grid.appendChild(createField(subField, data, `${path}.${index}`));
                                });
                                itemWrapper.appendChild(grid);
                                list.appendChild(itemWrapper);
                            });
                        };

                        const addButton = document.createElement('button');
                        addButton.type = 'button';
                        addButton.className = 'store-edit-link';
                        addButton.textContent = `Add ${field.label}`;
                        addButton.addEventListener('click', () => {
                            const items = getByPath(data, path) ?? [];
                            items.push(clone(field.emptyItem ?? {}));
                            setByPath(data, path, items);
                            renderRepeater();
                        });

                        renderRepeater();
                        wrapper.appendChild(list);
                        wrapper.appendChild(addButton);
                    } else if (field.type === 'media') {
                        const select = document.createElement('select');
                        const none = document.createElement('option');
                        none.value = '';
                        none.textContent = 'No media selected';
                        select.appendChild(none);

                        mediaAssets.forEach((asset) => {
                            const option = document.createElement('option');
                            option.value = asset.id;
                            option.textContent = asset.name;
                            select.appendChild(option);
                        });

                        const currentValue = getByPath(data, path) ?? '';
                        select.value = currentValue;
                        select.addEventListener('change', () => setByPath(data, path, select.value ? Number(select.value) : null));
                        wrapper.appendChild(select);

                        const help = document.createElement('div');
                        help.className = 'store-editor-help';
                        help.textContent = field.help ?? '';
                        wrapper.appendChild(help);

                        const uploadRow = document.createElement('div');
                        uploadRow.className = 'store-editor-upload-row';
                        const fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.accept = 'image/*';
                        const uploadButton = document.createElement('button');
                        uploadButton.type = 'button';
                        uploadButton.className = 'store-edit-link';
                        uploadButton.textContent = 'Upload New Image';
                        uploadButton.addEventListener('click', () => fileInput.click());
                        fileInput.addEventListener('change', async () => {
                            if (!fileInput.files?.length) {
                                return;
                            }

                            const payload = new FormData();
                            payload.append('file', fileInput.files[0]);
                            const selectedFileName = fileInput.files[0].name;

                            try {
                                const result = await requestJson('{{ $editorUploadUrl }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': csrf },
                                    body: payload,
                                });

                                if (!result.ok) {
                                    throw new Error(result.message || 'Upload failed.');
                                }

                                mediaAssets.unshift(result.asset);
                                const option = document.createElement('option');
                                option.value = result.asset.id;
                                option.textContent = result.asset.name;
                                select.prepend(option);
                                select.value = result.asset.id;
                                setByPath(data, path, result.asset.id);
                                showToast(`Media uploaded: ${selectedFileName}`);
                                fileInput.value = '';
                            } catch (error) {
                                showToast(error.message || 'Upload failed.', true);
                            }
                        });

                        uploadRow.appendChild(uploadButton);
                        uploadRow.appendChild(fileInput);
                        wrapper.appendChild(uploadRow);
                    } else {
                        const input = document.createElement('input');
                        input.type = field.type === 'color' ? 'color' : (field.type || 'text');
                        if (field.step) input.step = field.step;
                        if (field.min) input.min = field.min;
                        if (field.max) input.max = field.max;
                        input.value = getByPath(data, path) ?? '';
                        input.addEventListener('input', () => setByPath(data, path, coerceValue(field, input.value)));
                        wrapper.appendChild(input);
                    }

                    return wrapper;
                };

                const renderEditor = () => {
                    if (!currentEditor) {
                        return;
                    }

                    const schema = currentEditor.mode === 'section'
                        ? fieldSchemas[currentEditor.key]
                        : blockSchemas[currentEditor.key];

                    const title = currentEditor.mode === 'section'
                        ? (sectionTitles[currentEditor.key] ?? 'Edit Section')
                        : (currentEditor.data.label ?? 'Edit Block');

                    editorTitle.textContent = title;
                    editorBody.innerHTML = '';
                    const grid = document.createElement('div');
                    grid.className = 'store-editor-grid';
                    schema.forEach((field) => grid.appendChild(createField(field, currentEditor.data)));
                    editorBody.appendChild(grid);
                    overlay.classList.add('is-open');
                };

                const openSectionEditor = (section) => {
                    currentEditor = {
                        mode: 'section',
                        key: section,
                        data: clone(state[section]),
                    };
                    renderEditor();
                };

                const openBlockEditor = (blockId) => {
                    const block = state.homepage_blocks.find((item) => item.id === blockId);

                    if (!block) {
                        return;
                    }

                    currentEditor = {
                        mode: 'block',
                        key: blockId,
                        data: clone(block),
                    };
                    renderEditor();
                };

                document.querySelectorAll('[data-editor-section]').forEach((button) => {
                    button.addEventListener('click', () => openSectionEditor(button.dataset.editorSection));
                });

                document.querySelectorAll('[data-editor-block]').forEach((button) => {
                    button.addEventListener('click', () => openBlockEditor(button.dataset.editorBlock));
                });

                saveButton?.addEventListener('click', async () => {
                    if (!currentEditor) {
                        return;
                    }

                    const isSection = currentEditor.mode === 'section';
                    const url = isSection
                        ? `${'{{ $editorSectionBaseUrl }}'}/${currentEditor.key.replace('_', '-')}`
                        : `${'{{ $editorBlockBaseUrl }}'}/${currentEditor.key}`;

                    try {
                        saveButton.disabled = true;
                        const result = await requestJson(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                            body: JSON.stringify({ data: currentEditor.data }),
                        });

                        if (!result.ok) {
                            throw new Error(result.message || 'Save failed.');
                        }

                        if (isSection) {
                            state[currentEditor.key] = clone(currentEditor.data);
                        } else {
                            const index = state.homepage_blocks.findIndex((item) => item.id === currentEditor.key);
                            if (index !== -1) {
                                state.homepage_blocks[index] = clone(currentEditor.data);
                            }
                        }

                        showToast('Changes saved. Reloading...');
                        window.location.reload();
                    } catch (error) {
                        showToast(error.message || 'Save failed.', true);
                    } finally {
                        saveButton.disabled = false;
                    }
                });

                const sortList = document.getElementById('store-block-sort-list');

                if (sortList && window.Sortable) {
                    Sortable.create(sortList, {
                        animation: 180,
                        handle: '.store-sort-handle',
                        onEnd: async () => {
                            const order = Array.from(sortList.querySelectorAll('[data-block-id]')).map((item) => item.dataset.blockId);

                            try {
                                const result = await requestJson('{{ $editorReorderUrl }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrf,
                                    },
                                    body: JSON.stringify({ order }),
                                });

                                if (!result.ok) {
                                    throw new Error(result.message || 'Reorder failed.');
                                }

                                showToast('Homepage order saved. Reloading...');
                                window.location.reload();
                            } catch (error) {
                                showToast(error.message || 'Reorder failed.', true);
                            }
                        },
                    });
                }
            })();
        </script>
    @endif
    <script>
        (() => {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            const root = document.documentElement;
            const body = document.body;
            const isLiteMotion = (window.matchMedia('(max-width: 1024px)').matches && !window.matchMedia('(min-width: 1025px)').matches)
                || window.matchMedia('(hover: none)').matches
                || ((navigator.hardwareConcurrency || 4) <= 4);
            let frame = null;
            let targetX = 0.5;
            let targetY = 0.5;

            if (isLiteMotion) {
                body.classList.add('store-motion-lite');
            }

            const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

            const writeMotion = (x, y) => {
                const motionScale = isLiteMotion ? 0.42 : 1;
                const shiftX = (x - 0.5) * 48 * motionScale;
                const shiftY = (y - 0.5) * 34 * motionScale;
                const tiltX = (0.5 - y) * 4.5 * motionScale;
                const tiltY = (x - 0.5) * 7 * motionScale;

                root.style.setProperty('--pointer-x', `${(x * 100).toFixed(2)}%`);
                root.style.setProperty('--pointer-y', `${(y * 100).toFixed(2)}%`);
                root.style.setProperty('--scene-shift-x', `${(shiftX * 1.4).toFixed(2)}px`);
                root.style.setProperty('--scene-shift-y', `${(shiftY * 1.2).toFixed(2)}px`);
                root.style.setProperty('--hero-shift-x', `${shiftX.toFixed(2)}px`);
                root.style.setProperty('--hero-shift-y', `${shiftY.toFixed(2)}px`);
                root.style.setProperty('--hero-tilt-x', `${tiltX.toFixed(2)}deg`);
                root.style.setProperty('--hero-tilt-y', `${tiltY.toFixed(2)}deg`);
            };

            const updateFromEvent = (event) => {
                targetX = clamp(event.clientX / window.innerWidth, 0, 1);
                targetY = clamp(event.clientY / window.innerHeight, 0, 1);

                if (frame) {
                    return;
                }

                frame = window.requestAnimationFrame(() => {
                    writeMotion(targetX, targetY);
                    frame = null;
                });
            };

            window.addEventListener('pointermove', updateFromEvent, { passive: true });
            window.addEventListener('pointerleave', () => writeMotion(0.5, 0.5));
            window.addEventListener('blur', () => writeMotion(0.5, 0.5));

            writeMotion(0.5, 0.5);
        })();
    </script>
</body>
</html>
