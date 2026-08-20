@aware(['popoverId' => null, 'popoverSide' => 'bottom', 'popoverAlign' => 'start'])

@php
    if ($popoverId === null && ! $popoverContentStandalone) {
        throw new InvalidArgumentException('Popover content must be rendered inside a Popover root.');
    }

    if (! $popoverContentStandalone && ($popoverContentSideProvided || $popoverContentAlignProvided)) {
        throw new InvalidArgumentException('Popover content side and align props are only supported when standalone is true. Set side and align on the Popover root instead.');
    }

    $resolvedId = $popoverContentStandalone
        ? $attributes->get('id')
        : $popoverId;

    if ($resolvedId === null || $resolvedId === '') {
        throw new InvalidArgumentException('Standalone Popover content requires an id attribute.');
    }

    $resolvedSide = $popoverContentStandalone && $popoverContentSideProvided ? $popoverContentSide : $popoverSide;
    $resolvedAlign = $popoverContentStandalone && $popoverContentAlignProvided ? $popoverContentAlign : $popoverAlign;

    $contentAttributes = [
        'id' => $resolvedId,
        'data-slot' => 'popover-content',
        'data-state' => 'closed',
        'data-motion' => $popoverContentMotion,
        'data-side' => $resolvedSide,
        'data-align' => $resolvedAlign,
        'hidden' => true,
        'inert' => true,
        'data-popover-target' => $popoverContentStandalone ? null : 'content',
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
