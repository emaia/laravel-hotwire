@php
    $toggleAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => $slotName,
        'data-variant' => $variant,
        'data-size' => $size,
        'data-controller' => $toggleController,
        'data-action' => 'color-scheme#cycle',
        'data-color-scheme-modes-value' => $modes,
        'data-color-scheme-storage-key-value' => $storageKey,
        'data-color-scheme-default-value' => $default,
        'data-color-scheme-view-transition-value' => $viewTransition ? 'true' : null,
        'data-mode' => $default,
        'data-scheme' => $default === 'dark' ? 'dark' : 'light',
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-align-value' => $hasTooltip ? $tooltipAlign : null,
        'data-tooltip-motion-value' => $hasTooltip ? $tooltipMotion : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, $stimulus, except: ['modes', 'storage-key', 'default', 'tooltip', 'tooltip-side', 'tooltip-align', 'tooltip-motion', 'tooltip-enabled-when', 'data-tooltip-content-value', 'view-transition'], protectedPrefixes: $protectedPrefixes);
@endphp

<button {{ $toggleAttributes }}>
    <x-hw::icon name="sun" :data-slot="$iconSlotName" data-scheme-icon="light" />
    <x-hw::icon name="moon" :data-slot="$iconSlotName" data-scheme-icon="dark" />
    <x-hw::icon name="monitor" :data-slot="$iconSlotName" data-mode-icon="system" />
    {{ $slot }}
    @if ($hasTooltip)
        <x-hw::tooltip>{{ $tooltip }}</x-hw::tooltip>
    @endif
</button>
