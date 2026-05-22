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
    @if ($controller !== '') data-controller="{{ $controller }}" @endif
    {{ $attributes->merge(['method' => 'post'])->except(['auto-submit', 'unsaved-changes', 'clean-query-params']) }}
>
    {{ $slot }}
</form>
