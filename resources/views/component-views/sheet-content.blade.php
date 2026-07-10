@aware(['side' => 'right', 'backdrop' => true, 'sheetHiddenClass' => 'translate-x-full'])

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
        {{ $attributes->merge(['class' => $sheetHiddenClass]) }}
    >
        {{ $slot }}

        <button
            type="button"
            data-slot="sheet-close-icon"
            data-action="sheet#close"
            aria-label="Close sheet"
        >
            <hw:icon name="x" />
            <span class="sr-only">Close</span>
        </button>
    </div>
</div>
