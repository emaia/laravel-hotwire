@aware(['name' => null, 'id' => null, 'errorKey' => null, 'required' => false])

@php
    extract($compute($name, $id, $errorKey, $required, $errors, $attributes));
    // Escape `\` and `'` so an id containing either still produces a valid CSS attribute selector.
    $escapedId = addcslashes($resolvedId, "\\'");
    $outletSelector = '[data-'.$identifier."-id-value='".$escapedId."']";
@endphp

<div
    {{ $attributes->except(['data-controller', 'required'])->merge(['class' => trim('hwc-rich-text '.$class)]) }}
    data-controller="{{ $dataController }}"
    data-{{ $identifier }}-id-value="{{ $resolvedId }}"
    @if ($placeholder !== null) data-{{ $identifier }}-placeholder-value="{{ $placeholder }}" @endif
    @if (! $editable) data-{{ $identifier }}-editable-value="false" @endif
    @if ($output !== 'html') data-{{ $identifier }}-output-value="{{ $output }}" @endif
    @if ($editorClass !== '') data-{{ $identifier }}-editor-class-value="{{ $editorClass }}" @endif
    @if ($imageUpload) data-{{ $identifier }}-image-upload-value="true" @endif
    @if ($isRequired) aria-required="true" @endif
    @if ($hasErrors) aria-invalid="true" data-invalid @endif
>
    {{-- The synced textarea carries `aria-required` but NOT the HTML `required` attr: a `hidden`
         form control that can't be focused triggers Chrome's "An invalid form control is not
         focusable" warning and silently blocks submit with no visible tooltip. Validation lives
         server-side (Laravel `required`), and `[data-invalid]` on the wrapper handles the visual.
         See "Required + client-side validation" in the component docs for a JS opt-in. --}}
    <textarea
        @if ($name) name="{{ $name }}" @endif
        data-{{ $identifier }}-target="input"
        @if ($isRequired) aria-required="true" @endif
        @if ($hasErrors) aria-invalid="true" @endif
        @if ($inputClass !== '') class="{{ $inputClass }}" @else hidden @endif
    >{{ $resolvedValue }}</textarea>

    @if ($toolbar)
        <div
            class="hwc-rich-text-toolbar"
            role="toolbar"
            aria-label="Formatting"
            data-controller="rich-text-toolbar"
            data-rich-text-toolbar-editor-value="{{ $outletSelector }}"
        >
            <button type="button" data-action="click->rich-text-toolbar#bold" data-rich-text-toolbar-target="bold" aria-label="Bold"><strong>B</strong></button>
            <button type="button" data-action="click->rich-text-toolbar#italic" data-rich-text-toolbar-target="italic" aria-label="Italic"><em>I</em></button>
            <button type="button" data-action="click->rich-text-toolbar#underline" data-rich-text-toolbar-target="underline" aria-label="Underline"><u>U</u></button>
            <button type="button" data-action="click->rich-text-toolbar#heading" data-rich-text-toolbar-target="heading" data-level="1" aria-label="Heading 1">H1</button>
            <button type="button" data-action="click->rich-text-toolbar#heading" data-rich-text-toolbar-target="heading" data-level="2" aria-label="Heading 2">H2</button>
            <button type="button" data-action="click->rich-text-toolbar#heading" data-rich-text-toolbar-target="heading" data-level="3" aria-label="Heading 3">H3</button>
            <button type="button" data-action="click->rich-text-toolbar#bulletList" data-rich-text-toolbar-target="bulletList" aria-label="Bullet list">&bull;</button>
            <button type="button" data-action="click->rich-text-toolbar#orderedList" data-rich-text-toolbar-target="orderedList" aria-label="Numbered list">1.</button>
            <button type="button" data-action="click->rich-text-toolbar#blockquote" data-rich-text-toolbar-target="blockquote" aria-label="Quote">&ldquo;</button>
            <button type="button" data-action="click->rich-text-toolbar#codeBlock" data-rich-text-toolbar-target="codeBlock" aria-label="Code block">&lt;/&gt;</button>
            <button type="button" data-action="click->rich-text-toolbar#link" data-rich-text-toolbar-target="link" aria-label="Link">Link</button>
            <button type="button" data-action="click->rich-text-toolbar#undo" aria-label="Undo">&larr;</button>
            <button type="button" data-action="click->rich-text-toolbar#redo" aria-label="Redo">&rarr;</button>
        </div>
    @else
        {{ $slot ?? '' }}
    @endif

    <div data-{{ $identifier }}-target="editor" class="hwc-rich-text-editor"></div>
</div>
