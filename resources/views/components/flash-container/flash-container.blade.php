@php
    $bool = fn (bool $v) => $v ? 'true' : 'false';
@endphp
<div
    id="{{ $id }}"
    @if ($turboPermanent) data-turbo-permanent @endif
    @if ($class !== '') class="{{ $class }}" @endif
    data-controller="notification--toaster"
    data-notification--toaster-position-value="{{ $position }}"
    data-notification--toaster-theme-value="{{ $theme }}"
    data-notification--toaster-duration-value="{{ $duration }}"
    data-notification--toaster-visible-toasts-value="{{ $visibleToasts }}"
    data-notification--toaster-close-button-value="{{ $bool($closeButton) }}"
    data-notification--toaster-rich-colors-value="{{ $bool($richColors) }}"
    data-notification--toaster-expand-value="{{ $bool($expand) }}"
    data-notification--toaster-invert-value="{{ $bool($invert) }}"
    data-notification--toaster-auto-disconnect-value="{{ $bool($autoDisconnect) }}"
    @if (! is_null($gap))
        data-notification--toaster-gap-value="{{ $gap }}"
    @endif
    @if (! is_null($hotkey))
        data-notification--toaster-hotkey-value="{{ $hotkey }}"
    @endif
    @if (! is_null($dir))
        data-notification--toaster-dir-value="{{ $dir }}"
    @endif
    @if (! is_null($offset))
        data-notification--toaster-offset-value="{{ $offset }}"
    @endif
    @if (! is_null($mobileOffset))
        data-notification--toaster-mobile-offset-value="{{ $mobileOffset }}"
    @endif
    @if (! is_null($swipeDirections))
        data-notification--toaster-swipe-directions-value="{{ $swipeDirections }}"
    @endif
    @if (! is_null($className))
        data-notification--toaster-class-name-value="{{ $className }}"
    @endif
    @if (! is_null($containerAriaLabel))
        data-notification--toaster-container-aria-label-value="{{ $containerAriaLabel }}"
    @endif
    @if (! is_null($customAriaLabel))
        data-notification--toaster-custom-aria-label-value="{{ $customAriaLabel }}"
    @endif
></div>
