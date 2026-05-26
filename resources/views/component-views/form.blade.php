@php extract($compute($attributes)) @endphp

<form
    @if ($controller !== '') data-controller="{{ $controller }}" @endif
    method="{{ $isSpoofMethod ? 'post' : $method }}"
    {{ $attributes->whereDoesntStartWith(['data-controller'])->except(['method', 'auto-submit', 'unsaved-changes', 'clean-query-params', 'track-frame-src']) }}
>
    @if ($method !== 'get')
        @csrf
    @endif

    @if ($isSpoofMethod)
        @method($method)
    @endif

    @if ($trackFrameSrc)
        <input type="hidden" name="_turbo_frame_src" value="{{ url()->full() }}">
    @endif

    {{ $slot }}
</form>
