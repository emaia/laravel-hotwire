@aware(['hoverCardId' => null, 'hoverCardSide' => 'bottom', 'hoverCardAlign' => 'start'])

@php
    if ($hoverCardId === null) {
        throw new InvalidArgumentException('Hover Card content must be rendered inside a Hover Card root.');
    }

    $contentAttributes = [
        'id' => $hoverCardId,
        'data-slot' => 'hover-card-content',
        'data-state' => 'closed',
        'data-motion' => $hoverCardContentMotion,
        'data-side' => $hoverCardSide,
        'data-align' => $hoverCardAlign,
        'hidden' => true,
        'inert' => true,
        'data-hover-card-target' => 'content',
        'data-action' => 'mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut',
        'role' => 'tooltip',
    ];

    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        $contentAttributes,
        $attributes,
        except: ['id', 'data-slot', 'data-state', 'data-motion', 'data-side', 'data-align', 'hidden', 'inert', 'role'],
        protectedPrefixes: ['data-hover-card-'],
    );
@endphp

<div {{ $contentAttributes }}>
    {{ $slot }}
</div>
