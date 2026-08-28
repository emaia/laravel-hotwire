@php
    $alertDialogAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'alert-dialog',
        'data-controller' => 'alert-dialog',
        'data-alert-dialog-lock-scroll-value' => $lockScroll ? 'true' : 'false',
        'data-alert-dialog-close-on-click-outside-value' => $closeOnClickOutside ? 'true' : 'false',
        'data-alert-dialog-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->alert-dialog#closeForCache',
    ], $attributes, $stimulus, protectedPrefixes: ['data-alert-dialog-']);
    $alertDialogOverlayLabelContext->assertNoIdCollisions($slot);
    if ($alertDialogOverlayLabelContext->hasRegisteredLabels($slot)) {
        throw new InvalidArgumentException('Alert Dialog title and description subcomponents must be rendered in the content slot.');
    }
    if (isset($content)) {
        $alertDialogOverlayLabelContext->assertNoIdCollisions($content);
    }
    $contentLabelReferences = isset($content) && ($title === '' || $description === '')
        ? $alertDialogOverlayLabelContext->referencesFor($content)
        : ['title' => null, 'description' => null];
    $alertDialogTitleId = $title !== ''
        ? $alertDialogOverlayLabelContext->titleId()
        : $contentLabelReferences['title'];
    $alertDialogDescriptionId = $description !== ''
        ? $alertDialogOverlayLabelContext->descriptionId()
        : $contentLabelReferences['description'];
@endphp

<div
    {{ $alertDialogAttributes }}
>
    <div data-slot="alert-dialog-trigger" data-action="click->alert-dialog#intercept">
        {{ $slot }}
    </div>

    <div
        data-slot="alert-dialog-overlay"
        data-state="closed"
        data-motion="{{ $motion }}"
        data-alert-dialog-target="modal"
        data-action="click->alert-dialog#clickOutside"
        role="alertdialog"
        aria-modal="true"
        @if ($alertDialogTitleId !== null) aria-labelledby="{{ $alertDialogTitleId }}" @endif
        @if ($alertDialogDescriptionId !== null) aria-describedby="{{ $alertDialogDescriptionId }}" @endif
        hidden
        inert
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
                @if ($title !== '')
                    <h2 id="{{ $alertDialogOverlayLabelContext->titleId() }}" data-slot="alert-dialog-title">{{ $title }}</h2>
                @endif

                @if ($description !== '')
                    <p id="{{ $alertDialogOverlayLabelContext->descriptionId() }}" data-slot="alert-dialog-description" style="text-wrap-mode: wrap">{{ $description }}</p>
                @endif

                @isset($content)
                    {{ $content }}
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
