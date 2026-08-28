@aware([
    'modalId' => null,
    'modalSize' => 'md',
    'modalClass' => '',
    'modalCloseButton' => true,
    'modalFixedTop' => false,
    'modalFrame' => null,
    'modalMotion' => 'default',
    'modalViewTransition' => false,
    'modalAriaLabel' => null,
    'modalAriaLabelledby' => null,
    'modalAriaDescription' => null,
    'modalAriaDescribedby' => null,
    'modalOverlayLabelContext' => null,
])

@php
    if ($modalId === null) {
        throw new InvalidArgumentException('Modal content must be rendered inside a Modal root.');
    }

    $presetSizes = ['sm', 'md', 'lg', 'xl', 'full', 'auto'];
    $sizeStyle = in_array($modalSize, $presetSizes, true) ? '' : "max-width: {$modalSize};";
    $modalLabelReferences = $modalOverlayLabelContext?->resolveReferences($slot) ?? ['title' => null, 'description' => null];
    $modalManagedTitle = $modalAriaLabel === null && $modalAriaLabelledby === null ? $modalLabelReferences['title'] : null;
    $modalManagedDescription = $modalAriaDescription === null && $modalAriaDescribedby === null ? $modalLabelReferences['description'] : null;
    $modalLabelledby = $modalAriaLabelledby ?? $modalManagedTitle;
    $modalDescribedby = $modalAriaDescribedby ?? $modalManagedDescription;
@endphp

<div
    data-slot="modal-overlay"
    data-state="closed"
    data-motion="{{ $modalMotion }}"
    data-modal-target="modal"
    data-action="click->modal#clickOutside"
    data-hotwire-overlay-labels
    @if ($modalManagedTitle !== null) data-hotwire-overlay-labelledby="{{ $modalManagedTitle }}" @endif
    @if ($modalManagedDescription !== null) data-hotwire-overlay-describedby="{{ $modalManagedDescription }}" @endif
    role="dialog"
    aria-modal="true"
    @if ($modalAriaLabel !== null) aria-label="{{ $modalAriaLabel }}" @endif
    @if ($modalLabelledby !== null) aria-labelledby="{{ $modalLabelledby }}" @endif
    @if ($modalAriaDescription !== null) aria-description="{{ $modalAriaDescription }}" @endif
    @if ($modalDescribedby !== null) aria-describedby="{{ $modalDescribedby }}" @endif
    hidden
    inert
>
    <div
        data-slot="modal-backdrop"
        data-modal-target="backdrop"
    ></div>

    <div
        data-slot="modal-positioner"
        data-size="{{ $modalSize }}"
        data-fixed-top="{{ $modalFixedTop ? 'true' : 'false' }}"
        data-modal-target="dialog"
        @if ($sizeStyle !== '') style="{{ $sizeStyle }}" @endif
    >
        <div data-slot="modal-panel" data-size="{{ $modalSize }}" @if ($modalClass !== '') class="{{ $modalClass }}" @endif>
            <div data-slot="modal-content" data-size="{{ $modalSize }}" {{ $attributes }}>
                @if ($modalFrame !== null)
                    <x-hw::frame
                        :id="$modalFrame"
                        :view-transition="$modalViewTransition"
                        :data-turbo--view-transition-skip-initial-value="$modalViewTransition ? 'true' : null"
                        data-modal-target="dynamicContent"
                        data-modal-frame-owner="{{ $modalId }}"
                    >
                        {{ $slot }}
                    </x-hw::frame>
                @else
                    {{ $slot }}
                @endif
            </div>

            @if ($modalCloseButton)
                <button
                    type="button"
                    data-slot="modal-close-icon"
                    data-modal-size="{{ $modalSize }}"
                    data-action="click->modal#close"
                    aria-label="Close modal"
                >
                    <x-hw::icon name="x" />
                </button>
            @endif
        </div>
    </div>
</div>
