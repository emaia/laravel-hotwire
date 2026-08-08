@php
    $bool = fn (bool $v) => $v ? 'true' : 'false';

    $toasterAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'toaster',
        'id' => $id,
        'data-turbo-permanent' => $turboPermanent ? true : null,
        'class' => $class !== '' ? $class : null,
        'data-controller' => 'toaster',
        'data-toaster-position-value' => $position,
        'data-toaster-duration-value' => $duration,
        'data-toaster-visible-toasts-value' => $visibleToasts,
        'data-toaster-close-button-value' => $bool($closeButton),
        'data-toaster-expand-value' => $bool($expand),
        'data-toaster-auto-disconnect-value' => $bool($autoDisconnect),
        'data-toaster-class-name-value' => $className,
        'data-toaster-container-aria-label-value' => $containerAriaLabel,
    ], $attributes, $stimulus, protectedPrefixes: ['data-toaster-']);
@endphp
<div
    {{ $toasterAttributes }}
></div>
