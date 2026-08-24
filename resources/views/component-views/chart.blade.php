@php
    $chartAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'chart',
        'data-controller' => $controller,
        "data-{$controller}-option-value" => $encodedOption !== null ? e($encodedOption) : null,
        "data-{$controller}-url-value" => $url !== '' ? $url : null,
        "data-{$controller}-theme-value" => $theme !== '' ? $theme : null,
        "data-{$controller}-poll-value" => $poll > 0 ? $poll : null,
        'style' => $style(),
        'class' => $class,
    ], $attributes, $stimulus, protectedPrefixes: [
        "data-{$controller}-option-",
        "data-{$controller}-url-",
        "data-{$controller}-theme-",
        "data-{$controller}-poll-",
    ]);
@endphp

<div
    {{ $chartAttributes }}
></div>
