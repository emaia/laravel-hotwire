<div
    data-slot="flash-message"
    data-turbo-temporary
    data-controller="toast"
    data-toast-message-value="{{ $finalMessage }}"
    data-toast-type-value="{{ $finalType }}"
    @if ($description)
        data-toast-description-value="{{ $description }}"
    @endif
    @if ($position)
        data-toast-position-value="{{ $position }}"
    @endif
    @if ($className)
        data-toast-class-name-value="{{ $className }}"
    @endif
></div>
