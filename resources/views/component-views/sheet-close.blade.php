<button {{ $attributes->merge(['type' => 'button', 'data-slot' => $slotName, 'data-action' => 'sheet#close']) }}>{{ $slot }}</button>
