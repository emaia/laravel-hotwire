<button {{ $attributes->merge(['type' => 'button', 'data-slot' => $slotName, 'data-drawer-target' => 'trigger', 'data-action' => 'click->drawer#toggle']) }}>{{ $slot }}</button>
