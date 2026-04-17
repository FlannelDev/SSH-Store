@php($settings = $block['settings'])

<section class="store-section fade-up flex flex-wrap items-center justify-between gap-4 rounded-[1.75rem] border border-white/6 bg-white/[0.03] px-5 py-4 store-edit-target" data-homepage-block="{{ $block['id'] }}">
    @if($isStoreAdmin)
        <div class="store-edit-toolbar">
            <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
        </div>
    @endif
    <div>
        <div class="section-kicker">{{ $settings['kicker'] ?? '' }}</div>
        <p class="mt-2 text-sm text-slate-300">{{ $settings['body'] ?? '' }}</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="{{ $settings['primary_url'] ?? '/store' }}" class="category-pill rounded-full bg-cyan-400/14 px-5 py-3 text-sm font-semibold text-white">{{ $settings['primary_label'] ?? 'Game Servers' }}</a>
        <a href="{{ $settings['secondary_url'] ?? '/store/dedicated' }}" class="category-pill rounded-full px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-cyan-300/30 hover:text-white">{{ $settings['secondary_label'] ?? 'Dedicated Machines' }}</a>
    </div>
</section>