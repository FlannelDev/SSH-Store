@php($settings = $block['settings'])

<section class="store-section fade-up rounded-[2rem] border border-[#1a1a20] bg-[linear-gradient(135deg,rgba(12,12,15,0.96),rgba(10,18,20,0.96))] p-8 shadow-[0_24px_80px_rgba(0,0,0,0.34)] sm:p-10 store-edit-target" data-homepage-block="{{ $block['id'] }}">
    @if($isStoreAdmin)
        <div class="store-edit-toolbar">
            <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
            <button type="button" class="store-edit-link" data-editor-section="background">Edit Background</button>
        </div>
    @endif
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="section-kicker">{{ $settings['kicker'] ?? '' }}</div>
            <h2 class="genesis-section-title mt-2 font-bold text-white">{{ $settings['title'] ?? '' }}</h2>
            <p class="mt-4 max-w-2xl text-base leading-8 text-[#b0b0ba]">{{ $settings['body'] ?? '' }}</p>
        </div>
        <a href="{{ $settings['button_url'] ?? '/store/dedicated' }}" class="whitespace-nowrap rounded-full bg-[linear-gradient(90deg,#00e6b0_0%,#00a8cc_55%,#0088cc_100%)] px-7 py-3.5 text-sm font-bold text-[#050505] transition hover:brightness-110">
            {{ $settings['button_label'] ?? 'View Dedicated Servers ->' }}
        </a>
    </div>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(($settings['stats'] ?? []) as $stat)
            <div class="rounded-[1.5rem] border border-[#1a1a20] bg-[#101014] p-5">
                <div class="text-xs uppercase tracking-[0.2em] text-[#6b6b76]">{{ $stat['label'] ?? '' }}</div>
                <div class="mt-2 font-display text-3xl font-bold text-[#00e6b0]">{{ $stat['value'] ?? '' }}</div>
                <div class="mt-1 text-sm text-[#7f7f8b]">{{ $stat['body'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</section>