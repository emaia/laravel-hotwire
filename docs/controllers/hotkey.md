# Hotkey

Binds keyboard shortcuts to click or focus an element. Automatically ignores keystrokes typed inside inputs, textareas, and rich-text editors.

**Identifier:** `hotkey`  
**Install:** `php artisan hotwire:controllers hotkey`

## Requirements

- No external dependencies.

## Actions

| Action                  | Description                                                   |
|-------------------------|---------------------------------------------------------------|
| `hotkey#click`   | Clicks the controller element when the key is pressed         |
| `hotkey#focus`   | Focuses the controller element when the key is pressed        |

Both actions are no-ops when:
- The element has `pointer-events: none` (e.g. a disabled button styled via CSS)
- The key event originated inside an `<input>`, `<textarea>`, or `<lexxy-editor>`
- The event was already prevented by another handler

## Basic usage — keyboard shortcut for a button

```html
<button
    data-controller="hotkey"
    data-action="keydown.n@window->hotkey#click"
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
    data-controller="hotkey"
    data-action="keydown.slash@window->hotkey#focus"
/>
```

## Multiple shortcuts on one element

```html
<a
    href="/dashboard"
    data-controller="hotkey"
    data-action="keydown.g@window->hotkey#click keydown.h@window->hotkey#click"
>
    Dashboard
</a>
```

## With modifier keys

Stimulus action descriptors support modifier keys via filters:

```html
<button
    data-controller="hotkey"
    data-action="keydown.ctrl+k@window->hotkey#click"
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
    data-controller="hotkey"
    data-action="keydown.c@window->hotkey#click"
>
    Compose
</a>
```
