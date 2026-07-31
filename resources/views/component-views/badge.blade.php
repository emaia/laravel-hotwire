@php
    $href = $attributes->get('href');
@endphp

<{{ $as }} data-slot="badge" data-variant="{{ $variant }}" @if ($as === 'a' && $href !== null && $href !== false) href="{{ $href }}" @endif {{ $attributes->except('href') }}>{{ $slot }}</{{ $as }}>
