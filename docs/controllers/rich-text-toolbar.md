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

## Outlet

```js
static outlets = ["rich-text"];
```

The outlet attribute on the toolbar element is a CSS selector that resolves to the editor:

```html
<div data-controller="rich-text-toolbar"
     data-rich-text-toolbar-rich-text-outlet="[data-rich-text-id-value='content']">
```

Stimulus filters the selector matches to elements that actually carry `data-controller~="rich-text"`,
so the `id-value` attribute is enough to pin one toolbar to one editor — even with several editors
on the same page.

## Targets

Each button is registered as a target named after the action it triggers, so `syncButtons` can flip
the `is-active` class without re-querying the DOM:

| Target          | Reflects                                |
|-----------------|------------------------------------------|
| `bold`          | `editor.isActive("bold")`                |
| `italic`        | `editor.isActive("italic")`              |
| `underline`     | `editor.isActive("underline")`           |
| `bulletList`    | `editor.isActive("bulletList")`          |
| `orderedList`   | `editor.isActive("orderedList")`         |
| `blockquote`    | `editor.isActive("blockquote")`          |
| `codeBlock`     | `editor.isActive("codeBlock")`           |
| `link`          | `editor.isActive("link")`                |
| `heading`       | `editor.isActive("heading", { level })`  |
| `undo`          | —                                        |
| `redo`          | —                                        |

`heading` targets must declare their level via `data-level="2"` so `syncButtons` can match against
the specific heading level. Buttons without a target attribute simply aren't tracked — handy when
the visual style doesn't need the active treatment.

## Actions

| Action         | Tiptap command                                              |
|----------------|-------------------------------------------------------------|
| `bold`         | `editor.chain().focus().toggleBold().run()`                  |
| `italic`       | `editor.chain().focus().toggleItalic().run()`                |
| `underline`    | `editor.chain().focus().toggleUnderline().run()`             |
| `bulletList`   | `editor.chain().focus().toggleBulletList().run()`            |
| `orderedList`  | `editor.chain().focus().toggleOrderedList().run()`           |
| `blockquote`   | `editor.chain().focus().toggleBlockquote().run()`            |
| `codeBlock`    | `editor.chain().focus().toggleCodeBlock().run()`             |
| `heading`      | `editor.chain().focus().toggleHeading({ level }).run()` — level read from the action's `data-level` param (`data-rich-text-toolbar-level-param`) or from the button's `data-level` attribute, defaulting to `1`. |
| `link`         | Reads the URL from the `url` action param when present, otherwise prompts the user. An empty string runs `unsetLink`; cancelling the prompt is a no-op; a URL runs `setLink({ href })`. |
| `undo` / `redo`| `editor.chain().focus().undo().run()` / `redo().run()`        |

The chain pattern (`focus().toggleX().run()`) keeps the document focus, applies the mark, and runs
the transaction in a single ProseMirror update.

## Sync lifecycle

- `richTextOutletConnected(_outlet, element)` stores the editor element, attaches a listener for
  `rich-text:state`, and calls `syncButtons()` once so the initial state is correct.
- `richTextOutletDisconnected` removes the listener.
- `disconnect()` removes the listener defensively in case the outlet is still bound.

`syncButtons` iterates only the targets that are actually present, so a minimal toolbar (e.g. just
bold + italic) doesn't pay for buttons it doesn't render.

## Basic usage (raw)

```html
<div data-controller="rich-text" data-rich-text-id-value="content">
    <input type="hidden" name="content" data-rich-text-target="input">
    <div data-rich-text-target="editor"></div>
</div>

<div data-controller="rich-text-toolbar"
     data-rich-text-toolbar-rich-text-outlet="[data-rich-text-id-value='content']">
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
         data-rich-text-toolbar-rich-text-outlet="[data-rich-text-id-value='content']"
         class="my-toolbar">
        <button data-action="click->rich-text-toolbar#bold"
                data-rich-text-toolbar-target="bold">
            @svg('heroicon-o-bold')
        </button>
        {{-- … --}}
    </div>
</x-hwc::rich-text>
```

The outlet selector matches the controller's `id-value`, so multi-editor pages stay isolated.

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

## See also

- [Rich text controller](rich-text.md)
- [Component documentation](../components/rich-text.md)
