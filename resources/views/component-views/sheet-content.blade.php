@aware(['side' => 'right', 'backdrop' => true, 'frame' => null, 'motion' => 'default'])

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
        {{ $attributes }}
    >
        @if ($frame !== null)
            <turbo-frame id="{{ $frame }}" data-sheet-target="dynamicContent">
                {{ $slot }}
            </turbo-frame>
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
