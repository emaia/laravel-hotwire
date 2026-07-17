@aware(['id' => '', 'open' => false])

@php
    $contentAttributes = [
        'id' => $id,
        'data-slot' => 'dropdown-menu',
        'data-open' => $open ? 'true' : 'false',
        'data-side' => $side,
        'data-align' => $align,
        'data-dropdown-target' => 'menu',
        'data-dropdown-side-value' => $side,
        'data-dropdown-align-value' => $align,
        'data-dropdown-side-offset-value' => $sideOffset,
        'data-dropdown-align-offset-value' => $alignOffset,
        'data-dropdown-strategy-value' => $strategy,
        'data-dropdown-flip-value' => $flip ? 'true' : 'false',
        'data-dropdown-shift-value' => $shift ? 'true' : 'false',
        'data-dropdown-mobile-side-value' => $mobileSide,
        'data-dropdown-mobile-align-value' => $mobileAlign,
        'data-dropdown-mobile-media-value' => $mobileSide !== null || $mobileAlign !== null ? $mobileMedia : null,
        'data-dropdown-collapsed-side-value' => $collapsedSide,
        'data-dropdown-collapsed-align-value' => $collapsedAlign,
        'data-dropdown-collapsed-when-value' => $collapsedSide !== null || $collapsedAlign !== null ? $collapsedWhen : null,
        'class' => trim($width.' '.$menuClass) ?: null,
    ];

    if ($transition) {
        $contentAttributes += [
            'data-transition-enter' => 'transition ease-out duration-100',
            'data-transition-enter-from' => 'opacity-0 scale-95',
            'data-transition-enter-to' => 'opacity-100 scale-100',
            'data-transition-leave' => 'transition ease-in duration-75',
            'data-transition-leave-from' => 'opacity-100 scale-100',
            'data-transition-leave-to' => 'opacity-0 scale-95',
        ];
    }

    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        $contentAttributes,
        $attributes,
        except: ['id', 'data-slot', 'data-open', 'data-side', 'data-align'],
        protectedPrefixes: ['data-dropdown-'],
    );
@endphp

<div {{ $contentAttributes }}>
    {{ $slot }}
</div>
