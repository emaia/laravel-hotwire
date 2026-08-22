# `<hw:rich-text>`

Renders a Tiptap-backed rich text editor with a hidden textarea synced to the form, an optional default toolbar, and
Stimulus events for app-side integration. Wraps the
[`rich-text`](../controllers/rich-text.md) and [`rich-text-toolbar`](../controllers/rich-text-toolbar.md)
controllers — see those for the runtime side.

## Quick example

```blade
{{-- Simplest: default toolbar, HTML output --}}
<hw:rich-text name="content" />

{{-- Edit form with initial content and a placeholder --}}
<hw:rich-text
    name="content"
    placeholder="Write something…"
    :value="$post->content"
/>

{{-- Read-only preview --}}
<hw:rich-text name="preview" :editable="false" :value="$post->content" />

{{-- JSON output (store as ProseMirror JSON) --}}
<hw:rich-text name="content" output="json" :value="$post->content_json" />

{{-- Image upload enabled; the app listens for rich-text:image-upload --}}
<hw:rich-text name="content" :image-upload="true" />

{{-- Compatibility toolbar with headings, underline, quote, code block, undo/redo, etc. --}}
<hw:rich-text name="content" toolbar="classic" />

{{-- Custom packaged toolbar buttons --}}
<hw:rich-text name="content" toolbar="bold italic link bullet-list ordered-list" />

{{-- Custom toolbar via slot --}}
<hw:rich-text name="content" :toolbar="false">
    {{-- your own <div data-controller="rich-text-toolbar"> here --}}
</hw:rich-text>
```

## Props

| Prop          | Type                        | Default       | Description                                                                                                                                                                                                                                                                                                          |
|---------------|-----------------------------|---------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `name`        | `?string`                   | `null`        | Used for the textarea's `name` and to derive the Stimulus id when `id` is omitted. Omit for a standalone editor that isn't part of a form submission. Inherited from scoped Field context when absent.                                                                                                             |
| `id`          | `?string`                   | derived       | Stable identifier used in the toolbar's outlet selector. Defaults to `\Emaia\LaravelHotwire\Support\FieldKey::toId($name)` (so `user[bio]` becomes `user-bio`); falls back to a generated `hw-rich-text-<uniqid>` when both `name` and `id` are absent. Inherited from scoped Field context when absent.           |
| `value`       | `mixed`                     | `null`        | Initial HTML (or JSON when `output="json"`). Cast to string in the view. On a request with validation errors, `old()` takes precedence.                                                                                                                                                                              |
| `errorKey`    | `?string`                   | derived       | Validation key for `old()` and error lookups. Derived from `name` (e.g. `user.bio` from `user[bio]`); override only when the validation key doesn't match the field name. Inherited from scoped Field context when absent.                                                                                         |
| `placeholder` | `?string`                   | `null`        | Empty-state text. When set, adds the Tiptap Placeholder extension.                                                                                                                                                                                                                                                   |
| `editable`    | `bool`                      | `true`        | Set to `false` for a read-only editor.                                                                                                                                                                                                                                                                               |
| `required`    | `bool`/HTML attr            | `false`       | Marks the field as required for a11y (`aria-required="true"` on wrapper + textarea). The HTML `required` attribute is **intentionally not emitted** — see [Required + client-side validation](#required--client-side-validation). Inherited from scoped Field context.                                                |
| `output`      | `string`                    | `'html'`      | `html` writes serialized HTML into the textarea; `json` writes `JSON.stringify`'d ProseMirror JSON.                                                                                                                                                                                                                  |
| `toolbar`     | `bool\|string\|array\|null` | `true`        | Render packaged toolbar buttons. `true`, `null`, and `basic` render bold, italic, link, bullet list, ordered list. `classic` renders the previous broad toolbar plus `strike`, inline `code`, and `horizontal-rule`. Pass a string/array of button keys for a custom set, or `false` to render slot content instead. |
| `imageUpload` | `bool`                      | `false`       | Intercept image paste/drop and dispatch `rich-text:image-upload` for the app to handle.                                                                                                                                                                                                                              |
| `old`         | `bool`                      | `true`        | Honor `old()` for the initial value (re-populates after a failed validation).                                                                                                                                                                                                                                        |
| `class`       | `string`                    | `''`          | Merged on the wrapper element.                                                                                                                                                                                                                                                                                       |
| `inputClass`  | `string`                    | `''`          | CSS class for the synced textarea. Empty (default) renders the textarea with the `hidden` attribute (drop-in for the old hidden input). Set a class to drop `hidden` and style the textarea — useful as a no-JS fallback or for a "view source" mode.                                                                |
| `editorClass` | `string`                    | `''`          | CSS class applied to the editor's `.ProseMirror` contenteditable (forwarded into Tiptap's `editorProps.attributes.class`). Typical pick on a Tailwind project: `'prose prose-sm focus:outline-none'`.                                                                                                                |
| `controller`  | `string`                    | `'rich-text'` | Stimulus identifier — swap for a subclass when you need different extensions or behavior.                                                                                                                                                                                                                            |

When `name` is omitted, the textarea renders without a `name` attribute and the editor's value isn't included in form
submissions — useful for standalone editors (search-as-rich-text, comment draft, etc.). Most uses of the component pass
a `name`.

### Inside a `<hw:field>`

When nested in a field, the rich text component inherits `name`, `id`, and `errorKey` from scoped Field context, so
you don't repeat them:

```blade
<hw:field name="bio" label="Bio" error description="Tell us about yourself">
    <hw:rich-text :value="$user->bio" placeholder="Type here…" />
</hw:field>
```

An explicit prop on the child always wins over the field-provided value, so you can override one attribute without
losing the others.

## DOM shape

The component renders:

```html

<div data-slot="rich-text" data-controller="rich-text" data-rich-text-id-value="content" …>
    <textarea hidden name="content" data-rich-text-target="input">…</textarea>

    {{-- omitted when :toolbar="false" --}}
    <div data-controller="rich-text-toolbar"
         data-rich-text-toolbar-editor-value="[data-rich-text-id-value='content']"
         …>
        <button data-action="click->rich-text-toolbar#bold" aria-label="Bold" …>
            <svg data-slot="icon">…</svg>
        </button>
        …
    </div>

    <div data-slot="rich-text-editor" data-rich-text-target="editor"></div>
</div>
```

## Initial content + `old()`

The textarea is the source of truth for content. The component populates it from `value`
first, then overrides with the last submitted value from `old()` when validation fails — same behavior you get on the
package's other form components:

```blade
<hw:rich-text name="content" :value="$post->content" />
```

If validation rejects the form, the page re-renders with the user's draft instead of `$post->content`. Disable with
`:old="false"` if you need the prop value to always win.

### Error state

When validation rejects the form, the component marks itself invalid the same way `<hw:input>`,
`<hw:textarea>` and the other form components do — `aria-invalid="true" data-invalid` on the wrapper `<div>`, plus
`aria-invalid="true"` on the synced textarea. Style the error visual on the wrapper so it covers the whole editor
(toolbar + contenteditable + textarea) instead of just the hidden form payload:

```css
[data-slot="rich-text"][data-invalid] {
    border-color: var(--color-danger);
}
```

See the [Styling](#styling) section below for a full recipe using `aria-invalid:*` variants on Tailwind.

`hasErrors` is resolved from `errorKey` (derived from `name` when omitted), so nesting inside
`<hw:field name="bio" error>` propagates the error state through scoped Field context automatically.

### Required + client-side validation

The component accepts `required` (or inherits it from `<hw:field required>`) and emits
`aria-required="true"` on the wrapper and the synced textarea, so screen readers announce the field correctly. The HTML
`required` attribute is **not** emitted, deliberately.

Why: the synced textarea is `hidden` by default, and Chrome refuses to surface validation errors on form controls it
can't focus — submit gets silently blocked with the warning
`An invalid form control with name='X' is not focusable` and **no visible tooltip**. Every established rich-text editor
(TinyMCE, CKEditor, Quill) ran into the same wall and dropped the attribute for the same reason. Server-side `required`
validation + the wrapper's
`data-invalid` visual is the supported path. Use the package's rich-text rule so placeholder markup cannot satisfy the
requirement:

```php
use Emaia\LaravelHotwire\Validation\Rules\RichText;

$request->validate([
    'content' => ['bail', RichText::required(), 'string'],
]);
```

```css
/* app.css — already in the Styling recipe; restated here for clarity */
[data-slot="rich-text"][data-invalid] {
    border-color: var(--color-destructive);
}
```

If you want browser-side blocking (no server round-trip on empty submit), wire it explicitly through the controller's
public API:

```js
// resources/js/controllers/rich_text_form_controller.js
import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static outlets = ["rich-text"];

    submit(event) {
        if (this.hasRichTextOutlet && this.richTextOutlet.editor?.isEmpty) {
            event.preventDefault();
            this.element.dispatchEvent(new CustomEvent("rich-text:empty-submit", {bubbles: true}));
        }
    }
}
```

```blade
<form data-controller="rich-text-form"
      data-rich-text-form-rich-text-outlet="[data-rich-text-id-value='content']"
      data-action="submit->rich-text-form#submit">
    <hw:rich-text name="content" required />
</form>
```

This keeps the package opinion-free: apps that prefer server-only validation pay zero JS; apps that want early stop opt
in with a few lines.

### Server-side content validation

Laravel's built-in `required`, `min`, and `max` rules inspect the serialized HTML string. The rich-text rules inspect
normalized text instead, so tags, semicolon-terminated named/numeric entities, empty paragraphs, and Tiptap placeholders
do not inflate the length:

```php
use Emaia\LaravelHotwire\Validation\Rules\RichText;

$validated = $request->validate([
    'content' => [
        'bail',
        'nullable',
        'string',
        'max:200000',
        RichText::requiredIf(fn (): bool => $request->boolean('published')),
        RichText::min(3),
        RichText::max(500),
    ],
]);
```

`RichText::required()` and a truthy `RichText::requiredIf()` reject missing, null, empty, whitespace-only, and empty
structural markup. The HTML policy recognizes `audio`, `canvas`, `embed`, `hr`, `iframe`, `img`, `object`, `svg`, and
`video` as meaningful non-text content. Their attributes, including image `alt`, do not contribute to text length.

| Content                          | `required()` | `min(1)` | `max(0)` |
|----------------------------------|--------------|----------|----------|
| `<p><br></p>`                    | fails        | skipped  | skipped  |
| `<img src="…">`                 | passes       | fails    | passes   |
| `<div data-youtube-video>…`      | passes       | fails    | passes   |
| `<video src="…"></video>`       | passes       | fails    | passes   |
| `<video>fallback</video>`        | passes       | fails    | passes   |
| empty table                      | fails        | skipped  | skipped  |
| unknown empty custom atom        | fails        | skipped  | skipped  |
| `<p>Text</p>`                    | passes       | passes   | fails    |

`RichText::min()` and `RichText::max()` skip absent or truly blank optional values; they do not make a field required.
Use `required()` plus `min(1)` when a field must contain actual text instead of media alone. Compose the rules with
Laravel's `nullable`, `sometimes`, `string`, and `bail` rules as needed. Default failures use the package's translated
`hotwire::validation.rich_text.required|min|max|invalid` messages.

The built-in English messages work without publishing. To customize them or add another locale, run:

```bash
php artisan vendor:publish --tag=hotwire-translations
```

Publishing creates `lang/vendor/hotwire/en/validation.php`. For an application using `APP_LOCALE=pt_BR`, add
`lang/vendor/hotwire/pt_BR/validation.php` with the same structure:

```php
<?php

return [
    'rich_text' => [
        'required' => 'O campo :attribute deve conter conteúdo.',
        'min' => 'O campo :attribute deve conter no mínimo :min caracteres.',
        'max' => 'O campo :attribute não pode conter mais de :max caracteres.',
        'invalid' => 'O campo :attribute deve conter conteúdo rich text válido.',
    ],
];
```

No call to `trans()` is needed in application code. `RichText::min(10)`, for example, resolves `rich_text.min` in the
current locale and replaces both `:attribute` and `:min` automatically. When a locale file is absent, Laravel uses the
application's fallback locale.

The raw `max:200000` guard runs before HTML parsing because of `bail`. Choose a limit appropriate for your application;
it protects memory independently from the visible-text limit enforced by `RichText::max(500)`.

These rules validate the default `output="html"` format only. They do not auto-detect or validate Tiptap JSON from
`output="json"`.

Use `RichTextContent` directly when application code needs the same normalization outside validation:

```php
use Emaia\LaravelHotwire\Support\RichTextContent;

$content = RichTextContent::fromHtml($post->content);

$content->isBlank();
$content->plainText();
$content->plainTextLength();
```

`plainText()` keeps inline text together and separates blocks and `<br>` elements with normalized line breaks.
`plainTextLength()` is `mb_strlen()` of that normalized value, including those line breaks and any joiners preserved in
visible Unicode text. Media attributes are not text. Fallback children of `audio`, `canvas`, `embed`, `iframe`, `object`,
and `video` are excluded by the package's deterministic text policy, so opaque media-only content has a text length of
zero regardless of fallback rendering context. Captions outside the media element, such as `<figcaption>`, continue to
count.

SVG remains traversable: visible `<text>` contributes to plain text, while SVG `<title>` and `<desc>` metadata do not.
Content made exclusively of whitespace or invisible controls is canonicalized to an empty string. The helper ignores
common non-content elements and editor artifacts, but it does not evaluate CSS visibility and does not sanitize HTML.
Tiptap emits named entities with their terminating semicolon; legacy references that omit it are treated as literal
text.

This policy approximates common Tiptap media nodes from serialized HTML; it cannot reproduce schema-aware
`editor.isEmpty` for every extension. An empty custom atom such as `<div data-type="widget"></div>` is indistinguishable
from empty structural markup, so applications using such nodes need matching application validation. Presence validation
also does not authorize active content: sanitize elements, URLs, iframe origins, SVG, and attributes against the actual
editor schema before persistence or rendering.

### Empty-state normalization

Tiptap represents an empty document as `<p></p>` — a non-empty string that would bypass Laravel's
`required` validation. The controller checks `editor.isEmpty` on every change and writes `""` to the textarea in that
case, so server-side `required` sees an empty field instead of placeholder markup. The same check runs on mount, so a
leftover `<p></p>` from a previous submission's `old()`
(or from data stored under the old behavior) is cleared before the next submit.

This applies to both `output="html"` and `output="json"` — Tiptap's "empty doc" JSON
(`{"type":"doc","content":[{"type":"paragraph"}]}`) is normalized to `""` the same way.

## Toolbar presets

The default toolbar is `basic`: bold, italic, link, bullet list, and numbered list. This keeps the common editor surface
compact while still using the bundled `rich-text-toolbar` controller.

Use `toolbar="classic"` when you want the previous broad toolbar: bold, italic, underline, strike, inline code,
H1/H2/H3, link, bullet list, numbered list, blockquote, code block, horizontal rule, undo, and redo.

Pass a string or array of button keys when you want only selected packaged buttons:

```blade
<hw:rich-text name="content" toolbar="bold italic link" />

<hw:rich-text
    name="content"
    :toolbar="['bold', 'italic', 'heading-2', 'blockquote']"
/>
```

Supported button keys are: `bold`, `italic`, `underline`, `strike`, `code`, `heading-1`,
`heading-2`, `heading-3`, `link`, `bullet-list`, `ordered-list`, `blockquote`, `code-block`,
`horizontal-rule`, `undo`, and `redo`. Unsupported keys are ignored.

There is no `full` preset. Buttons such as alignment, highlight, image insertion, task lists, and tables require Tiptap
extensions that are not loaded by the base editor, so they stay in app-owned custom toolbars. Each packaged button is a
`<button type="button">` with a Lucide-style icon and stable `aria-label`.

When you need a different set of buttons, drop the default and render your own through the slot:

```blade
<hw:rich-text name="content" :toolbar="false">
    <div data-controller="rich-text-toolbar"
         data-rich-text-toolbar-editor-value="[data-rich-text-id-value='content']"
         class="my-toolbar">
        <button type="button"
                data-action="click->rich-text-toolbar#heading"
                data-rich-text-toolbar-target="heading"
                data-rich-text-toolbar-level-param="2"
                data-level="2">H2</button>

        <button type="button"
                data-action="click->rich-text-toolbar#bold"
                data-rich-text-toolbar-target="bold">B</button>

        <button type="button"
                data-action="click->rich-text-toolbar#italic"
                data-rich-text-toolbar-target="italic">I</button>
    </div>
</hw:rich-text>
```

See the [toolbar controller docs](../controllers/rich-text-toolbar.md) for the full action and target reference. To add
buttons for a Tiptap extension you turned on (Table, TaskList, etc.), subclass the toolbar and spread `activeStates` —
see
[Extending the toolbar](../controllers/rich-text-toolbar.md#extending-the-toolbar-table-recipe).

## Styling hooks

The component exposes stable `data-slot` hooks and prop-based knobs; the visual is controlled by presets or your app
CSS.

### Stable hooks

| Hook                                          | Element                                           |
|-----------------------------------------------|---------------------------------------------------|
| `[data-slot="rich-text"]`                     | The outer wrapper                                 |
| `[data-slot="rich-text-toolbar"]`             | The default toolbar row                           |
| `[data-slot="rich-text-toolbar-button"]`      | A default toolbar button                          |
| `[data-slot="rich-text-editor"]`              | The div Tiptap mounts the contenteditable into    |
| `[data-slot="rich-text-editor"] .ProseMirror` | The contenteditable surface itself                |
| `[data-slot="rich-text"][data-invalid]`       | The wrapper when the field has a validation error |
| `[data-slot="rich-text-input"]`               | The synced textarea                               |

### Knobs

- `editorClass` → lands on `editorProps.attributes.class` (the contenteditable). Best place for typography (`prose`,
  custom heading sizes, code-block styling).
- `inputClass` → goes on the synced textarea. When set, the textarea drops its `hidden` attribute so the class actually
  renders — useful as a no-JS fallback or "view source" toggle.
- `class` → merged on the wrapper.

### Recipes

**Tailwind + `@tailwindcss/typography`** — content rendered with semantic heading/list visuals:

```blade
<hw:rich-text
    name="content"
    editorClass="prose prose-sm focus:outline-none max-w-none"
/>
```

**Preset style** — the preset styles the editor, toolbar, active states, content, focus ring, and validation errors
through the component's semantic hooks. No additional application CSS is required.

```blade
<hw:field name="description" label="Description">
    <hw:rich-text :value="$task->description" />
</hw:field>
```

The wrapper carries `aria-invalid="true"` automatically when validation flags the field, so the destructive ring appears
without extra wiring. Nesting inside `<hw:field required>` propagates
`required` to the textarea through scoped Field context.

**Vanilla CSS** — same idea, no preprocessor:

```css
[data-slot="rich-text"] {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
}

[data-slot="rich-text"][data-invalid] {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
}

[data-slot="rich-text"] [data-slot="rich-text-toolbar"] {
    display: flex;
    gap: 4px;
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
}

[data-slot="rich-text"] [data-slot="rich-text-editor"] .ProseMirror {
    min-height: 8rem;
    padding: 12px;
    outline: none;
}

[data-slot="rich-text"] [data-slot="rich-text-editor"] .ProseMirror h1 {
    font-size: 1.5rem;
    font-weight: 700;
}

[data-slot="rich-text"] [data-slot="rich-text-editor"] .ProseMirror ul {
    list-style: disc;
    padding-left: 1.25rem;
}
```

### Exposing the textarea as a "view source" surface

By default the synced textarea has the `hidden` HTML attribute — the editor renders inside the
`data-rich-text-target="editor"` div and the textarea only ships the value with the form. Passing
`inputClass` drops the `hidden` attribute so the textarea becomes visible (and styleable) — handy as a no-JS fallback or
a debug surface alongside the rich editor:

```blade
<hw:rich-text
    name="content"
    inputClass="mt-2 block w-full rounded border px-3 py-2 font-mono text-sm"
/>
```

The textarea sits before the editor div in the DOM, so it'll appear above the Tiptap view when visible.

## Multiple editors on the same page

Each editor needs a distinct id-value so the toolbar's outlet selector picks the right one. The component derives the id
from `name`, so two editors with different names just work:

```blade
<hw:rich-text name="summary" />
<hw:rich-text name="body" />
```

When you need to override the derivation (e.g. two editors with the same name in different contexts), pass `id`
explicitly.

## Controller swap

Swap to a subclass when you need different extensions, a different image-upload pipeline, or any other behavior change
without forking the controller:

```blade
<hw:rich-text name="content" controller="rich-text-extended" />
```

The swap renames the data attributes (so `data-rich-text-id-value` becomes
`data-rich-text-extended-id-value`) and swaps the `data-controller`. The packaged toolbar follows the swapped identifier
in its outlet selector.

## Server-side rendering of saved content

The component is meant for the editing UI. For *displaying* saved content elsewhere on the site — posts, comments,
descriptions — render the HTML directly:

```blade
{!! $post->content !!}
```

You don't need a controller, a toolbar, or Tiptap on the page to show stored content. This keeps the public side
dependency-free.

## Security

The editor sends client-generated HTML (or JSON) to your server. **Sanitize before storing or re-rendering.** Tiptap's
extensions are conservative by default, but a malicious client can still post anything to your endpoint — never trust
the payload. Common picks for Laravel:

- [`mews/purifier`](https://github.com/mewebstudio/Purifier) — HTMLPurifier wrapper, configurable allowlist
- A custom allowlist using `HTMLPurifier_Config` directly

A typical pattern:

```php
public function update(Request $request, Post $post)
{
    $clean = clean($request->input('content'), [
        'HTML.Allowed' => 'p,br,strong,em,u,ul,ol,li,blockquote,a[href|title],h1,h2,h3,pre,code',
    ]);

    $post->update(['content' => $clean]);

    return to_route('posts.show', $post);
}
```

The component doesn't sanitize for you — the editor's job is editing, the app decides what to keep.

## Image upload

Enable image upload by passing `:image-upload="true"`. The controller intercepts paste/drop of image files, calls
`preventDefault`, and dispatches `rich-text:image-upload` with `{ file, editor }`. The app handles the upload and
inserts the resulting URL. See
[the recipe](../recipes/rich-text-image-upload.md) for a full Laravel example.

## See also

- [Rich text controller](../controllers/rich-text.md)
- [Toolbar controller](../controllers/rich-text-toolbar.md)
- [Image-upload recipe](../recipes/rich-text-image-upload.md)
- [Tiptap reference](https://tiptap.dev/)
