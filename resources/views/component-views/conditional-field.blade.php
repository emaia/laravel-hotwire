@aware(['formState' => null])

@php
    $resolvedMatches = $state !== null ? $matches : $matchesWith($formState);

    $conditionalFieldAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(array_merge([
        'data-slot' => 'conditional-field',
        'data-conditional-fields-target' => 'dependent',
        'hidden' => $resolvedMatches ? null : true,
        'disabled' => $resolvedMatches ? null : true,
    ], collect($dataWhenAttributes())
        ->mapWithKeys(fn ($a) => [$a['attribute'] => $a['value']])
        ->all()), $attributes, $stimulus, protectedPrefixes: ['data-conditional-fields-']);
@endphp
<{!! $tag !!} {{ $conditionalFieldAttributes }}>
{{ $slot }}
</{!! $tag !!}>
