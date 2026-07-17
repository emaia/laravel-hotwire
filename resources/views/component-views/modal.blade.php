@php
    $modalAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $id,
        'data-slot' => 'modal',
        'data-controller' => 'modal',
        'data-modal-hidden-class' => 'pointer-events-none',
        'data-modal-visible-class' => 'pointer-events-auto',
        'data-modal-backdrop-hidden-class' => 'opacity-0',
        'data-modal-backdrop-visible-class' => 'opacity-100',
        'data-modal-dialog-hidden-class' => $dialogHiddenClass(),
        'data-modal-dialog-visible-class' => $dialogVisibleClass(),
        'data-modal-lock-scroll-class' => 'overflow-hidden',
        'data-action' => 'turbo:before-cache@window->modal#close',
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
