@aware(['hoverCardId' => null, 'hoverCardSide' => 'bottom', 'hoverCardAlign' => 'start'])

@php
    if ($hoverCardId === null && ! $hoverCardContentStandalone) {
        throw new InvalidArgumentException('Hover Card content must be rendered inside a Hover Card root.');
    }

    if (! $hoverCardContentStandalone && ($hoverCardContentSide !== 'bottom' || $hoverCardContentAlign !== 'start')) {
        throw new InvalidArgumentException('Hover Card content side and align props are only supported when standalone is true. Set side and align on the Hover Card root instead.');
    }

    $resolvedId = $hoverCardContentStandalone
        ? ($attributes->get('id') ?? $hoverCardId)
        : $hoverCardId;

    if ($resolvedId === null || $resolvedId === '') {
        throw new InvalidArgumentException('Standalone Hover Card content requires an id attribute.');
    }

    $resolvedSide = $hoverCardContentStandalone ? $hoverCardContentSide : $hoverCardSide;
    $resolvedAlign = $hoverCardContentStandalone ? $hoverCardContentAlign : $hoverCardAlign;

    $contentAttributes = [
        'id' => $resolvedId,
        'data-slot' => 'hover-card-content',
        'data-state' => 'closed',
        'data-motion' => $hoverCardContentMotion,
        'data-side' => $resolvedSide,
        'data-align' => $resolvedAlign,
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
