@aware(['dropdownId' => null])

@php
    if ($dropdownId === null) {
        throw new InvalidArgumentException('Dropdown content must be rendered inside a Dropdown root.');
    }

    $contentAttributes = [
        'id' => $dropdownId,
        'data-slot' => 'dropdown-menu',
        'data-state' => 'closed',
        'data-motion' => $motion,
        'data-side' => $side,
        'data-align' => $align,
        'hidden' => true,
        'inert' => true,
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

    $contentAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge(
        $contentAttributes,
        $attributes,
        except: ['id', 'data-slot', 'data-state', 'data-motion', 'data-side', 'data-align', 'hidden', 'inert'],
        protectedPrefixes: ['data-dropdown-'],
    );
@endphp

<div {{ $contentAttributes }}>
    {{ $slot }}
</div>
