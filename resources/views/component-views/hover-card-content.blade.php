@aware(['id' => '', 'open' => false, 'side' => 'bottom', 'align' => 'start'])

@php
    $contentAttributes = [
        'id' => $id,
        'data-slot' => 'hover-card-content',
        'data-state' => 'closed',
        'data-motion' => $motion,
        'data-side' => $side,
        'data-align' => $align,
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
