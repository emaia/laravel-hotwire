@php
    $modalAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $modalId,
        'data-slot' => 'modal',
        'data-controller' => 'modal',
        'data-modal-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->modal#closeForCache',
    ], $attributes, $modalStimulus, protectedPrefixes: ['data-modal-']);
    $modalOverlayLabelContext->validateRoot($slot);
    $frameHostCount = $modalFrame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $modalFrame,
        'data-modal-frame-owner',
        $modalId,
        'modal.content',
    );
@endphp

<div
    {{ $modalAttributes }}
>
    {{ $slot }}

    @if ($modalFrame !== null && $frameHostCount === 0)
        <x-hw::modal.content />
    @endif

    @if (isset($loading_template))
        <template data-modal-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>
