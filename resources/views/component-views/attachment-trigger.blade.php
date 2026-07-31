@php
    $disabled = $attributes->has('disabled') && ! in_array($attributes->get('disabled'), [false, null], true);
    $resolvedFrame = $as === 'a' && ! $disabled ? \Emaia\LaravelHotwire\Support\FrameTarget::resolve($frame, $attributes) : null;
    $triggerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'attachment-trigger',
        'type' => $as === 'button' ? $type : null,
        'href' => $as === 'a' && ! $disabled ? $attributes->get('href') : null,
        'disabled' => $as === 'button' && $disabled ? true : null,
        'aria-disabled' => $as === 'a' && $disabled ? 'true' : $attributes->get('aria-disabled'),
        'tabindex' => $as === 'a' && $disabled ? '-1' : $attributes->get('tabindex'),
        'data-turbo-frame' => $resolvedFrame,
    ], $attributes, except: ['data-slot', 'type', 'href', 'disabled', 'aria-disabled', 'tabindex', 'frame', 'data-turbo-frame']);
@endphp

<{{ $as }} {{ $triggerAttributes }}>{{ $slot }}</{{ $as }}>
