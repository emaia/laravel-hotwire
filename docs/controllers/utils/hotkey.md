# Hotkey

Binds keyboard shortcuts to click or focus an element. Automatically ignores keystrokes typed inside inputs, textareas, and rich-text editors.

**Identifier:** `utils--hotkey`

## Requirements

- No external dependencies.

## Actions

| Action                  | Description                                                   |
|-------------------------|---------------------------------------------------------------|
| `utils--hotkey#click`   | Clicks the controller element when the key is pressed         |
| `utils--hotkey#focus`   | Focuses the controller element when the key is pressed        |

Both actions are no-ops when:
- The element has `pointer-events: none` (e.g. a disabled button styled via CSS)
- The key event originated inside an `<input>`, `<textarea>`, or `<lexxy-editor>`
- The event was already prevented by another handler

## Basic usage — keyboard shortcut for a button

```html
<button
    data-controller="utils--hotkey"
    data-action="keydown.n@window->utils--hotkey#click"
>
    New post
</button>
```

Pressing `n` anywhere on the page (except inside a text field) clicks the button.

## Focus a search input

```html
<input
    type="search"
    name="q"
    placeholder="Search… (/)"
    data-controller="utils--hotkey"
    data-action="keydown.slash@window->utils--hotkey#focus"
/>
```

## Multiple shortcuts on one element

```html
<a
    href="/dashboard"
    data-controller="utils--hotkey"
    data-action="keydown.g@window->utils--hotkey#click keydown.h@window->utils--hotkey#click"
>
    Dashboard
</a>
```

## With modifier keys

Stimulus action descriptors support modifier keys via filters:

```html
<button
    data-controller="utils--hotkey"
    data-action="keydown.ctrl+k@window->utils--hotkey#click"
>
    Command palette
</button>
```

## Combining with Turbo

Because the controller clicks or focuses the real element, it works transparently with Turbo Drive links and Turbo Frame forms — no extra wiring needed.

```html
<a
    href="/posts/new"
    data-turbo-frame="modal"
    data-controller="utils--hotkey"
    data-action="keydown.c@window->utils--hotkey#click"
>
    Compose
</a>
```
