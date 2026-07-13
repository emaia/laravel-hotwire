@php
    $isDisabled = $disabled || $href === null;
    $tag = $isDisabled ? 'span' : 'a';
@endphp

<{{ $tag }}
    {{ $attributes->merge([
        'href' => $tag === 'a' ? $href : null,
        'data-turbo-frame' => $tag === 'a' ? $turboFrame : null,
        'aria-label' => 'Go to next page',
        'aria-disabled' => $isDisabled ? 'true' : null,
        'data-slot' => 'pagination-next',
        'data-size' => 'default',
        'data-disabled' => $isDisabled ? 'true' : null,
    ]) }}
>
    <span data-slot="pagination-next-label">{{ $label }}</span>
    <x-hw::icon name="chevron-right" data-icon="inline-end" aria-hidden="true" />
</{{ $tag }}>
