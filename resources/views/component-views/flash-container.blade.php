@php
    $bool = fn (bool $v) => $v ? 'true' : 'false';

    $flashContainerAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'flash-container',
        'id' => $id,
        'data-turbo-permanent' => $turboPermanent ? true : null,
        'class' => $class !== '' ? $class : null,
        'data-controller' => 'toaster',
        'data-toaster-position-value' => $position,
        'data-toaster-theme-value' => $theme,
        'data-toaster-duration-value' => $duration,
        'data-toaster-visible-toasts-value' => $visibleToasts,
        'data-toaster-close-button-value' => $bool($closeButton),
        'data-toaster-rich-colors-value' => $bool($richColors),
        'data-toaster-expand-value' => $bool($expand),
        'data-toaster-invert-value' => $bool($invert),
        'data-toaster-auto-disconnect-value' => $bool($autoDisconnect),
        'data-toaster-gap-value' => $gap,
        'data-toaster-hotkey-value' => $hotkey,
        'data-toaster-dir-value' => $dir,
        'data-toaster-offset-value' => $offset !== null ? e($offset) : null,
        'data-toaster-mobile-offset-value' => $mobileOffset !== null ? e($mobileOffset) : null,
        'data-toaster-swipe-directions-value' => $swipeDirections,
        'data-toaster-class-name-value' => $className,
        'data-toaster-container-aria-label-value' => $containerAriaLabel,
        'data-toaster-custom-aria-label-value' => $customAriaLabel,
    ], $attributes, $stimulus, protectedPrefixes: ['data-toaster-']);
@endphp
<div
    {{ $flashContainerAttributes }}
></div>
