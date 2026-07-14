@php
    extract($compute($attributes));
@endphp

<div {{ $sheetAttributes }}>
    {{ $slot }}

    @if ($frame !== null && trim($slot->toHtml()) === '')
        <div
            data-slot="sheet-overlay"
            data-sheet-target="modal"
            data-open="false"
            role="dialog"
            aria-modal="true"
            hidden
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
                class="{{ $sheetHiddenClass }}"
            >
                <turbo-frame id="{{ $frame }}" data-sheet-target="dynamicContent"></turbo-frame>

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
