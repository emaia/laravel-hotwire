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
        'aria-label' => 'Go to next page',
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
