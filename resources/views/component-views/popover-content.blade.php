@aware(['popoverId' => null, 'popoverSide' => 'bottom', 'popoverAlign' => 'start'])

@php
    if ($popoverId === null && ! $popoverContentStandalone) {
        throw new InvalidArgumentException('Popover content must be rendered inside a Popover root.');
    }

    if (! $popoverContentStandalone && ($popoverContentSide !== 'bottom' || $popoverContentAlign !== 'start')) {
        throw new InvalidArgumentException('Popover content side and align props are only supported when standalone is true. Set side and align on the Popover root instead.');
    }

    $resolvedId = $popoverContentStandalone
        ? ($attributes->get('id') ?? $popoverId)
        : $popoverId;

    if ($resolvedId === null || $resolvedId === '') {
        throw new InvalidArgumentException('Standalone Popover content requires an id attribute.');
    }

    $resolvedSide = $popoverContentStandalone ? $popoverContentSide : $popoverSide;
    $resolvedAlign = $popoverContentStandalone ? $popoverContentAlign : $popoverAlign;

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
