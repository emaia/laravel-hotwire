@aware(['sidebarState' => 'expanded'])

@php
    use Emaia\LaravelHotwire\Support\StimulusAttributes;

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
    ]), $attributes, except: ['style'], protectedPrefixes: [
        'data-slot',
        'data-side',
        'data-sidebar-target',
        'data-reveal-',
        'data-motion',
    ]);
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
        data-sidebar-target="modal"
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
            data-sidebar-target="backdrop"
            data-action="click->sidebar#clickOutside"
        ></div>
        <div data-slot="sidebar-gap"></div>
        <div
            {{ $surfaceAttributes([
                'data-slot' => 'sidebar-container',
                'data-side' => $side,
                'data-sidebar-target' => 'dialog',
            ]) }}
        >
            <aside data-slot="sidebar-inner" data-sidebar="sidebar">
                {{ $slot }}
            </aside>
        </div>
    </div>
@endif
