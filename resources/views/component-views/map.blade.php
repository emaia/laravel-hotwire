@php
    $controller = \Emaia\LaravelHotwire\Support\StimulusIdentifier::guard((string) $controller, 'map');

    $mapAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'map',
        'data-controller' => $controller,
        "data-{$controller}-center-value" => $center !== null ? e(json_encode($center)) : null,
        "data-{$controller}-zoom-value" => $zoom,
        "data-{$controller}-markers-value" => $encodedMarkers !== null ? e($encodedMarkers) : null,
        "data-{$controller}-url-value" => $url !== '' ? $url : null,
        "data-{$controller}-scroll-wheel-zoom-value" => $scrollWheelZoom === false ? 'false' : null,
        "data-{$controller}-fit-value" => $resolvedFit ? 'true' : null,
        'style' => $style(),
        'class' => $class,
    ], $attributes, $stimulus, protectedPrefixes: [
        "data-{$controller}-center-",
        "data-{$controller}-zoom-",
        "data-{$controller}-markers-",
        "data-{$controller}-url-",
        "data-{$controller}-scroll-wheel-zoom-",
        "data-{$controller}-fit-",
    ]);
@endphp

<div
    {{ $mapAttributes }}
></div>
