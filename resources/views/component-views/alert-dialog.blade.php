@php
    $stimulusAttributes = $stimulus instanceof \Illuminate\Contracts\Support\Arrayable ? $stimulus->toArray() : [];
    $stimulusController = $stimulusAttributes['data-controller'] ?? '';
    $stimulusAction = $stimulusAttributes['data-action'] ?? '';
    unset($stimulusAttributes['data-controller'], $stimulusAttributes['data-action']);
@endphp

<div
    data-slot="alert-dialog"
    id="{{ $id }}"
    data-controller="{{ trim('alert-dialog '.$stimulusController) }}"
    data-alert-dialog-open-duration-value="{{ $openDuration }}"
    data-alert-dialog-close-duration-value="{{ $closeDuration }}"
    data-alert-dialog-lock-scroll-value="{{ $lockScroll ? 'true' : 'false' }}"
    data-alert-dialog-close-on-click-outside-value="{{ $closeOnClickOutside ? 'true' : 'false' }}"
    data-alert-dialog-hidden-class="pointer-events-none"
    data-alert-dialog-visible-class="pointer-events-auto"
    data-alert-dialog-backdrop-hidden-class="opacity-0"
    data-alert-dialog-backdrop-visible-class="opacity-100"
    data-alert-dialog-dialog-hidden-class="scale-90 opacity-0"
    data-alert-dialog-dialog-visible-class="scale-100 opacity-100"
    data-alert-dialog-lock-scroll-class="overflow-hidden"
    data-action="{{ trim('turbo:before-cache@window->alert-dialog#cancel '.$stimulusAction) }}"
    {{ new \Illuminate\View\ComponentAttributeBag($stimulusAttributes) }}
    @if ($stimulus !== null && ! $stimulus instanceof \Illuminate\Contracts\Support\Arrayable) {!! $stimulus->toHtml() !!} @endif
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
                <hw:button
                    slot-name="alert-dialog-cancel"
                    type="button"
                    data-action="alert-dialog#cancel"
                    variant="{{ $cancelVariant }}"
                    class="{{ $cancelClass }}"
                >
                    {{ $cancelLabel }}
                </hw:button>
                <hw:button
                    slot-name="alert-dialog-action"
                    type="button"
                    data-action="alert-dialog#confirm"
                    variant="{{ $confirmVariant }}"
                    class="{{ $confirmClass }}"
                >
                    {{ $confirmLabel }}
                </hw:button>
            </div>
        </div>
    </div>
</div>
