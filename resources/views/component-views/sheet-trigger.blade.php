<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'sheet-trigger', 'data-action' => 'click->sheet#toggle']) }}>{{ $slot }}</button>
