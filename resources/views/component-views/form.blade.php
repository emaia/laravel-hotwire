@php
    extract($compute($attributes));

    $formAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'form',
        'data-controller' => $controller ?: null,
        'data-auto-submit-delay-value' => $autoSubmit ? $autoSubmitDelay : null,
        'data-turbo-frame' => $frame,
        'method' => $isSpoofMethod ? 'post' : $method,
        'enctype' => $enctype,
    ], $attributes, $stimulus, except: [
        'method',
        'enctype',
        'auto-submit',
        'unsaved-changes',
        'error-scroll',
        'clean-query-params',
        'track-frame-src',
        'auto-submit-delay',
        'frame',
    ], protectedPrefixes: array_values(array_filter([
        $autoSubmit ? 'data-auto-submit-' : null,
        $unsavedChanges ? 'data-unsaved-changes-' : null,
        $errorScroll ? 'data-error-scroll-' : null,
        $cleanQueryParams ? 'data-clean-query-params-' : null,
    ])));
@endphp

<form
    {{ $formAttributes }}
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
