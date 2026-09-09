<button {{ $attributes->merge(['type' => 'button', 'data-slot' => $slotName, 'data-action' => 'click->sheet#toggle']) }}>{{ $slot }}</button>
