@php
    extract($compute($attributes));
    $frameHostCount = $frame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $frame,
        'data-sheet-frame-owner',
        $id,
        'sheet.content',
    );
@endphp

<div {{ $sheetAttributes }}>
    {{ $slot }}

    @if ($frame !== null && $frameHostCount === 0)
        <div
            data-slot="sheet-overlay"
            data-sheet-target="modal"
            data-state="closed"
            data-motion="{{ $motion }}"
            role="dialog"
            aria-modal="true"
            hidden
            inert
        >
            @if ($backdrop)
                <div
                    data-slot="sheet-backdrop"
                    data-sheet-target="backdrop"
                    data-action="click->sheet#clickOutside"
                ></div>
            @endif

            <div
                data-slot="sheet-content"
                data-side="{{ $side }}"
                data-sheet-target="dialog"
            >
                <turbo-frame id="{{ $frame }}" data-sheet-target="dynamicContent" data-sheet-frame-owner="{{ $id }}"></turbo-frame>

                <button
                    type="button"
                    data-slot="sheet-close-icon"
                    data-action="sheet#close"
                    aria-label="Close sheet"
                >
                    <x-hw::icon name="x" />
                </button>
            </div>
        </div>
    @endif

    @if (isset($loading_template))
        <template data-sheet-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>
