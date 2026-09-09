<div {{ $attributes->except('data-slot')->merge(['data-slot' => $slotName]) }}>{{ $slot }}</div>
