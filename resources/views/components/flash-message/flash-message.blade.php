<div
    data-turbo-temporary
    data-controller="notification--toast"
    data-notification--toast-message-value="{{ $finalMessage }}"
    data-notification--toast-type-value="{{ $finalType }}"
    @if ($description)
        data-notification--toast-description-value="{{ $description }}"
    @endif
></div>
