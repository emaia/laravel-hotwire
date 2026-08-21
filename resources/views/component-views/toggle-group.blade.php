@php
    extract($compute($attributes));

    $groupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'role' => 'group',
        'data-slot' => 'toggle-group',
        'data-controller' => $elementController,
        'data-action' => $elementAction,
        'data-toggle-group-type-value' => $toggleGroupType,
        'data-orientation' => $toggleGroupOrientation,
        'data-variant' => $toggleGroupVariant,
        'data-size' => $toggleGroupSize,
        'data-connected' => $isConnected ? 'true' : null,
        'aria-orientation' => $toggleGroupOrientation,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-disabled' => $isDisabled ? 'true' : null,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
    ], $attributes, $toggleGroupStimulus, except: ['type', 'value', 'variant', 'size', 'orientation', 'disabled', 'connected', 'old', 'name', 'id', 'error-key', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: $internalPrefixes);
@endphp

<div {{ $groupAttributes }}>{{ $slot }}</div>
