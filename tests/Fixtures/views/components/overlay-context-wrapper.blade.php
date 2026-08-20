@props([
    'id' => 'shadow-overlay',
    'open' => false,
    'size' => 'sm',
    'class' => 'shadow-class',
    'closeButton' => true,
    'fixedTop' => false,
    'side' => 'bottom',
    'align' => 'start',
    'direction' => 'left',
    'axis' => 'y',
    'backdrop' => true,
    'frame' => 'shadow-frame',
    'motion' => 'default',
    'viewTransition' => false,
])

<div>{{ $slot }}</div>
