@aware(['sheetId' => null])

@php
    if ($sheetId === null) {
        throw new InvalidArgumentException('Sheet trigger must be rendered inside a Sheet root.');
    }
@endphp

<button {{ $attributes->merge(['type' => 'button', 'data-slot' => 'sheet-trigger', 'data-action' => 'click->sheet#toggle']) }}>{{ $slot }}</button>
