@php
    $menuAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'id' => $navigationMenuId,
        'data-slot' => $slotName,
        'data-orientation' => $orientation,
        'data-anchor' => $anchor,
        'data-controller' => 'navigation-menu',
        'data-navigation-menu-hover-value' => $hover ? 'true' : 'false',
        'data-navigation-menu-open-delay-value' => $openDelay,
        'data-navigation-menu-close-delay-value' => $closeDelay,
        'data-action' => 'pointerenter->navigation-menu#cancelClose pointerleave->navigation-menu#leave'
            .' focusin->navigation-menu#focusIn focusout->navigation-menu#focusOut keydown->navigation-menu#keydown'
            .' keydown.esc@window->navigation-menu#escapeOutside:capture click@document->navigation-menu#outside'
            .' turbo:before-cache@document->navigation-menu#close turbo:morph@document->navigation-menu#close',
    ], $attributes, $stimulus, except: ['id', 'data-slot', 'data-orientation', 'data-anchor'], protectedPrefixes: ['data-navigation-menu-']);
@endphp

<nav {{ $menuAttributes }}>{{ $slot }}</nav>
