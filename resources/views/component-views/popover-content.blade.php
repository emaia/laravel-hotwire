@aware(['popoverId' => null, 'popoverSide' => 'bottom', 'popoverAlign' => 'start'])

@php
    if ($popoverId === null && ! $popoverContentStandalone) {
        throw new InvalidArgumentException('Popover content must be rendered inside a Popover root.');
    }

    $resolvedId = $popoverId ?? $attributes->get('id');

    if ($resolvedId === null || $resolvedId === '') {
        throw new InvalidArgumentException('Standalone Popover content requires an id attribute.');
    }

    $resolvedSide = $popoverId === null ? $popoverContentSide : $popoverSide;
    $resolvedAlign = $popoverId === null ? $popoverContentAlign : $popoverAlign;

    $contentAttributes = [
        'id' => $resolvedId,
        'data-slot' => 'popover-content',
        'data-state' => 'closed',
        'data-motion' => $popoverContentMotion,
        'data-side' => $resolvedSide,
        'data-align' => $resolvedAlign,
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
