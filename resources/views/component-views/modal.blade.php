@php
    $modalAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'modal',
        'data-controller' => 'modal',
        'data-modal-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->modal#closeForCache',
    ], $attributes, $stimulus, protectedPrefixes: ['data-modal-']);
@endphp

<div
    {{ $modalAttributes }}
>
    {{ $slot }}

    @if ($frame !== null && trim($slot->toHtml()) === '')
        <x-hw::modal.content />
    @endif

    @if (isset($loading_template))
        <template data-modal-target="loadingTemplate">
            {{ $loading_template }}
        </template>
    @endif
</div>
