@aware([
    'size' => 'md',
    'class' => '',
    'closeButton' => true,
    'fixedTop' => false,
    'frame' => null,
])

@php
    $presetSizes = ['sm', 'md', 'lg', 'xl', 'full', 'auto'];
    $sizeStyle = in_array($size, $presetSizes, true) ? '' : "max-width: {$size};";
@endphp

<div
    data-slot="modal-overlay"
    data-open="false"
    data-modal-target="modal"
    data-action="click->modal#clickOutside"
    role="dialog"
    aria-modal="true"
    hidden
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
                    <turbo-frame id="{{ $frame }}" data-modal-target="dynamicContent">
                        {{ $slot }}
                    </turbo-frame>
                @else
                    {{ $slot }}
                @endif
            </div>

            @if ($closeButton)
                <button
                    type="button"
                    data-slot="modal-close-icon"
                    data-modal-size="{{ $size }}"
                    data-action="modal#close"
                    aria-label="Close modal"
                >
                    <x-hw::icon name="x" />
                </button>
            @endif
        </div>
    </div>
</div>
