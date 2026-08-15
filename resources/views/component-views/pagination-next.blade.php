@php
    $isDisabled = $disabled || $href === null;
    $tag = $isDisabled ? 'span' : 'a';
    $hasLabel = $label !== null && $label !== '';
    $controlSize = $hasLabel ? $size : 'icon';
    $resolvedFrame = $tag === 'a' ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $controlAttributes = $attributes->except(['frame', 'turbo-frame', 'data-turbo-frame', 'data-turbo-stream']);
@endphp

<{{ $tag }}
    {{ $controlAttributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $resolvedFrame,
        'data-turbo-stream' => $tag === 'a' && $turboStream ? true : null,
        'role' => $isDisabled ? 'link' : null,
        'aria-label' => $ariaLabel,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-next',
        'data-size' => $controlSize,
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>
    @if ($hasLabel || $icon !== null || $iconName !== '')
        <span data-slot="pagination-next-content">
            @if ($hasLabel)
                <span data-slot="pagination-next-label">{{ $label }}</span>
            @endif
            @if ($icon !== null)
                {{ $icon }}
            @else
                <x-hw::icon :name="$iconName" data-slot="pagination-next-icon" data-icon="inline-end" aria-hidden="true" />
            @endif
        </span>
    @endif
    @if ($loadingLabel !== null)
        <span data-slot="pagination-next-loading-content">
            @if ($hasLabel && $loadingLabel !== '')
                <span data-slot="pagination-next-loading-label">{{ $loadingLabel }}</span>
            @endif
            <x-hw::spinner data-slot="pagination-next-spinner" role="presentation" aria-label="" aria-hidden="true" />
        </span>
    @endif
</{{ $tag }}>
