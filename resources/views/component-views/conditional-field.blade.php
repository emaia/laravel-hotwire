@php
    $conditionalFieldAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(array_merge([
        'data-slot' => 'conditional-field',
        'data-conditional-fields-target' => 'dependent',
        'hidden' => $matches ? null : true,
        'disabled' => $matches ? null : true,
    ], collect($dataWhenAttributes())
        ->mapWithKeys(fn ($a) => [$a['attribute'] => $a['value']])
        ->all()), $attributes, $stimulus, protectedPrefixes: ['data-conditional-fields-']);
@endphp
<{!! $tag !!} {{ $conditionalFieldAttributes }}>
{{ $slot }}
</{!! $tag !!}>
