@aware(['sidebarState' => 'expanded', 'sidebarIdentifier' => 'sidebar'])

@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;
    use Emaia\LaravelHotwire\Support\StimulusIdentifier;

    $sidebarIdentifier = StimulusIdentifier::guard((string) $sidebarIdentifier, 'sidebar');
    $collapsed = $sidebarState === 'collapsed';
    $userStyle = trim((string) $attributes->get('style'));
    $revealStyle = $reveal ? collect([
        $revealStagger !== null ? "--reveal-stagger: {$revealStagger}" : null,
        $revealDuration !== null ? "--reveal-duration: {$revealDuration}" : null,
        $revealDelay !== null ? "--reveal-delay: {$revealDelay}" : null,
        $revealMaxSteps !== null ? "--reveal-max-steps: {$revealMaxSteps}" : null,
    ])->filter()->implode('; ') : '';
    $style = collect([$revealStyle, $userStyle !== '' ? $userStyle : null])->filter()->implode('; ');
    $style = $style !== '' ? $style.';' : null;
    $surfaceAttributes = fn (array $internal) => StimulusAttributes::merge(array_merge($internal, [
        'data-controller' => $reveal ? 'reveal' : null,
        'data-reveal-trigger-value' => $reveal ? 'load' : null,
        'data-reveal-scope' => $reveal ? 'document' : null,
        'data-motion' => $reveal ? $revealMotion : ($internal['data-motion'] ?? null),
        'style' => $style,
    ]), $attributes, except: ['style'], protectedPrefixes: array_values(array_filter([
        'data-slot',
        'data-side',
        "data-{$sidebarIdentifier}-target",
        // Only while the internal Reveal is the one driving them; with the prop off the visitor is
        // free to mount their own reveal and configure it.
        $reveal ? 'data-reveal-' : null,
        $reveal ? 'data-motion' : null,
    ])));
@endphp

@if ($collapsible === 'none')
    <aside
        {{ $surfaceAttributes([
            'data-slot' => 'sidebar',
            'data-sidebar' => 'sidebar',
            'data-side' => $side,
            'data-variant' => $variant,
            'data-collapsible' => 'none',
        ]) }}
    >{{ $slot }}</aside>
@else
    <div
        data-slot="sidebar"
        data-{{ $sidebarIdentifier }}-target="modal"
        data-state="{{ $sidebarState }}"
        data-mobile-state="closed"
        data-motion="{{ $motion }}"
        data-side="{{ $side }}"
        data-variant="{{ $variant }}"
        data-collapsible="{{ $collapsed ? $collapsible : '' }}"
        data-sidebar-collapsible="{{ $collapsible }}"
    >
        <div
            data-slot="sidebar-backdrop"
            data-{{ $sidebarIdentifier }}-target="backdrop"
            data-action="click->{{ $sidebarIdentifier }}#clickOutside"
        ></div>
        <div data-slot="sidebar-gap"></div>
        <div
            {{ $surfaceAttributes([
                'data-slot' => 'sidebar-container',
                'data-side' => $side,
                "data-{$sidebarIdentifier}-target" => 'dialog',
            ]) }}
        >
            <aside data-slot="sidebar-inner" data-sidebar="sidebar">
                {{ $slot }}
            </aside>
        </div>
    </div>
@endif
