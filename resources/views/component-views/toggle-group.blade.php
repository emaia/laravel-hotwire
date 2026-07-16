@php
    extract($compute($attributes));

    $groupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'role' => 'group',
        'data-slot' => 'toggle-group',
        'data-controller' => $elementController,
        'data-action' => $elementAction,
        'data-toggle-group-type-value' => $type,
        'data-orientation' => $orientation,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-connected' => $isConnected ? 'true' : null,
        'aria-orientation' => $orientation,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-disabled' => $isDisabled ? 'true' : null,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
    ], $attributes, $stimulus, except: ['type', 'value', 'variant', 'size', 'orientation', 'disabled', 'connected', 'old', 'name', 'id', 'error-key', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);
@endphp

<div {{ $groupAttributes }}>{{ $slot }}</div>
