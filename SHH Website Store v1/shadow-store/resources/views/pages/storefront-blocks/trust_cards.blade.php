@php($settings = $block['settings'])

<section class="store-section fade-up grid gap-6 lg:grid-cols-3 store-edit-target" data-homepage-block="{{ $block['id'] }}">
    @if($isStoreAdmin)
        <div class="store-edit-toolbar">
            <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
        </div>
    @endif
    @foreach(($settings['cards'] ?? []) as $card)
        <div class="section-card rounded-[1.75rem] p-6">
            <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/16 bg-cyan-300/10 text-cyan-200">
                @if(($card['icon'] ?? '') === 'shield')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                @elseif(($card['icon'] ?? '') === 'star')
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                @else
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                @endif
            </div>
            <h3 class="text-2xl font-bold text-white">{{ $card['title'] ?? '' }}</h3>
            <p class="mt-3 text-sm leading-7 text-slate-400">{{ $card['body'] ?? '' }}</p>
        </div>
    @endforeach
</section>