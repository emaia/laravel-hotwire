@php
    $backToTopAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'type' => 'button',
        'data-slot' => 'back-to-top',
        'data-variant' => $variant,
        'data-size' => $size,
        'data-controller' => 'back-to-top',
        'data-action' => 'back-to-top#scrollToTop',
        'data-back-to-top-threshold-value' => $threshold,
        'data-visible' => 'false',
        'aria-label' => $label,
        'inert' => true,
    ], $attributes, $stimulus, except: ['type', 'data-slot', 'data-variant', 'data-size'], protectedPrefixes: ['data-back-to-top-', 'data-visible', 'inert']);
@endphp

<button {{ $backToTopAttributes }}>
    @if ($slot->isEmpty())
        <x-hw::icon :name="$icon" aria-hidden="true" />
    @else
        {{ $slot }}
    @endif
</button>
