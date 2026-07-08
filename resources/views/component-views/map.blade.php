@php
    $mapAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'map',
        'data-controller' => $identifier,
        "data-{$identifier}-center-value" => $center !== null ? e(json_encode($center)) : null,
        "data-{$identifier}-zoom-value" => $zoom,
        "data-{$identifier}-markers-value" => $encodedMarkers !== null ? e($encodedMarkers) : null,
        "data-{$identifier}-url-value" => $url !== '' ? $url : null,
        "data-{$identifier}-scroll-wheel-zoom-value" => $scrollWheelZoom === false ? 'false' : null,
        "data-{$identifier}-fit-value" => $resolvedFit ? 'true' : null,
        'style' => $style(),
        'class' => $class,
    ], $attributes, $stimulus, protectedPrefixes: [
        "data-{$identifier}-center-",
        "data-{$identifier}-zoom-",
        "data-{$identifier}-markers-",
        "data-{$identifier}-url-",
        "data-{$identifier}-scroll-wheel-zoom-",
        "data-{$identifier}-fit-",
    ]);
@endphp

<div
    {{ $mapAttributes }}
></div>
