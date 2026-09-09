@php
    $alertDialogShared ??= false;
    $alertDialogAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => $slotName,
        'data-controller' => 'alert-dialog',
        'data-alert-dialog-lock-scroll-value' => $lockScroll ? 'true' : 'false',
        'data-alert-dialog-close-on-click-outside-value' => $closeOnClickOutside ? 'true' : 'false',
        'data-alert-dialog-initial-focus-value' => $initialFocus,
        'data-alert-dialog-shared-value' => $alertDialogShared ? 'true' : null,
        'data-alert-dialog-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->alert-dialog#closeForCache',
    ], $attributes, $stimulus, protectedPrefixes: ['data-alert-dialog-']);
    $alertDialogOverlayLabelContext->assertNoIdCollisions($slot, 'trigger slot');
    if ($alertDialogOverlayLabelContext->hasRegisteredLabels($slot)) {
        throw new InvalidArgumentException('Alert Dialog title and description subcomponents must be rendered in the content slot.');
    }
    $contentLabelReferences = isset($content)
        ? $alertDialogOverlayLabelContext->resolveReferences($content)
        : ['title' => null, 'description' => null];
    if ($alertDialogShared && ($contentLabelReferences['title'] !== null || $contentLabelReferences['description'] !== null)) {
        throw new InvalidArgumentException('Shared Alert Dialog labels must use the host or trigger text props.');
    }
    $alertDialogTitleId = $alertDialogShared
        ? $alertDialogOverlayLabelContext->titleId()
        : ($title !== ''
        ? $alertDialogOverlayLabelContext->titleId()
        : $contentLabelReferences['title']);
    $alertDialogDescriptionId = $alertDialogShared
        ? $alertDialogOverlayLabelContext->descriptionId()
        : ($description !== ''
        ? $alertDialogOverlayLabelContext->descriptionId()
        : $contentLabelReferences['description']);
@endphp

<div
    {{ $alertDialogAttributes }}
>
    <div data-slot="{{ $triggerSlotName }}" data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
        {{ $slot }}
    </div>

    <div
        data-slot="{{ $overlaySlotName }}"
        data-state="closed"
        data-motion="{{ $motion }}"
        data-alert-dialog-target="modal"
        data-action="click->alert-dialog#clickOutside"
        role="alertdialog"
        aria-modal="true"
        tabindex="-1"
        @if ($alertDialogTitleId !== null) aria-labelledby="{{ $alertDialogTitleId }}" @endif
        @if ($alertDialogDescriptionId !== null) aria-describedby="{{ $alertDialogDescriptionId }}" @endif
        hidden
        inert
    >
        <div
            data-slot="{{ $backdropSlotName }}"
            data-alert-dialog-target="backdrop"
        ></div>

        <div
            data-slot="{{ $panelSlotName }}"
            data-alert-dialog-target="dialog"
        >
            <div data-slot="{{ $headerSlotName }}">
                @if ($alertDialogShared || $title !== '')
                    <h2
                        id="{{ $alertDialogOverlayLabelContext->titleId() }}"
                        data-slot="{{ $titleSlotName }}"
                        @if ($alertDialogShared) data-alert-dialog-target="title" @endif
                        @if ($alertDialogShared && $title === '') hidden @endif
                    >{{ $title }}</h2>
                @endif

                @if ($alertDialogShared || $description !== '')
                    <p
                        id="{{ $alertDialogOverlayLabelContext->descriptionId() }}"
                        data-slot="{{ $descriptionSlotName }}"
                        @if ($alertDialogShared) data-alert-dialog-target="description" @endif
                        @if ($alertDialogShared && $description === '') hidden @endif
                        style="text-wrap-mode: wrap"
                    >{{ $description }}</p>
                @endif

                @isset($content)
                    {{ $content }}
                @endisset
            </div>

            <div data-slot="{{ $footerSlotName }}">
                <x-hw::button
                    :slot-name="$cancelSlotName"
                    type="button"
                    data-action="alert-dialog#cancel"
                    data-alert-dialog-target="cancel"
                    variant="{{ $cancelVariant }}"
                    class="{{ $cancelClass }}"
                >
                    {{ $cancelLabel }}
                </x-hw::button>
                <x-hw::button
                    :slot-name="$actionSlotName"
                    type="button"
                    data-action="alert-dialog#confirm"
                    data-alert-dialog-target="confirm"
                    variant="{{ $confirmVariant }}"
                    class="{{ $confirmClass }}"
                >
                    {{ $confirmLabel }}
                </x-hw::button>
            </div>
        </div>
    </div>
</div>
