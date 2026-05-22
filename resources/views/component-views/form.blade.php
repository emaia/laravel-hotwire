@php
    $userController = trim($attributes->get('data-controller', ''));
    $method = strtolower($attributes->get('method', 'post'));

    $controller = trim(implode(' ', array_filter([
        $userController,
        $autoSubmit ? 'auto-submit' : null,
        $unsavedChanges ? 'unsaved-changes' : null,
        $cleanQueryParams ? 'clean-query-params' : null,
    ])));
@endphp

<form
    @if ($controller !== '') data-controller="{{ $controller }}" @endif
    {{ $attributes->merge(['method' => 'post'])->except(['auto-submit', 'unsaved-changes', 'clean-query-params', 'track-frame-src']) }}
>
    @if ($method !== 'get')
        @csrf
    @endif

    @if ($trackFrameSrc)
        <input type="hidden" name="_turbo_frame_src" value="{{ url()->full() }}">
    @endif

    {{ $slot }}
</form>
