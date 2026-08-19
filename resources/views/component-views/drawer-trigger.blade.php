<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'drawer-trigger', 'data-drawer-target' => 'trigger', 'data-action' => 'click->drawer#toggle']) }}>{{ $slot }}</button>
