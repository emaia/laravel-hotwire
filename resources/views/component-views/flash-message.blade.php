<div
    data-turbo-temporary
    data-controller="toast"
    data-toast-message-value="{{ $finalMessage }}"
    data-toast-type-value="{{ $finalType }}"
    @if ($description)
        data-toast-description-value="{{ $description }}"
    @endif
></div>
