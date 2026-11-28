@php
    $linkAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'href' => $href,
        'aria-current' => $current ? 'page' : null,
        'data-turbo-frame' => \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes),
        'data-slot' => $slotName,
        'data-navigation-menu-target' => 'link',
        'data-action' => 'pointerenter->navigation-menu#hoverLink',
    ], $attributes, except: ['data-turbo-frame', 'data-slot'], protectedPrefixes: ['data-navigation-menu-']);
@endphp

<a {{ $linkAttributes }}>{{ $slot }}</a>
