@php
    $chartAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'chart',
        'data-controller' => $identifier,
        "data-{$identifier}-option-value" => $encodedOption !== null ? e($encodedOption) : null,
        "data-{$identifier}-url-value" => $url !== '' ? $url : null,
        "data-{$identifier}-theme-value" => $theme !== '' ? $theme : null,
        "data-{$identifier}-poll-value" => $poll > 0 ? $poll : null,
        'style' => $style(),
        'class' => $class,
    ], $attributes, $stimulus, protectedPrefixes: [
        "data-{$identifier}-option-",
        "data-{$identifier}-url-",
        "data-{$identifier}-theme-",
        "data-{$identifier}-poll-",
    ]);
@endphp

<div
    {{ $chartAttributes }}
></div>
