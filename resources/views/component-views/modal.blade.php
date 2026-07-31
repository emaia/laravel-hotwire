@php
    $modalAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'modal',
        'data-controller' => 'modal',
        'data-modal-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->modal#closeForCache',
    ], $attributes, $stimulus, protectedPrefixes: ['data-modal-']);
    $frameHostCount = $frame === null ? 0 : \Emaia\LaravelHotwire\Support\OverlayFrameHost::count(
        $slot->toHtml(),
        $frame,
        'data-modal-frame-owner',
        $id,
        'modal.content',
    );
@endphp

<div
    {{ $modalAttributes }}
>
    {{ $slot }}

    @if ($frame !== null && $frameHostCount === 0)
        <x-hw::modal.content />
    @endif

    @if (isset($loading_template))
        <template data-modal-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>
