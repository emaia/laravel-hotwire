# Rich text toolbar

Optional toolbar paired with the [`rich-text`](rich-text.md) controller via a Stimulus outlet.
Provides the standard formatting actions (bold, italic, underline, lists, blockquote, code block,
link, headings, undo, redo) and keeps each button's `is-active` class + `aria-pressed` attribute
in sync with the editor's current selection.

**Identifier:** `rich-text-toolbar`
**Install:** `php artisan hotwire:controllers rich-text-toolbar`

## Requirements

- A `rich-text` controller registered on a sibling element (or anywhere in the document) that the
  outlet selector can find.

## Editor lookup

The toolbar takes a CSS selector that points at the editor element via the `editor` value:

```html
<div data-controller="rich-text-toolbar"
     data-rich-text-toolbar-editor-value="[data-rich-text-id-value='content']">
```

On `connect()`, the toolbar resolves the editor element, walks its `data-controller` attribute
looking for a controller that exposes an `editor` getter (the rich-text controller or any
subclass), caches the Tiptap editor instance, and listens for `rich-text:state` events on that
element to keep the active-button reflection in sync. The lookup is **identifier-agnostic** — it
works with the default `rich-text` controller, with subclasses mounted under the same name, and
with per-instance swaps like `controller="rich-text-full"`.

`rich-text:state` is dispatched under a fixed `rich-text:` prefix by the editor controller
([rich_text_controller.js:53](../../resources/js/controllers/rich_text_controller.js)) so the
toolbar's listener catches the event regardless of the editor's registered identifier.

## Targets

Each button is registered as a target named after the action it triggers, so `syncButtons` can flip
the `is-active` class without re-querying the DOM:

| Target        | Reflects                                |
|---------------|-----------------------------------------|
| `bold`        | `editor.isActive("bold")`               |
| `italic`      | `editor.isActive("italic")`             |
| `underline`   | `editor.isActive("underline")`          |
| `bulletList`  | `editor.isActive("bulletList")`         |
| `orderedList` | `editor.isActive("orderedList")`        |
| `blockquote`  | `editor.isActive("blockquote")`         |
| `codeBlock`   | `editor.isActive("codeBlock")`          |
| `link`        | `editor.isActive("link")`               |
| `heading`     | `editor.isActive("heading", { level })` |
| `undo`        | —                                       |
| `redo`        | —                                       |

`heading` targets must declare their level via `data-level="2"` so `syncButtons` can match against
the specific heading level. Buttons without a target attribute simply aren't tracked — handy when
the visual style doesn't need the active treatment.

The mapping above lives in a static `activeStates` map on the controller:

```js
static activeStates = {
    bold: "bold",
    italic: "italic",
    underline: "underline",
    bulletList: "bulletList",
    orderedList: "orderedList",
    blockquote: "blockquote",
    codeBlock: "codeBlock",
    link: "link",
};
```

`syncButtons` iterates this map and reflects `editor.isActive(state)` on each entry whose target is
rendered. Subclasses spread it to add new targets — see [Extending the toolbar](#extending-the-toolbar-table-recipe).
`undo`/`redo` deliberately stay out of the map (no `isActive` semantics). `heading` is special-cased
in `syncHeading()` because it needs an attr lookup (`isActive("heading", { level })`).

## Actions

| Action          | Tiptap command                                                                                                                                                                                                   |
|-----------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `bold`          | `editor.chain().focus().toggleBold().run()`                                                                                                                                                                      |
| `italic`        | `editor.chain().focus().toggleItalic().run()`                                                                                                                                                                    |
| `underline`     | `editor.chain().focus().toggleUnderline().run()`                                                                                                                                                                 |
| `bulletList`    | `editor.chain().focus().toggleBulletList().run()`                                                                                                                                                                |
| `orderedList`   | `editor.chain().focus().toggleOrderedList().run()`                                                                                                                                                               |
| `blockquote`    | `editor.chain().focus().toggleBlockquote().run()`                                                                                                                                                                |
| `codeBlock`     | `editor.chain().focus().toggleCodeBlock().run()`                                                                                                                                                                 |
| `heading`       | `editor.chain().focus().toggleHeading({ level }).run()` — level read from the action's `data-level` param (`data-rich-text-toolbar-level-param`) or from the button's `data-level` attribute, defaulting to `1`. |
| `link`          | Reads the URL from the `url` action param when present, otherwise prompts the user. An empty string runs `unsetLink`; cancelling the prompt is a no-op; a URL runs `setLink({ href })`.                          |
| `undo` / `redo` | `editor.chain().focus().undo().run()` / `redo().run()`                                                                                                                                                           |

The chain pattern (`focus().toggleX().run()`) keeps the document focus, applies the mark, and runs
the transaction in a single ProseMirror update.

## Sync lifecycle

- `connect()` resolves the editor element from `editorValue`, caches the editor (either via the
  controller-walk or via the first incoming `rich-text:state` event), attaches the state listener,
  and runs `syncButtons()` once so the initial state is correct.
- `disconnect()` removes the state listener and clears the cached references.

`syncButtons` iterates only the targets that are actually present, so a minimal toolbar (e.g. just
bold + italic) doesn't pay for buttons it doesn't render. When the editor element can't be
resolved (selector matches nothing) the toolbar stays inert — actions become no-ops, no throws.

## Basic usage (raw)

```html
<div data-controller="rich-text" data-rich-text-id-value="content">
    <input type="hidden" name="content" data-rich-text-target="input">
    <div data-rich-text-target="editor"></div>
</div>

<div data-controller="rich-text-toolbar"
     data-rich-text-toolbar-editor-value="[data-rich-text-id-value='content']">
    <button type="button" data-action="click->rich-text-toolbar#bold"
            data-rich-text-toolbar-target="bold" aria-label="Bold"><strong>B</strong></button>
    <button type="button" data-action="click->rich-text-toolbar#italic"
            data-rich-text-toolbar-target="italic" aria-label="Italic"><em>I</em></button>
    <button type="button" data-action="click->rich-text-toolbar#heading"
            data-rich-text-toolbar-target="heading"
            data-rich-text-toolbar-level-param="2"
            data-level="2">H2</button>
    <button type="button" data-action="click->rich-text-toolbar#bulletList"
            data-rich-text-toolbar-target="bulletList">List</button>
    <button type="button" data-action="click->rich-text-toolbar#link"
            data-rich-text-toolbar-target="link">Link</button>
</div>
```

Most apps don't write this markup by hand — the [`<x-hwc::rich-text>`](../components/rich-text.md)
component renders the default toolbar for you and lets you swap it for a custom one via a slot.

## Custom toolbars

The default buttons are intentionally plain — square brackets like `<strong>B</strong>` and
`<em>I</em>` rather than icons — so you can restyle them or replace them entirely. Drop the
default toolbar from the Blade component by passing `:toolbar="false"` and render your own inside
the slot:

```blade
<x-hwc::rich-text name="content" :toolbar="false">
    <div data-controller="rich-text-toolbar"
         data-rich-text-toolbar-editor-value="[data-rich-text-id-value='content']"
         class="my-toolbar">
        <button type="button"
                data-action="click->rich-text-toolbar#bold"
                data-rich-text-toolbar-target="bold">
            @svg('heroicon-o-bold')
        </button>
        {{-- … --}}
    </div>
</x-hwc::rich-text>
```

The editor selector matches the rich-text controller's `id-value`, so multi-editor pages stay
isolated. Inside a `<form>`, **always** set `type="button"` on toolbar buttons — without it the
default is `type="submit"` and clicks submit the surrounding form instead of toggling formatting.

## Styling the active state

`syncButtons` toggles two things on each tracked target:

- The CSS class `is-active`
- The attribute `aria-pressed="true|false"`

Style either one to communicate the current state:

```css
.my-toolbar button.is-active {
    background: var(--accent);
    color: white;
}
```

## Extending the toolbar (Table recipe)

The default toolbar covers what `starter-kit` ships. When you add a Tiptap extension to the editor
(via the `extensions()` hook on a `rich-text` subclass), the toolbar doesn't grow buttons
automatically — *something* still has to expose Stimulus actions and reflect active state.

The cleanest path is to subclass the toolbar and spread the parent's `targets` and `activeStates`:

```js
// resources/js/controllers/rich_text_table_toolbar_controller.js
import RichTextToolbarController from "./rich_text_toolbar_controller.js";

export default class extends RichTextToolbarController {
    static targets = [...RichTextToolbarController.targets, "table"];

    static activeStates = {
        ...RichTextToolbarController.activeStates,
        table: "table",
    };

    insertTable() {
        this.editor?.chain().focus().insertTable({rows: 3, cols: 3, withHeaderRow: true}).run();
    }

    addColumnBefore() {
        this.editor?.chain().focus().addColumnBefore().run();
    }

    addColumnAfter() {
        this.editor?.chain().focus().addColumnAfter().run();
    }

    deleteColumn() {
        this.editor?.chain().focus().deleteColumn().run();
    }

    addRowBefore() {
        this.editor?.chain().focus().addRowBefore().run();
    }

    addRowAfter() {
        this.editor?.chain().focus().addRowAfter().run();
    }

    deleteRow() {
        this.editor?.chain().focus().deleteRow().run();
    }

    deleteTable() {
        this.editor?.chain().focus().deleteTable().run();
    }
}
```

Pair it with a rich-text subclass that registers the Tiptap extensions:

```js
// resources/js/controllers/rich_text_with_tables_controller.js
import RichTextController from "./rich_text_controller";
import { defaultExtensions } from "./_rich_text_editor";
import { TableKit } from "@tiptap/extension-table";

export default class extends RichTextController {
    extensions(options) {
        return [
            ...defaultExtensions(options),
            TableKit.configure({
                table: { HTMLAttributes: { class: "table" } },
            }),
        ];
    }
}
```

`TableKit` bundles `Table` + `TableRow` + `TableCell` + `TableHeader` from a single
`@tiptap/extension-table` install — one import, one `configure`. Per-extension options live under
the matching key (`table`, `tableRow`, `tableCell`, `tableHeader`).

`options` is `{ placeholder }`, threaded back into `defaultExtensions` so the Placeholder
extension still picks up the configured text — same pattern as the
[Extensions hook](rich-text.md#extensions-hook-subclass) on the editor controller.

> **npm dep:** `@tiptap/extension-table` (any 2.10+ build that ships `TableKit`). Add it to the
> app's `package.json` manually — the catalog only declares core Tiptap deps.

Wire both in the Blade markup with a custom toolbar slot. The `editor` value points at the editor
element via its swapped `id-value` attribute — the toolbar walks `data-controller` and finds the
`rich-text-with-tables` controller transparently:

```blade
<x-hwc::rich-text name="content" controller="rich-text-with-tables" :toolbar="false">
    <div data-controller="rich-text-table-toolbar"
         data-rich-text-table-toolbar-editor-value="[data-rich-text-with-tables-id-value='content']">
        {{-- Default buttons (inherited from the parent's actions) --}}
        <button type="button"
                data-action="click->rich-text-table-toolbar#bold"
                data-rich-text-table-toolbar-target="bold">B</button>
        <button type="button"
                data-action="click->rich-text-table-toolbar#italic"
                data-rich-text-table-toolbar-target="italic">I</button>

        {{-- New table buttons --}}
        <button type="button"
                data-action="click->rich-text-table-toolbar#insertTable"
                data-rich-text-table-toolbar-target="table">Table</button>
        <button type="button" data-action="click->rich-text-table-toolbar#addColumnAfter">+ Col</button>
        <button type="button" data-action="click->rich-text-table-toolbar#addRowAfter">+ Row</button>
        <button type="button" data-action="click->rich-text-table-toolbar#deleteTable">Drop</button>
    </div>
</x-hwc::rich-text>
```

**Two non-obvious bits in this markup:**

- Every `<button>` has `type="button"`. Without it, default is `type="submit"` — clicks submit the
  form (the editor is almost always inside a form) instead of triggering toolbar actions.
- The `editor` value uses `[data-rich-text-with-tables-id-value='content']`, **not**
  `[data-rich-text-id-value='content']`. The Blade view rewrites the `id-value` attribute name
  when you swap `controller=`, so the selector has to follow.

`syncButtons` still does the right thing for the `table` button — the spread brought in every entry
the parent reflected, and the new `table: "table"` entry maps the new target to `editor.isActive("table")`.
The `+ Col` / `+ Row` / `Drop` buttons have no `data-rich-text-table-toolbar-target`, so they stay
inert visually — they're just action triggers.

The same shape works for any Tiptap extension: add the target, add the `activeStates` entry, add
the action method. **No outlet plumbing needed** — the toolbar finds the editor by walking
`data-controller` on the resolved element, so it works against any editor identifier (`rich-text`,
`rich-text-full`, `rich-text-with-tables`, …) without renaming callbacks or declaring extra outlets.

## See also

- [Rich text controller](rich-text.md)
- [Component documentation](../components/rich-text.md)
