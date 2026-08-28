@aware([
    'sheetId' => null,
    'sheetSide' => 'right',
    'sheetBackdrop' => true,
    'sheetFrame' => null,
    'sheetMotion' => 'default',
    'sheetViewTransition' => false,
    'sheetAriaLabel' => null,
    'sheetAriaLabelledby' => null,
    'sheetAriaDescription' => null,
    'sheetAriaDescribedby' => null,
    'sheetOverlayLabelContext' => null,
])

@php
    if ($sheetId === null) {
        throw new InvalidArgumentException('Sheet content must be rendered inside a Sheet root.');
    }

    $sheetLabelReferences = $sheetOverlayLabelContext?->resolveReferences($slot) ?? ['title' => null, 'description' => null];
    $sheetManagedTitle = $sheetAriaLabel === null && $sheetAriaLabelledby === null ? $sheetLabelReferences['title'] : null;
    $sheetManagedDescription = $sheetAriaDescription === null && $sheetAriaDescribedby === null ? $sheetLabelReferences['description'] : null;
    $sheetLabelledby = $sheetAriaLabelledby ?? $sheetManagedTitle;
    $sheetDescribedby = $sheetAriaDescribedby ?? $sheetManagedDescription;
@endphp

<div
    data-slot="sheet-overlay"
    data-sheet-target="modal"
    data-state="closed"
    data-motion="{{ $sheetMotion }}"
    data-hotwire-overlay-labels
    @if ($sheetManagedTitle !== null) data-hotwire-overlay-labelledby="{{ $sheetManagedTitle }}" @endif
    @if ($sheetManagedDescription !== null) data-hotwire-overlay-describedby="{{ $sheetManagedDescription }}" @endif
    role="dialog"
    aria-modal="true"
    @if ($sheetAriaLabel !== null) aria-label="{{ $sheetAriaLabel }}" @endif
    @if ($sheetLabelledby !== null) aria-labelledby="{{ $sheetLabelledby }}" @endif
    @if ($sheetAriaDescription !== null) aria-description="{{ $sheetAriaDescription }}" @endif
    @if ($sheetDescribedby !== null) aria-describedby="{{ $sheetDescribedby }}" @endif
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
