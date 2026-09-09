@php
    $resolvedFrame = $href !== null
        ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes)
        : null;
@endphp

<a data-slot="{{ $slotName }}"@if ($href !== null) href="{{ $href }}"@endif @if ($resolvedFrame !== null) data-turbo-frame="{{ $resolvedFrame }}" @endif {{ $attributes->except(['frame', 'data-turbo-frame']) }}>{{ $slot }}</a>
