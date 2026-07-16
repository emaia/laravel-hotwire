@aware(['id' => '', 'open' => false, 'side' => 'bottom', 'align' => 'start', 'transition' => true])

@php
    $contentAttributes = [
        'id' => $id,
        'data-slot' => 'popover-content',
        'data-open' => $open ? 'true' : 'false',
        'data-side' => $side,
        'data-align' => $align,
        'data-popover-target' => 'content',
        'role' => 'dialog',
        'tabindex' => '-1',
    ];

    if ($transition) {
        $contentAttributes += [
            'data-transition-enter' => 'transition ease-out duration-150',
            'data-transition-enter-from' => 'opacity-0 scale-95',
            'data-transition-enter-to' => 'opacity-100 scale-100',
            'data-transition-leave' => 'transition ease-out duration-150',
            'data-transition-leave-from' => 'block opacity-100 scale-100',
            'data-transition-leave-to' => 'block opacity-0 scale-95',
        ];
    }

    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        $contentAttributes,
        $attributes,
        except: ['id', 'data-slot', 'data-open', 'data-side', 'data-align', 'role', 'tabindex'],
        protectedPrefixes: ['data-popover-'],
    );
@endphp

<div {{ $contentAttributes }}>
    {{ $slot }}
</div>
