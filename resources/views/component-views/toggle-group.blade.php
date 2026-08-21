@aware(['fieldName' => null, 'fieldId' => null])

@php
    extract($compute($attributes));

    $resolvedName = $toggleGroupName ?? $fieldName;
    $baseId = $toggleGroupId ?? $fieldId ?? ($resolvedName ? \Emaia\LaravelHotwire\Support\FieldKey::toId($resolvedName) : null);
    $labelId = $fieldOwnerContext->labelId();
    $hasExplicitAccessibleName = $attributes->has('aria-label') || $attributes->has('aria-labelledby');

    if ($toggleGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext) {
        $labelId = $toggleGroupFieldContext->registerSelection($baseId, $resolvedName, $labelId, $hasExplicitAccessibleName);
    }

    $groupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'role' => 'group',
        'aria-labelledby' => $labelId,
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
