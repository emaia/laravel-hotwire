@props([
    'id' => 'shadow-overlay',
    'size' => 'sm',
    'class' => 'shadow-class',
    'closeButton' => true,
    'fixedTop' => false,
    'side' => 'bottom',
    'direction' => 'left',
    'axis' => 'y',
    'backdrop' => true,
    'frame' => 'shadow-frame',
    'motion' => 'default',
    'viewTransition' => false,
])

<div>{{ $slot }}</div>
