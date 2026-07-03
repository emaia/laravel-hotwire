# Rich text

Tiptap-backed rich text editor. Mounts a Tiptap `Editor` on a target div, syncs serialized output
into a hidden textarea on every change, and dispatches Stimulus events for ready/change/focus/blur
plus a paired `state` event for toolbars to resync. The companion
[`rich-text-toolbar`](rich-text-toolbar.md) controller wires the default buttons via a Stimulus
outlet; you can also drive the editor from anywhere by accessing the public API on the controller
instance.

**Identifier:** `rich-text`
**Install:** `php artisan hotwire:controllers rich-text`
**npm deps:** `@tiptap/core ^2.0`, `@tiptap/starter-kit ^2.0`, `@tiptap/extension-placeholder ^2.0`,
`@tiptap/extension-link ^2.0`, `@tiptap/extension-underline ^2.0`

## Requirements

- The five Tiptap packages listed above. `hotwire:check` reports them as required when the
  controller is in use.
- A hidden `<textarea data-rich-text-target="input">` is the source of truth for content. It
  receives the editor's HTML (or JSON) on every change so the form submission carries the value.
  An `<input type="hidden">` works too — the controller only touches `.value`, so the element
  type doesn't matter. The Blade component ships a `<textarea>` because it can be made visible
  via `inputClass` as a no-JS fallback or "view source" surface.

## Targets

| Target   | Description                                                              |
|----------|--------------------------------------------------------------------------|
| `editor` | The div Tiptap mounts the contenteditable into. **Required.**            |
| `input`  | The synced field (`<textarea hidden>` or `<input type="hidden">`) the controller writes the editor's output into. Optional but typical. |

## Values

| Value         | Type    | Default | Description                                                                                  |
|---------------|---------|---------|----------------------------------------------------------------------------------------------|
| `id`          | String  | `""`    | Stable identifier used by toolbar outlet selectors. Required when pairing with a toolbar.    |
| `placeholder` | String  | `""`    | Text shown when the editor is empty. Adds the `@tiptap/extension-placeholder` to the stack.  |
| `editable`    | Boolean | `true`  | When `false`, the editor renders in read-only mode.                                          |
| `output`      | String  | `html`  | Serialization for the synced field: `html` (default) writes the rendered HTML; `json` writes Tiptap's ProseMirror JSON via `JSON.stringify`. |
| `editorClass` | String  | `""`    | CSS class applied to the `.ProseMirror` contenteditable, via Tiptap's `editorProps.attributes.class`. Typical pick on Tailwind: `prose prose-sm focus:outline-none`. |
| `imageUpload` | Boolean | `false` | When `true`, paste/drop of image files is intercepted and re-dispatched as `rich-text:image-upload` for the app to handle. |

## Events

| Event                    | Detail                       | Description                                              |
|--------------------------|------------------------------|----------------------------------------------------------|
| `rich-text:ready`        | `{ editor }`                 | Fires once after the Tiptap editor is constructed.        |
| `rich-text:state`        | `{ editor }`                 | Fires after connect and on every selection or content change. Toolbars listen for this to resync `is-active` states. |
| `rich-text:change`       | `{ html, json }`             | Fires when the document changes (typing, command, paste). |
| `rich-text:focus`        | —                            | Fires when the editor gains focus.                        |
| `rich-text:blur`         | —                            | Fires when the editor loses focus.                        |
| `rich-text:image-upload` | `{ file, editor }`           | Fires per image dropped or pasted when `image-upload` is enabled. The handler is responsible for uploading the file and inserting the resulting URL via `editor.chain().focus().setImage({ src: url }).run()`. |

All events are dispatched under the fixed `rich-text:` prefix — Stimulus's `dispatch(name,
{ prefix })` option pins the event name regardless of the registered identifier. A subclass
mounted under `controller="rich-text-full"` still emits `rich-text:state`, `rich-text:ready`,
etc. The toolbar (and any app-side listener) can stay generic.

## Public API

The controller exposes a few properties and methods for app code to drive the editor without
reaching into Tiptap directly:

```js
const controller = application.getControllerForElementAndIdentifier(el, "rich-text");

controller.editor;            // The underlying Tiptap Editor instance
controller.html;              // Current HTML string
controller.json;              // Current ProseMirror JSON
controller.setContent(html);  // Replace the document (emits change → input sync)
controller.clear();           // Empty the document (emits change → input sync)
controller.focus();           // Move the cursor into the editor
```

## Basic usage (raw, without the Blade component)

```html
<div data-controller="rich-text"
     data-rich-text-id-value="content">
    <textarea hidden name="content" data-rich-text-target="input"></textarea>
    <div data-rich-text-target="editor"></div>
</div>
```

This is the minimum: a hidden textarea for the form payload and a target div for Tiptap. The
[`<hw:rich-text>`](../components/rich-text.md) Blade component scaffolds this for you and pairs
it with the default toolbar.

## Placeholder

```html
<div data-controller="rich-text"
     data-rich-text-id-value="content"
     data-rich-text-placeholder-value="Write something…">
    <textarea hidden name="content" data-rich-text-target="input"></textarea>
    <div data-rich-text-target="editor"></div>
</div>
```

The placeholder shows when the editor is empty and disappears as soon as the user types.

## Read-only mode

Set `editable` to `false` to render saved content without an editing affordance — useful for
preview tabs or audit views that reuse the same styling as the editor:

```html
<div data-controller="rich-text"
     data-rich-text-id-value="preview"
     data-rich-text-editable-value="false">
    <textarea hidden data-rich-text-target="input">{{ $post->content }}</textarea>
    <div data-rich-text-target="editor"></div>
</div>
```

For a pure display (no editor styling), render the HTML directly with `{!! $content !!}` instead.

## JSON output

When you store ProseMirror JSON instead of HTML, set `output` to `json`. The textarea will
contain a `JSON.stringify`d snapshot:

```html
<div data-controller="rich-text"
     data-rich-text-id-value="content"
     data-rich-text-output-value="json">
    <textarea hidden name="content" data-rich-text-target="input"></textarea>
    <div data-rich-text-target="editor"></div>
</div>
```

The initial value in the textarea can be either a JSON string or an HTML string — the
controller `JSON.parse`s when the document looks like JSON, falling back to HTML.

## Extensions hook (subclass)

The default extension stack is StarterKit + Link + Underline (plus Placeholder when the
`placeholder` value is set). To add or swap extensions without forking, subclass the controller
and override `extensions(options)`:

```js
import RichTextController from "@hotwire/rich_text_controller.js";
import { defaultExtensions } from "@hotwire/_rich_text_editor.js";
import { Table } from "@tiptap/extension-table";
import { TableRow } from "@tiptap/extension-table-row";
import { TableCell } from "@tiptap/extension-table-cell";
import { TableHeader } from "@tiptap/extension-table-header";

export default class extends RichTextController {
    extensions(options) {
        return [
            ...defaultExtensions(options),
            Table.configure({ resizable: true }),
            TableRow,
            TableCell,
            TableHeader,
        ];
    }
}
```

`options` is `{ placeholder }` — pass it back to `defaultExtensions` so the Placeholder extension
still picks up the configured text. Returning `null` (the default) uses the built-in stack.

`hotwire:make-controller` is the easiest way to scaffold this subclass — pick a kebab-case name
like `rich-text-extended` and reference it from the component as `controller="rich-text-extended"`.

## Image upload

When `image-upload` is enabled, the editor intercepts paste and drop events that carry image files,
calls `preventDefault`, and dispatches `rich-text:image-upload` with the file. The app is
responsible for the upload + the `setImage` insertion:

```js
document.addEventListener("rich-text:image-upload", async (event) => {
    const { file, editor } = event.detail;

    const body = new FormData();
    body.append("image", file);

    const response = await fetch("/uploads", { method: "POST", body });
    const { url } = await response.json();

    editor.chain().focus().setImage({ src: url }).run();
});
```

See [the image-upload recipe](../recipes/rich-text-image-upload.md) for the matching Laravel route
and storage wiring.

You can also handle the upload in a subclass by overriding the wrapper's `onImageDrop` callback —
useful when you want to keep the logic colocated with the editor:

```js
import RichTextController from "@hotwire/rich_text_controller.js";

export default class extends RichTextController {
    async handleImageUpload(file) {
        const body = new FormData();
        body.append("image", file);
        const { url } = await fetch("/uploads", { method: "POST", body }).then((r) => r.json());
        this.editor.chain().focus().setImage({ src: url }).run();
    }
}
```

## Lifecycle

- `connect()` reads the synced field's value, instantiates `RichTextEditor`, wires the callbacks
  that drive field sync and event dispatch, and emits `rich-text:ready` + `rich-text:state`.
- `disconnect()` destroys the underlying Tiptap editor.

The controller scopes every read/write to `this.element`, `this.editorTarget`, and `this.inputTarget`
so it stacks cleanly with other controllers on the same div.

## See also

- [Toolbar controller](rich-text-toolbar.md)
- [Component documentation](../components/rich-text.md)
- [Image-upload recipe](../recipes/rich-text-image-upload.md)
- [Tiptap docs](https://tiptap.dev/)
