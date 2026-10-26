@php
    use Emaia\LaravelHotwire\Support\RevealItems;
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $reveal = $revealRoot;
    $resolvedItems = RevealItems::resolve($slot->toHtml());
    $slotHtml = $resolvedItems['html'];
    $hasExplicitItems = $resolvedItems['declaresItems'];
    foreach ($resolvedItems['warnings'] as $warning) {
        logger()->warning($warning);
    }
    $userStyle = trim((string) $attributes->get('style'));
    $style = collect([
        $reveal->stagger !== null ? "--reveal-stagger: {$reveal->stagger}" : null,
        $reveal->duration !== null ? "--reveal-duration: {$reveal->duration}" : null,
        $reveal->delay !== null ? "--reveal-delay: {$reveal->delay}" : null,
        $reveal->maxSteps !== null ? "--reveal-max-steps: {$reveal->maxSteps}" : null,
        $userStyle !== '' ? $userStyle : null,
    ])->filter()->implode('; ');
    $style = $style !== '' ? $style.';' : null;
    $revealAttributes = StimulusAttributes::merge([
        'data-slot' => $slotName,
        'data-controller' => 'reveal',
        'data-reveal-trigger-value' => $reveal->trigger,
        'data-reveal-threshold-value' => $reveal->threshold,
        'data-reveal-root-margin-value' => $reveal->rootMargin,
        'data-reveal-once-value' => $reveal->once ? 'true' : 'false',
        'data-reveal-scope' => $reveal->scope,
        'data-motion' => $reveal->motion,
        'data-reveal-children' => $hasExplicitItems ? null : true,
        'style' => $style,
    ], $attributes, $reveal->stimulus, except: ['as', 'style'], protectedPrefixes: [
        'data-reveal-',
        'data-slot',
        'data-motion',
    ]);
@endphp

<{{ $reveal->as }} {{ $revealAttributes }}>{!! $slotHtml !!}</{{ $reveal->as }}>
