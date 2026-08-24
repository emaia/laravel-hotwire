@php
    $form = $formRoot;
    extract($compute($attributes));
    $resolvedFrame = \Emaia\LaravelHotwire\Support\FrameTarget::resolve($form->frame, $attributes);

    $formAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'form',
        'data-controller' => $controller ?: null,
        'data-auto-submit-delay-value' => $form->autoSubmit ? $form->autoSubmitDelay : null,
        'data-turbo-frame' => $resolvedFrame,
        'method' => $isSpoofMethod ? 'post' : $method,
        'enctype' => $form->enctype,
    ], $attributes, $form->stimulus, except: [
        'method',
        'enctype',
        'auto-submit',
        'unsaved-changes',
        'error-scroll',
        'clean-query-params',
        'conditional-fields',
        'track-frame-src',
        'auto-submit-delay',
        'frame',
        'data-turbo-frame',
        'state',
    ], protectedPrefixes: array_values(array_filter([
        $form->autoSubmit ? 'data-auto-submit-' : null,
        $form->unsavedChanges ? 'data-unsaved-changes-' : null,
        $form->errorScroll ? 'data-error-scroll-' : null,
        $form->cleanQueryParams ? 'data-clean-query-params-' : null,
        $form->conditionalFields ? 'data-conditional-fields-' : null,
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

    @if ($form->trackFrameSrc)
        <input type="hidden" name="_turbo_frame_src" value="{{ url()->full() }}">
    @endif

    {{ $slot }}
</form>
