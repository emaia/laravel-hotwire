<li {{ $attributes->except('data-slot')->merge(['data-slot' => $slotName]) }}>{{ $slot }}</li>
