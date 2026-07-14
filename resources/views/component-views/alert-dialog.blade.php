@php
    $alertDialogAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'alert-dialog',
        'data-controller' => 'alert-dialog',
        'data-alert-dialog-open-duration-value' => $openDuration,
        'data-alert-dialog-close-duration-value' => $closeDuration,
        'data-alert-dialog-lock-scroll-value' => $lockScroll ? 'true' : 'false',
        'data-alert-dialog-close-on-click-outside-value' => $closeOnClickOutside ? 'true' : 'false',
        'data-alert-dialog-hidden-class' => 'pointer-events-none',
        'data-alert-dialog-visible-class' => 'pointer-events-auto',
        'data-alert-dialog-backdrop-hidden-class' => 'opacity-0',
        'data-alert-dialog-backdrop-visible-class' => 'opacity-100',
        'data-alert-dialog-dialog-hidden-class' => 'scale-90 opacity-0',
        'data-alert-dialog-dialog-visible-class' => 'scale-100 opacity-100',
        'data-alert-dialog-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->alert-dialog#cancel',
    ], $attributes, $stimulus, protectedPrefixes: ['data-alert-dialog-']);
@endphp

<div
    {{ $alertDialogAttributes }}
>
    <div data-slot="alert-dialog-trigger" data-action="click->alert-dialog#intercept">
        {{ $slot }}
    </div>

    <div
        data-slot="alert-dialog-overlay"
        data-open="false"
        data-alert-dialog-target="modal"
        data-action="click->alert-dialog#clickOutside"
        role="dialog"
        aria-modal="true"
        hidden
    >
        <div
            data-slot="alert-dialog-backdrop"
            data-alert-dialog-target="backdrop"
        ></div>

        <div
            data-slot="alert-dialog-panel"
            data-alert-dialog-target="dialog"
        >
            <div data-slot="alert-dialog-header">
                @if ($title)
                    <h2 data-slot="alert-dialog-title">{{ $title }}</h2>
                @endif

                @if ($message)
                    <p data-slot="alert-dialog-description" style="text-wrap-mode: wrap">{{ $message }}</p>
                @endif

                @isset($body)
                    {{ $body }}
                @endisset
            </div>

            <div data-slot="alert-dialog-footer">
                <x-hw::button
                    slot-name="alert-dialog-cancel"
                    type="button"
                    data-action="alert-dialog#cancel"
                    variant="{{ $cancelVariant }}"
                    class="{{ $cancelClass }}"
                >
                    {{ $cancelLabel }}
                </x-hw::button>
                <x-hw::button
                    slot-name="alert-dialog-action"
                    type="button"
                    data-action="alert-dialog#confirm"
                    variant="{{ $confirmVariant }}"
                    class="{{ $confirmClass }}"
                >
                    {{ $confirmLabel }}
                </x-hw::button>
            </div>
        </div>
    </div>
</div>
