@aware([
    'size' => 'md',
    'id' => '',
    'class' => '',
    'closeButton' => true,
    'fixedTop' => false,
    'frame' => null,
    'motion' => 'default',
    'viewTransition' => false,
])

@php
    $presetSizes = ['sm', 'md', 'lg', 'xl', 'full', 'auto'];
    $sizeStyle = in_array($size, $presetSizes, true) ? '' : "max-width: {$size};";
@endphp

<div
    data-slot="modal-overlay"
    data-state="closed"
    data-motion="{{ $motion }}"
    data-modal-target="modal"
    data-action="click->modal#clickOutside"
    role="dialog"
    aria-modal="true"
    hidden
    inert
>
    <div
        data-slot="modal-backdrop"
        data-modal-target="backdrop"
    ></div>

    <div
        data-slot="modal-positioner"
        data-size="{{ $size }}"
        data-fixed-top="{{ $fixedTop ? 'true' : 'false' }}"
        data-modal-target="dialog"
        @if ($sizeStyle !== '') style="{{ $sizeStyle }}" @endif
    >
        <div data-slot="modal-panel" data-size="{{ $size }}" @if ($class !== '') class="{{ $class }}" @endif>
            <div data-slot="modal-content" data-size="{{ $size }}" {{ $attributes }}>
                @if ($frame !== null)
                    <x-hw::frame
                        :id="$frame"
                        :view-transition="$viewTransition"
                        :data-turbo--view-transition-skip-initial-value="$viewTransition ? 'true' : null"
                        data-modal-target="dynamicContent"
                        data-modal-frame-owner="{{ $id }}"
                    >
                        {{ $slot }}
                    </x-hw::frame>
                @else
                    {{ $slot }}
                @endif
            </div>

            @if ($closeButton)
                <button
                    type="button"
                    data-slot="modal-close-icon"
                    data-modal-size="{{ $size }}"
                    data-action="click->modal#close"
                    aria-label="Close modal"
                >
                    <x-hw::icon name="x" />
                </button>
            @endif
        </div>
    </div>
</div>
