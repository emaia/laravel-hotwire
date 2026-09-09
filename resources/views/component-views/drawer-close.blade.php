<button {{ $attributes->merge(['type' => 'button', 'data-slot' => $slotName, 'data-action' => 'drawer#close']) }}>{{ $slot }}</button>
