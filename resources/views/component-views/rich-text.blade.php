@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));
    // Escape `\` and `'` so an id containing either still produces a valid CSS attribute selector.
    $escapedId = addcslashes($resolvedId, "\\'");
    $outletSelector = '[data-'.$identifier."-id-value='".$escapedId."']";

    $richTextAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'rich-text',
        'class' => $class ?: null,
        'data-controller' => $dataController,
        "data-{$identifier}-id-value" => $resolvedId,
        "data-{$identifier}-placeholder-value" => $placeholder,
        "data-{$identifier}-editable-value" => $editable ? null : 'false',
        "data-{$identifier}-output-value" => $output !== 'html' ? $output : null,
        "data-{$identifier}-editor-class-value" => $editorClass !== '' ? $editorClass : null,
        "data-{$identifier}-image-upload-value" => $imageUpload ? 'true' : null,
        'aria-required' => $isRequired ? 'true' : null,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: [
        "data-{$identifier}-id-",
        "data-{$identifier}-placeholder-",
        "data-{$identifier}-editable-",
        "data-{$identifier}-output-",
        "data-{$identifier}-editor-class-",
        "data-{$identifier}-image-upload-",
    ]);
@endphp

<div
    {{ $richTextAttributes }}
>
    {{-- The synced textarea carries `aria-required` but NOT the HTML `required` attr: a `hidden`
         form control that can't be focused triggers Chrome's "An invalid form control is not
         focusable" warning and silently blocks submit with no visible tooltip. Validation lives
         server-side (Laravel `required`), and `[data-invalid]` on the wrapper handles the visual.
         See "Required + client-side validation" in the component docs for a JS opt-in. --}}
    <textarea
        data-slot="rich-text-input"
        @if ($name) name="{{ $name }}" @endif
        data-{{ $identifier }}-target="input"
        @if ($isRequired) aria-required="true" @endif
        @if ($hasErrors) aria-invalid="true" @endif
        @if ($inputClass !== '') class="{{ $inputClass }}" @else hidden @endif
    >{{ $resolvedValue }}</textarea>

    @if ($toolbar !== false)
        <div
            data-slot="rich-text-toolbar"
            role="toolbar"
            aria-label="Formatting"
            data-controller="rich-text-toolbar"
            data-rich-text-toolbar-editor-value="{{ $outletSelector }}"
        >
            @foreach ($toolbarButtons() as $button)
                <button
                    data-slot="rich-text-toolbar-button"
                    type="button"
                    data-action="click->rich-text-toolbar#{{ $button['action'] }}"
                    @if ($button['target']) data-rich-text-toolbar-target="{{ $button['target'] }}" @endif
                    @if (isset($button['level'])) data-level="{{ $button['level'] }}" @endif
                    aria-label="{{ $button['label'] }}"
                >
                    <hw:icon name="{{ $button['icon'] }}" aria-hidden="true" />
                </button>
            @endforeach
        </div>
    @else
        {{ $slot ?? '' }}
    @endif

    <div data-slot="rich-text-editor" data-{{ $identifier }}-target="editor"></div>
</div>
