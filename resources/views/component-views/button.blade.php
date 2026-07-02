@php $isButton = $as === 'button'; @endphp

<{{ $as }}
    @if ($isButton) type="{{ $type }}" @endif
    data-slot="{{ $slotName }}"
    data-variant="{{ $variant }}"
    data-size="{{ $size }}"
    {{ $attributes }}
    @if ($stimulus !== null) {!! $stimulus->toHtml() !!} @endif
>{{ $slot }}</{{ $as }}>
