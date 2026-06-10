@php
    $dataController = trim($identifier.' '.($attributes->get('data-controller') ?? ''));
@endphp

<div
    data-controller="{{ $dataController }}"
    @if ($encodedOption !== null) data-{{ $identifier }}-option-value="{{ $encodedOption }}" @endif
    @if ($url !== null && $url !== '') data-{{ $identifier }}-url-value="{{ $url }}" @endif
    @if ($theme !== null && $theme !== '') data-{{ $identifier }}-theme-value="{{ $theme }}" @endif
    style="{{ $style() }}"
    {{ $attributes->except(['data-controller'])->merge(['class' => $class]) }}
></div>
