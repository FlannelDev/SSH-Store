@php($settings = $block['settings'])

@if(filled($settings['content'] ?? null))
    <section class="store-section fade-up store-edit-target" data-homepage-block="{{ $block['id'] }}">
        @if($isStoreAdmin)
            <div class="store-edit-toolbar">
                <button type="button" class="store-edit-link" data-editor-block="{{ $block['id'] }}">Edit Section</button>
                <button type="button" class="store-edit-link" data-editor-section="background">Edit Background</button>
            </div>
        @endif
        <div class="section-card rounded-[2rem] px-7 py-8 sm:px-10 sm:py-10">
            <div class="editor-content max-w-none text-base leading-8">{!! $settings['content'] !!}</div>
        </div>
    </section>
@endif