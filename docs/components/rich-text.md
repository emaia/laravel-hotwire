# `<x-hwc::rich-text>`

Renders a Tiptap-backed rich text editor with a hidden textarea synced to the form, an optional
default toolbar, and Stimulus events for app-side integration. Wraps the
[`rich-text`](../controllers/rich-text.md) and [`rich-text-toolbar`](../controllers/rich-text-toolbar.md)
controllers — see those for the runtime side.

## Quick example

```blade
{{-- Simplest: default toolbar, HTML output --}}
<x-hwc::rich-text name="content" />

{{-- Edit form with initial content and a placeholder --}}
<x-hwc::rich-text
    name="content"
    placeholder="Write something…"
    :content="$post->content"
/>

{{-- Read-only preview --}}
<x-hwc::rich-text name="preview" :editable="false" :content="$post->content" />

{{-- JSON output (store as ProseMirror JSON) --}}
<x-hwc::rich-text name="content" output="json" :content="$post->content_json" />

{{-- Image upload enabled; the app listens for rich-text:image-upload --}}
<x-hwc::rich-text name="content" :image-upload="true" />

{{-- Custom toolbar via slot --}}
<x-hwc::rich-text name="content" :toolbar="false">
    {{-- your own <div data-controller="rich-text-toolbar"> here --}}
</x-hwc::rich-text>
```

## Props

| Prop           | Type             | Default       | Description                                                                                                  |
|----------------|------------------|---------------|--------------------------------------------------------------------------------------------------------------|
| `name`         | `?string`        | `null`        | Used for the textarea's `name` and to derive the Stimulus id when `id` is omitted. Omit for a standalone editor that isn't part of a form submission. Inherited from `<x-hwc::field>` via `@aware` when absent. |
| `id`           | `?string`        | derived       | Stable identifier used in the toolbar's outlet selector. Defaults to `\Emaia\LaravelHotwire\Support\FieldKey::toId($name)` (so `user[bio]` becomes `user-bio`); falls back to a generated `hwc-rich-text-<uniqid>` when both `name` and `id` are absent. Inherited from `<x-hwc::field>` via `@aware` when absent. |
| `content`      | `?string`        | `null`        | Initial HTML (or JSON when `output="json"`). On a request with validation errors, `old()` takes precedence.  |
| `errorKey`     | `?string`        | derived       | Validation key for `old()` and error lookups. Derived from `name` (e.g. `user.bio` from `user[bio]`); override only when the validation key doesn't match the field name. Inherited from `<x-hwc::field>` via `@aware` when absent. |
| `placeholder`  | `?string`        | `null`        | Empty-state text. When set, adds the Tiptap Placeholder extension.                                           |
| `editable`     | `bool`           | `true`        | Set to `false` for a read-only editor.                                                                       |
| `output`       | `string`         | `'html'`      | `html` writes serialized HTML into the textarea; `json` writes `JSON.stringify`'d ProseMirror JSON.          |
| `toolbar`      | `bool`           | `true`        | Render the default toolbar. Pass `false` to use a custom one through the slot.                               |
| `imageUpload`  | `bool`           | `false`       | Intercept image paste/drop and dispatch `rich-text:image-upload` for the app to handle.                      |
| `old`          | `bool`           | `true`        | Honor `old()` for the initial value (re-populates after a failed validation).                                |
| `class`        | `string`         | `''`          | Merged on the wrapper element.                                                                               |
| `inputClass`   | `string`         | `''`          | CSS class for the synced textarea. Empty (default) renders the textarea with the `hidden` attribute (drop-in for the old hidden input). Set a class to drop `hidden` and style the textarea — useful as a no-JS fallback or for a "view source" mode. |
| `editorClass`  | `string`         | `''`          | CSS class applied to the editor's `.ProseMirror` contenteditable (forwarded into Tiptap's `editorProps.attributes.class`). Typical pick on a Tailwind project: `'prose prose-sm focus:outline-none'`. |
| `controller`   | `string`         | `'rich-text'` | Stimulus identifier — swap for a subclass when you need different extensions or behavior.                    |

When `name` is omitted, the textarea renders without a `name` attribute and the editor's value
isn't included in form submissions — useful for standalone editors (search-as-rich-text, comment
draft, etc.). Most uses of the component pass a `name`.

### Inside a `<x-hwc::field>`

When nested in a field, the rich text component inherits `name`, `id`, and `errorKey` from the
field via `@aware`, so you don't repeat them:

```blade
<x-hwc::field name="bio" label="Bio" error description="Tell us about yourself">
    <x-hwc::rich-text :content="$user->bio" placeholder="Type here…" />
</x-hwc::field>
```

An explicit prop on the child always wins over the field-provided value, so you can override one
attribute without losing the others.

## DOM shape

The component renders:

```html
<div data-controller="rich-text" data-rich-text-id-value="content" …>
    <textarea hidden name="content" data-rich-text-target="input">…</textarea>

    {{-- omitted when :toolbar="false" --}}
    <div data-controller="rich-text-toolbar"
         data-rich-text-toolbar-rich-text-outlet="[data-rich-text-id-value='content']"
         …>
        <button data-action="click->rich-text-toolbar#bold" …>B</button>
        …
    </div>

    <div data-rich-text-target="editor" class="hwc-rich-text-editor"></div>
</div>
```

## Initial content + `old()`

The textarea is the source of truth for content. The component populates it from `content`
first, then overrides with the last submitted value from `old()` when validation fails — same
behavior you get on the package's other form components:

```blade
<x-hwc::rich-text name="content" :content="$post->content" />
```

If validation rejects the form, the page re-renders with the user's draft instead of `$post->content`.
Disable with `:old="false"` if you need the prop value to always win.

### Empty-state normalization

Tiptap represents an empty document as `<p></p>` — a non-empty string that would bypass Laravel's
`required` validation. The controller checks `editor.isEmpty` on every change and writes `""` to
the textarea in that case, so server-side `required` sees an empty field instead of placeholder
markup. The same check runs on mount, so a leftover `<p></p>` from a previous submission's `old()`
(or from data stored under the old behavior) is cleared before the next submit.

This applies to both `output="html"` and `output="json"` — Tiptap's "empty doc" JSON
(`{"type":"doc","content":[{"type":"paragraph"}]}`) is normalized to `""` the same way.

## Default toolbar

The default toolbar exposes the buttons most editors need: bold, italic, underline, H1/H2/H3,
bullet list, numbered list, blockquote, code block, link, undo, redo. Each button is a
`<button type="button">` with a text label (no icons, so no extra dependency) and an `aria-label`
for screen readers. Restyle freely via CSS — the buttons live inside `.hwc-rich-text-toolbar`.

When you need a different set of buttons, drop the default and render your own through the slot:

```blade
<x-hwc::rich-text name="content" :toolbar="false">
    <div data-controller="rich-text-toolbar"
         data-rich-text-toolbar-rich-text-outlet="[data-rich-text-id-value='content']"
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
</x-hwc::rich-text>
```

See the [toolbar controller docs](../controllers/rich-text-toolbar.md) for the full action and
target reference.

## Styling the editor content

The editor renders inside a `.ProseMirror` contenteditable div that Tiptap mounts under
`data-rich-text-target="editor"`. On a Tailwind project, Preflight strips default heading/list
styles, so a fresh editor will render headings, paragraphs and lists as visually flat text. The
fix is to give the contenteditable a class — typically `@tailwindcss/typography`'s `prose`:

```blade
<x-hwc::rich-text
    name="content"
    editorClass="prose prose-sm focus:outline-none max-w-none"
/>
```

`editorClass` lands on `editorProps.attributes.class` (the Tiptap-recommended hook), so the
class ends up on the actual `.ProseMirror` node. Use it with any styling system — Tailwind
typography, your own stylesheet, plain CSS classes; the package stays unopinionated about
which one.

If you'd rather style externally (e.g. a shared `app.css` rule for every editor), target the
existing `.hwc-rich-text-editor .ProseMirror` selector and skip the prop.

## Styling the synced textarea

By default the synced textarea carries the `hidden` HTML attribute — the editor renders inside the
`data-rich-text-target="editor"` div and the textarea only ships the value with the form. Pass
`inputClass` to drop the `hidden` attribute and style the textarea — handy for a no-JS fallback
(if Tiptap fails to load, the user can still type into the textarea) or a "view source" toggle:

```blade
<x-hwc::rich-text
    name="content"
    inputClass="mt-2 block w-full rounded border px-3 py-2 font-mono text-sm"
/>
```

The textarea sits before the editor div in the DOM, so it'll appear above the Tiptap view when
visible. Hide one or the other with your own CSS if you only want one rendered at a time.

## Multiple editors on the same page

Each editor needs a distinct id-value so the toolbar's outlet selector picks the right one. The
component derives the id from `name`, so two editors with different names just work:

```blade
<x-hwc::rich-text name="summary" />
<x-hwc::rich-text name="body" />
```

When you need to override the derivation (e.g. two editors with the same name in different
contexts), pass `id` explicitly.

## Controller swap

Swap to a subclass when you need different extensions, a different image-upload pipeline, or any
other behavior change without forking the controller:

```blade
<x-hwc::rich-text name="content" controller="rich-text-extended" />
```

The swap renames the data attributes (so `data-rich-text-id-value` becomes
`data-rich-text-extended-id-value`) and swaps the `data-controller`. When you swap, the default
toolbar still references the `rich-text` outlet, so you'll usually want to pair the swap with
`:toolbar="false"` and render a toolbar configured for the new identifier.

## Server-side rendering of saved content

The component is meant for the editing UI. For *displaying* saved content elsewhere on the site —
posts, comments, descriptions — render the HTML directly:

```blade
{!! $post->content !!}
```

You don't need a controller, a toolbar, or Tiptap on the page to show stored content. This keeps
the public side dependency-free.

## Security

The editor sends client-generated HTML (or JSON) to your server. **Sanitize before storing or
re-rendering.** Tiptap's extensions are conservative by default, but a malicious client can still
post anything to your endpoint — never trust the payload. Common picks for Laravel:

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

Enable image upload by passing `:image-upload="true"`. The controller intercepts paste/drop of
image files, calls `preventDefault`, and dispatches `rich-text:image-upload` with `{ file, editor }`.
The app handles the upload and inserts the resulting URL. See
[the recipe](../recipes/rich-text-image-upload.md) for a full Laravel example.

## See also

- [Rich text controller](../controllers/rich-text.md)
- [Toolbar controller](../controllers/rich-text-toolbar.md)
- [Image-upload recipe](../recipes/rich-text-image-upload.md)
- [Tiptap reference](https://tiptap.dev/)
