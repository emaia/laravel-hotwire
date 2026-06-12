@php
    $dataController = trim($identifier.' '.($attributes->get('data-controller') ?? ''));
@endphp

<div
    data-controller="{{ $dataController }}"
    @if ($center !== null) data-{{ $identifier }}-center-value="{{ json_encode($center) }}" @endif
    data-{{ $identifier }}-zoom-value="{{ $zoom }}"
    @if ($encodedMarkers !== null) data-{{ $identifier }}-markers-value="{{ $encodedMarkers }}" @endif
    @if ($url !== null && $url !== '') data-{{ $identifier }}-url-value="{{ $url }}" @endif
    @if ($scrollWheelZoom === false) data-{{ $identifier }}-scroll-wheel-zoom-value="false" @endif
    @if ($resolvedFit) data-{{ $identifier }}-fit-value="true" @endif
    style="{{ $style() }}"
    {{ $attributes->except(['data-controller'])->merge(['class' => $class]) }}
></div>
