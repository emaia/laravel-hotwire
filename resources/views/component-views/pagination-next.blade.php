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
        'aria-label' => $ariaLabel,
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-next',
        'data-size' => $controlSize,
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>
    @if ($hasLabel)
        <span data-slot="pagination-next-label">{{ $label }}</span>
    @endif
    <x-hw::icon name="chevron-right" data-icon="inline-end" aria-hidden="true" />
</{{ $tag }}>
