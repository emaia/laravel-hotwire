@php
    $tag = $dropdownItemHref !== null ? 'a' : 'button';
    $resolvedFrame = $tag === 'a' && ! $dropdownItemDisabled
        ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($dropdownItemFrame, $attributes)
        : null;
    $itemAttributes = $attributes->except(['frame', 'data-turbo-frame']);
@endphp

<{{ $tag }}
    {{ $itemAttributes->merge([
        'href' => $tag === 'a' && ! $dropdownItemDisabled ? $dropdownItemHref : null,
        'type' => $tag === 'button' ? $dropdownItemType : null,
        'data-turbo-frame' => $resolvedFrame,
        'data-slot' => 'dropdown-item',
        'data-variant' => $dropdownItemVariant,
        'data-inset' => $dropdownItemInset ? 'true' : null,
        'data-disabled' => $dropdownItemDisabled ? 'true' : null,
        'disabled' => $dropdownItemDisabled && $tag === 'button' ? true : null,
        'aria-disabled' => $dropdownItemDisabled ? 'true' : null,
        'tabindex' => $dropdownItemDisabled && $tag === 'a' ? '-1' : null,
    ]) }}
>{{ $slot }}</{{ $tag }}>
