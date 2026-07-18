@php
    $toggleAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'color-scheme-toggle',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-controller' => $toggleController,
        'data-action' => 'color-scheme#cycle',
        'data-color-scheme-modes-value' => $modes,
        'data-color-scheme-storage-key-value' => $storageKey,
        'data-color-scheme-default-value' => $default,
        'data-mode' => $default,
        'data-scheme' => $default === 'dark' ? 'dark' : 'light',
        'data-tooltip-content-value' => $hasTooltip ? $tooltip : null,
        'data-tooltip-side-value' => $hasTooltip ? $tooltipSide : null,
        'data-tooltip-align-value' => $hasTooltip ? $tooltipAlign : null,
        'data-tooltip-enabled-when-value' => $hasTooltip ? $tooltipEnabledWhen : null,
    ], $attributes, $stimulus, except: ['modes', 'storage-key', 'default', 'tooltip', 'tooltip-side', 'tooltip-align', 'tooltip-enabled-when'], protectedPrefixes: $protectedPrefixes);
@endphp

<button {{ $toggleAttributes }}>
    <x-hw::icon name="sun" data-slot="color-scheme-icon" data-scheme-icon="light" />
    <x-hw::icon name="moon" data-slot="color-scheme-icon" data-scheme-icon="dark" />
    <x-hw::icon name="monitor" data-slot="color-scheme-icon" data-mode-icon="system" />
    {{ $slot }}
</button>
