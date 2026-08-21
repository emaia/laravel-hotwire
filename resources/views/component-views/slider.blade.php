@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null])

@php
    $name = $name ?? $fieldName;
    $id = $id ?? $fieldId;
    $errorKey = $errorKey ?? $fieldErrorKey;
    extract($compute($name, $id, $errorKey, $errors ?? new \Illuminate\Support\ViewErrorBag));

    $sliderAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'slider',
        'data-orientation' => $orientation,
        'aria-orientation' => $orientation === 'vertical' ? 'vertical' : null,
        'type' => 'range',
        'id' => $resolvedId,
        'name' => $name ?: null,
        'value' => $resolvedValue,
        'min' => $min,
        'max' => $max,
        'step' => $step,
        'aria-describedby' => $errorId,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
        'data-controller' => 'slider',
        'data-action' => $elementAction,
        'data-auto-submit-delay-param' => $autoSubmitDelayParam,
        'class' => $class ?: null,
        'style' => trim("--slider-value: {$fillPercent}%; ".($attributes->get('style') ?? '')),
    ], $attributes, $stimulus, except: ['type', 'value', 'min', 'max', 'step', 'orientation', 'error-key', 'old', 'required', 'auto-submit', 'auto-submit-delay', 'style'], protectedPrefixes: $internalPrefixes);
@endphp

<input {{ $sliderAttributes }} />
