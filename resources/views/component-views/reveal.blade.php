@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

    $slotHtml = $slot->toHtml();
    $revealOwner = spl_object_id($revealCounter);
    preg_match_all('/<[^>]*\sdata-reveal-item(?:=(?:"[^"]*"|\'[^\']*\'|[^\s>]+))?[^>]*>/i', $slotHtml, $rawItems);
    $hasExplicitItems = str_contains($slotHtml, "data-reveal-owner=\"{$revealOwner}\"")
        || collect($rawItems[0])->contains(fn (string $item) => ! str_contains($item, 'data-reveal-owner='));
    $userStyle = trim((string) $attributes->get('style'));
    $style = collect([
        $stagger !== null ? "--reveal-stagger: {$stagger}" : null,
        $duration !== null ? "--reveal-duration: {$duration}" : null,
        $delay !== null ? "--reveal-delay: {$delay}" : null,
        $maxSteps !== null ? "--reveal-max-steps: {$maxSteps}" : null,
        $userStyle !== '' ? $userStyle : null,
    ])->filter()->implode('; ');
    $style = $style !== '' ? $style.';' : null;
    $revealAttributes = StimulusAttributes::merge([
        'data-slot' => 'reveal',
        'data-controller' => 'reveal',
        'data-reveal-trigger-value' => $trigger,
        'data-reveal-threshold-value' => $threshold,
        'data-reveal-root-margin-value' => $rootMargin,
        'data-reveal-once-value' => $once ? 'true' : 'false',
        'data-reveal-scope' => $scope,
        'data-reveal-owner' => $revealOwner,
        'data-motion' => $motion,
        'data-reveal-children' => $hasExplicitItems ? null : true,
        'style' => $style,
    ], $attributes, $stimulus, except: ['as', 'style'], protectedPrefixes: [
        'data-reveal-',
        'data-slot',
        'data-motion',
        'data-reveal-owner',
    ]);
@endphp

<{{ $as }} {{ $revealAttributes }}>{!! $slotHtml !!}</{{ $as }}>
