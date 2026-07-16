@php
    $resolvedName = $name ?? null;
    extract($compute($resolvedName, $attributes));

    $userClasses = preg_split('/\s+/', trim((string) $attributes->get('class', ''))) ?: [];
    $toggleGroupClass = in_array('group/toggle', $userClasses, true) ? null : 'group/toggle';

    $toggleAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => $type,
        'data-slot' => 'toggle',
        'data-controller' => 'toggle',
        'data-action' => $elementAction,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-state' => $state,
        'data-disabled' => $isDisabled ? 'true' : null,
        'aria-pressed' => $isPressed ? 'true' : 'false',
        'data-toggle-pressed-value' => $isPressed ? 'true' : 'false',
        'data-toggle-value-value' => $htmlValue,
        'data-toggle-input-id-value' => $inputId,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $toggleGroupClass,
    ], $attributes, $stimulus, except: ['name', 'value', 'pressed', 'variant', 'size', 'type', 'auto-submit', 'auto-submit-delay'], protectedPrefixes: array_values(array_filter([
        'data-toggle-',
        \Emaia\LaravelHotwire\Support\AutoSubmit::enabled($autoSubmit) ? 'data-auto-submit-' : null,
    ])));
@endphp

@if ($resolvedName)
    <input
        id="{{ $inputId }}"
        data-toggle-input
        type="hidden"
        name="{{ $resolvedName }}"
        value="{{ $htmlValue }}"
        @if ($hiddenDisabled) disabled @endif
    />
@endif

<button
    {{ $toggleAttributes }}
>{{ $slot }}</button>
