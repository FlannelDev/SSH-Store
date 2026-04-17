@php
    $sizeClass = $sizeClass ?? 'h-10 w-10';
    $containerClass = $containerClass ?? 'rounded-xl border border-cyan-300/16 bg-white/6 p-1';
    $imageClass = $imageClass ?? 'h-full w-full object-contain';
    $textClass = $textClass ?? 'font-display text-sm font-bold text-cyan-100';
    $logoWrapperClass = $logoWrapperClass ?? 'h-10 w-auto max-w-[8rem] shrink-0';
    $logoContainerClass = $logoContainerClass ?? '';
    $logoImageClass = $logoImageClass ?? 'block h-full w-auto max-w-full object-contain';
    $logoUrl = $storeHeader['resolved_logo_url'] ?? null;
    $altText = trim(($storeHeader['brand_name'] ?? 'Store') . ' logo');
@endphp

@if(filled($logoUrl))
    <div class="{{ $logoWrapperClass }} {{ $logoContainerClass }} flex items-center justify-start overflow-visible">
        <img src="{{ $logoUrl }}" alt="{{ $altText }}" class="{{ $logoImageClass }}">
    </div>
@else
    <div class="{{ $sizeClass }} {{ $containerClass }} flex items-center justify-center overflow-hidden">
        <span class="{{ $textClass }}">{{ $storeHeader['badge_text'] }}</span>
    </div>
@endif