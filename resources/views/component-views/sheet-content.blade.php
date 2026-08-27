@aware(['sheetId' => null, 'sheetSide' => 'right', 'sheetBackdrop' => true, 'sheetFrame' => null, 'sheetMotion' => 'default', 'sheetViewTransition' => false, 'sheetOverlayLabelContext' => null])

@php
    if ($sheetId === null) {
        throw new InvalidArgumentException('Sheet content must be rendered inside a Sheet root.');
    }
@endphp

<div
    data-slot="sheet-overlay"
    data-sheet-target="modal"
    data-state="closed"
    data-motion="{{ $sheetMotion }}"
    role="dialog"
    aria-modal="true"
    @if ($sheetOverlayLabelContext?->titleId() !== null) aria-labelledby="{{ $sheetOverlayLabelContext->titleId() }}" @endif
    @if ($sheetOverlayLabelContext?->descriptionId() !== null) aria-describedby="{{ $sheetOverlayLabelContext->descriptionId() }}" @endif
    hidden
    inert
>
    @if ($sheetBackdrop)
        <div
            data-slot="sheet-backdrop"
            data-sheet-target="backdrop"
            data-action="click->sheet#clickOutside"
        ></div>
    @endif

    <div
        data-slot="sheet-content"
        data-side="{{ $sheetSide }}"
        data-sheet-target="dialog"
        {{ $attributes }}
    >
        @if ($sheetFrame !== null)
            <x-hw::frame
                :id="$sheetFrame"
                :view-transition="$sheetViewTransition"
                :data-turbo--view-transition-skip-initial-value="$sheetViewTransition ? 'true' : null"
                data-sheet-target="dynamicContent"
                data-sheet-frame-owner="{{ $sheetId }}"
            >
                {{ $slot }}
            </x-hw::frame>
        @else
            {{ $slot }}
        @endif

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
