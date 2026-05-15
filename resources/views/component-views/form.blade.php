@php
    $userController = trim($attributes->get('data-controller', ''));

    $controller = trim(implode(' ', array_filter([
        $userController,
        $autoSubmit ? 'auto-submit' : null,
        $unsavedChanges ? 'unsaved-changes' : null,
        $cleanQueryParams ? 'clean-query-params' : null,
    ])));
@endphp

<form
    method="post"
    @if ($controller !== '') data-controller="{{ $controller }}" @endif
    {{ $attributes->except(['auto-submit', 'unsaved-changes', 'clean-query-params']) }}
>
    {{ $slot }}
</form>
