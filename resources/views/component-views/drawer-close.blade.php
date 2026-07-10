<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'drawer-close', 'data-action' => 'drawer#close']) }}>{{ $slot }}</button>
