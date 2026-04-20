@php
    $bool = fn (bool $v) => $v ? 'true' : 'false';
@endphp
<div
    id="{{ $id }}"
    @if ($turboPermanent) data-turbo-permanent @endif
    @if ($class !== '') class="{{ $class }}" @endif
    data-controller="toaster"
    data-toaster-position-value="{{ $position }}"
    data-toaster-theme-value="{{ $theme }}"
    data-toaster-duration-value="{{ $duration }}"
    data-toaster-visible-toasts-value="{{ $visibleToasts }}"
    data-toaster-close-button-value="{{ $bool($closeButton) }}"
    data-toaster-rich-colors-value="{{ $bool($richColors) }}"
    data-toaster-expand-value="{{ $bool($expand) }}"
    data-toaster-invert-value="{{ $bool($invert) }}"
    data-toaster-auto-disconnect-value="{{ $bool($autoDisconnect) }}"
    @if (! is_null($gap))
        data-toaster-gap-value="{{ $gap }}"
    @endif
    @if (! is_null($hotkey))
        data-toaster-hotkey-value="{{ $hotkey }}"
    @endif
    @if (! is_null($dir))
        data-toaster-dir-value="{{ $dir }}"
    @endif
    @if (! is_null($offset))
        data-toaster-offset-value="{{ $offset }}"
    @endif
    @if (! is_null($mobileOffset))
        data-toaster-mobile-offset-value="{{ $mobileOffset }}"
    @endif
    @if (! is_null($swipeDirections))
        data-toaster-swipe-directions-value="{{ $swipeDirections }}"
    @endif
    @if (! is_null($className))
        data-toaster-class-name-value="{{ $className }}"
    @endif
    @if (! is_null($containerAriaLabel))
        data-toaster-container-aria-label-value="{{ $containerAriaLabel }}"
    @endif
    @if (! is_null($customAriaLabel))
        data-toaster-custom-aria-label-value="{{ $customAriaLabel }}"
    @endif
></div>
