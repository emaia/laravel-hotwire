@php
    $hiddenDisabled = $matches ? '' : ' hidden disabled';
    $whenAttrs = collect($dataWhenAttributes())
        ->map(fn ($a) => $a['attribute'].'="'.e($a['value']).'"')
        ->implode(' ');
@endphp
<{!! $tag !!} data-conditional-fields-target="dependent" {!! $whenAttrs !!}{!! $hiddenDisabled !!} {{ $attributes }}>
{{ $slot }}
</{!! $tag !!}>
