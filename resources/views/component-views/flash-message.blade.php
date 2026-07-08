@php
    $flashMessageAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'flash-message',
        'data-turbo-temporary' => true,
        'data-controller' => 'toast',
        'data-toast-message-value' => $finalMessage,
        'data-toast-type-value' => $finalType,
        'data-toast-description-value' => $description,
        'data-toast-position-value' => $position,
        'data-toast-class-name-value' => $className,
    ], $attributes, $stimulus, protectedPrefixes: ['data-toast-']);
@endphp

<div
    {{ $flashMessageAttributes }}
></div>
