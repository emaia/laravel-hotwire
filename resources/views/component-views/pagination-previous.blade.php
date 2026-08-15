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
        'data-slot' => 'pagination-previous',
        'data-size' => $controlSize,
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>
    <x-hw::icon name="chevron-left" data-icon="inline-start" aria-hidden="true" />
    @if ($hasLabel)
        <span data-slot="pagination-previous-label">{{ $label }}</span>
    @endif
</{{ $tag }}>
