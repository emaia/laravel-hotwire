@aware(['fieldName' => null, 'fieldId' => null, 'fieldErrorKey' => null, 'fieldRequired' => false])

@php
    $explicitName = $name ?? null;
    $id = \Emaia\LaravelHotwire\Support\FieldKey::resolveId($id ?? null, $explicitName, $fieldId, $fieldName);
    $errorKey = \Emaia\LaravelHotwire\Support\FieldKey::resolveErrorKey($errorKey ?? null, $explicitName, $fieldErrorKey, $fieldName);
    $name = $explicitName ?? $fieldName;
    extract($compute($name, $id, $errorKey, $fieldRequired ?? false, $errors, $attributes));
    // Escape `\` and `'` so an id containing either still produces a valid CSS attribute selector.
    $escapedId = addcslashes($resolvedId, "\\'");
    $outletSelector = '[data-'.$controller."-id-value='".$escapedId."']";

    $richTextAttributes = \Emaia\LaravelHotwire\Support\StimulusAttributes::merge([
        'data-slot' => 'rich-text',
        'class' => $class ?: null,
        'data-controller' => $controller,
        "data-{$controller}-id-value" => $resolvedId,
        "data-{$controller}-placeholder-value" => $placeholder,
        "data-{$controller}-editable-value" => $editable ? null : 'false',
        "data-{$controller}-output-value" => $output !== 'html' ? $output : null,
        "data-{$controller}-editor-class-value" => $editorClass !== '' ? $editorClass : null,
        "data-{$controller}-image-upload-value" => $imageUpload ? 'true' : null,
        'aria-required' => $isRequired ? 'true' : null,
        'aria-invalid' => $hasErrors ? 'true' : null,
        'data-invalid' => $hasErrors ? true : null,
    ], $attributes, $stimulus, except: ['required'], protectedPrefixes: [
        "data-{$controller}-id-",
        "data-{$controller}-placeholder-",
        "data-{$controller}-editable-",
        "data-{$controller}-output-",
        "data-{$controller}-editor-class-",
        "data-{$controller}-image-upload-",
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
        data-{{ $controller }}-target="input"
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
                    <x-hw::icon name="{{ $button['icon'] }}" aria-hidden="true" />
                </button>
            @endforeach
        </div>
    @else
        {{ $slot ?? '' }}
    @endif

    <div data-slot="rich-text-editor" data-{{ $controller }}-target="editor"></div>
</div>
