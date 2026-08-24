@aware(['conditionalFieldState' => null])

@php
    $resolvedMatches = $state !== null ? $matches : $matchesWith($conditionalFieldState);

    $conditionalFieldAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(array_merge([
        'data-slot' => 'conditional-field',
        'data-conditional-fields-target' => 'dependent',
        'hidden' => $resolvedMatches ? null : true,
        'disabled' => $resolvedMatches ? null : true,
    ], collect($dataWhenAttributes())
        ->mapWithKeys(fn ($a) => [$a['attribute'] => $a['value']])
        ->all()), $attributes, $stimulus, except: ['as', 'tag'], protectedPrefixes: ['data-conditional-fields-']);
@endphp
<{{ $as }} {{ $conditionalFieldAttributes }}>
{{ $slot }}
</{{ $as }}>
