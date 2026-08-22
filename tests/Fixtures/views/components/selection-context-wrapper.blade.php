@props([
    'name' => 'shadow-group',
    'id' => 'shadow-group-id',
    'errorKey' => 'shadow.group',
    'selected' => [],
    'old' => false,
    'disabled' => true,
    'selectAll' => false,
    'type' => 'single',
    'variant' => 'outline',
    'size' => 'lg',
    'groupDisabled' => true,
    'autoSubmit' => false,
    'autoSubmitDelay' => 1,
])

<div>{{ $slot }}</div>
