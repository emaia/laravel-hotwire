@php extract($compute($attributes)) @endphp

<form
    data-slot="form"
    @if ($controller !== '') data-controller="{{ $controller }}" @endif
    method="{{ $isSpoofMethod ? 'post' : $method }}"
    @if ($enctype !== null) enctype="{{ $enctype }}" @endif
    {{ $attributes->whereDoesntStartWith(['data-controller'])->except(['method', 'enctype', 'auto-submit', 'unsaved-changes', 'error-scroll', 'clean-query-params', 'track-frame-src']) }}
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
