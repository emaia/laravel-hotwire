@aware(['dropdownId' => null])

@php
    $resolvedId = $dropdownId ?? $attributes->get('id');

    if ($resolvedId === null || $resolvedId === '') {
        throw new InvalidArgumentException('Dropdown content must be rendered inside a Dropdown root.');
    }

    $contentAttributes = [
        'id' => $resolvedId,
        'data-slot' => 'dropdown-menu',
        'data-state' => 'closed',
        'data-motion' => $dropdownContentMotion,
        'data-side' => $dropdownContentSide,
        'data-align' => $dropdownContentAlign,
        'hidden' => true,
        'inert' => true,
        'data-dropdown-target' => 'menu',
        'data-dropdown-side-value' => $dropdownContentSide,
        'data-dropdown-align-value' => $dropdownContentAlign,
        'data-dropdown-side-offset-value' => $dropdownContentSideOffset,
        'data-dropdown-align-offset-value' => $dropdownContentAlignOffset,
        'data-dropdown-strategy-value' => $dropdownContentStrategy,
        'data-dropdown-flip-value' => $dropdownContentFlip ? 'true' : 'false',
        'data-dropdown-shift-value' => $dropdownContentShift ? 'true' : 'false',
        'data-dropdown-mobile-side-value' => $dropdownContentMobileSide,
        'data-dropdown-mobile-align-value' => $dropdownContentMobileAlign,
        'data-dropdown-mobile-media-value' => $dropdownContentMobileSide !== null || $dropdownContentMobileAlign !== null ? $dropdownContentMobileMedia : null,
        'data-dropdown-collapsed-side-value' => $dropdownContentCollapsedSide,
        'data-dropdown-collapsed-align-value' => $dropdownContentCollapsedAlign,
        'data-dropdown-collapsed-when-value' => $dropdownContentCollapsedSide !== null || $dropdownContentCollapsedAlign !== null ? $dropdownContentCollapsedWhen : null,
        'class' => trim($dropdownContentWidth.' '.$dropdownContentMenuClass) ?: null,
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
