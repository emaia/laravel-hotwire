@props([
    'state' => ['type' => 'shadow'],
    'formState' => ['type' => 'shadow'],
    'conditionalFieldState' => null,
    'value' => 99,
    'max' => 100,
    'formattedValue' => '99',
    'formattedMax' => '100',
    'formattedPercentage' => '99',
])

<div>{{ $slot }}</div>
