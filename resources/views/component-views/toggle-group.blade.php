@aware(['fieldName' => null, 'fieldErrorKey' => null])

@php
    extract($compute($attributes));

    $explicitName = $toggleGroupName ?? null;
    $resolvedName = $explicitName ?? $fieldName;
    $resolvedErrorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($toggleGroupErrorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $labelId = $fieldOwnerContext->labelId();
    $fieldOwnsSet = $toggleGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext
        && $toggleGroupFieldContext->ownsSet();
    $hasExplicitAccessibleName = $attributes->has('aria-labelledby') || $attributes->has('aria-label');

    if ($toggleGroupFieldContext instanceof \Emaia\LaravelHotwire\Support\FieldContext) {
        $labelId = $toggleGroupFieldContext->registerSelection(
            $labelId,
            $hasExplicitAccessibleName,
            $resolvedName,
            $fieldOwnerId,
            $resolvedErrorKey,
        );
    }

    $groupAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'role' => $fieldOwnsSet ? null : 'group',
        'aria-labelledby' => $fieldOwnsSet || $hasExplicitAccessibleName ? null : $labelId,
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
