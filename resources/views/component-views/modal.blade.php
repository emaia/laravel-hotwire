@php
    $modalAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'modal',
        'data-controller' => 'modal',
        'data-modal-prevent-reopen-delay-value' => $preventReopenDelay,
        'data-modal-hidden-class' => 'pointer-events-none',
        'data-modal-visible-class' => 'pointer-events-auto',
        'data-modal-backdrop-hidden-class' => 'opacity-0',
        'data-modal-backdrop-visible-class' => 'opacity-100',
        'data-modal-dialog-hidden-class' => 'scale-80 opacity-0',
        'data-modal-dialog-visible-class' => 'scale-100 opacity-100',
        'data-modal-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->modal#close',
    ], $attributes, $stimulus, protectedPrefixes: ['data-modal-']);
@endphp

<div
    {{ $modalAttributes }}
>
    @if (isset($trigger))
        {{ $trigger }}
    @endif

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
            @if ($sizeStyle()) style="{{ $sizeStyle() }}" @endif
        >
            <div data-slot="modal-panel" data-size="{{ $size }}" @if ($class !== '') class="{{ $class }}" @endif>
                <div data-slot="modal-content" data-size="{{ $size }}">
                    @if ($frame !== null)
                        <turbo-frame id="{{ $frame }}" data-modal-target="dynamicContent">
                            {{ $slot }}
                        </turbo-frame>
                    @else
                        {{ $slot }}
                    @endif
                </div>

                @if ($closeButton)
                    <hw:button
                        slot-name="modal-close"
                        data-modal-size="{{ $size }}"
                        variant="ghost"
                        size="icon-sm"
                        data-action="modal#close"
                        type="button"
                        aria-label="Close modal"
                    >
                        <hw:icon name="x" />
                    </hw:button>
                @endif
            </div>

            @if (isset($loading_template))
                <template data-modal-target="loadingTemplate">
                    {{ $loading_template }}
                </template>
            @endif
        </div>
    </div>
</div>
