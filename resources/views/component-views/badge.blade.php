@php
    $href = $attributes->get('href');
@endphp

<{{ $as }} data-slot="{{ $slotName }}" data-variant="{{ $variant }}" @if ($as === 'a' && $href !== null && $href !== false) href="{{ $href }}" @endif {{ $attributes->except('href') }}>{{ $slot }}</{{ $as }}>
