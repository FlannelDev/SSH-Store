@php($settings = $block['settings'])

<section class="store-section fade-up grid gap-4 md:grid-cols-4 store-edit-target" data-homepage-block="{{ $block['id'] }}">
    @if($isStoreAdmin)
        <div class="store-edit-toolbar">
            <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
        </div>
    @endif
    @foreach(($settings['cards'] ?? []) as $card)
        <div class="feature-tile rounded-[1.5rem] p-5 {{ $loop->last ? 'md:col-span-2 lg:col-span-1' : '' }}">
            <div class="section-kicker">{{ $card['kicker'] ?? '' }}</div>
            <div class="mt-3 font-display text-3xl font-bold text-white">{{ $card['title'] ?? '' }}</div>
            <p class="mt-2 text-sm leading-7 text-[#7f7f8b]">{{ $card['body'] ?? '' }}</p>
        </div>
    @endforeach
</section>