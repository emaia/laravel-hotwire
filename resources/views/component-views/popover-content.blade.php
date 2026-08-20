@aware(['popoverId' => null, 'popoverSide' => 'bottom', 'popoverAlign' => 'start'])

@php
    if ($popoverId === null) {
        throw new InvalidArgumentException('Popover content must be rendered inside a Popover root.');
    }

    $contentAttributes = [
        'id' => $popoverId,
        'data-slot' => 'popover-content',
        'data-state' => 'closed',
        'data-motion' => $popoverContentMotion,
        'data-side' => $popoverSide,
        'data-align' => $popoverAlign,
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
