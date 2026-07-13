@php
    $isDisabled = $disabled || $href === null;
    $tag = $isDisabled ? 'span' : 'a';
    $hasLabel = $label !== null && $label !== '';
    $controlSize = $hasLabel ? $size : 'icon';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $tag === 'a' ? $turboFrame : null,
        'aria-label' => 'Go to previous page',
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
