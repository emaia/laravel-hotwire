@aware(['id' => '', 'open' => false, 'side' => 'bottom', 'align' => 'start'])

@php
    $contentAttributes = [
        'id' => $id,
        'data-slot' => 'popover-content',
        'data-state' => 'closed',
        'data-motion' => $motion,
        'data-side' => $side,
        'data-align' => $align,
        'hidden' => true,
        'inert' => true,
        'data-popover-target' => 'content',
        'role' => 'dialog',
        'tabindex' => '-1',
    ];

    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        $contentAttributes,
        $attributes,
        except: ['id', 'data-slot', 'data-state', 'data-motion', 'data-side', 'data-align', 'hidden', 'inert', 'role', 'tabindex'],
        protectedPrefixes: ['data-popover-'],
    );
@endphp

<div {{ $contentAttributes }}>
    {{ $slot }}
</div>
