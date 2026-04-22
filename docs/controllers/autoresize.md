# Autoresize

Automatically resizes a `<textarea>` to fit its content, eliminating scroll bars.

**Identifier:** `autoresize`

## Requirements

- No external dependencies.

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `resize-debounce-delay` | `Number` | `100` | Debounce delay (ms) on the window `resize` event. Use `0` to disable |

## Basic usage

```html
<textarea
    data-controller="autoresize"
    name="description"
    rows="3"
    placeholder="Describe..."
></textarea>
```

The textarea grows automatically as the user types and shrinks when text is deleted.

## With custom resize delay

```html
<textarea
    data-controller="autoresize"
    data-autoresize-resize-debounce-delay-value="200"
    name="content"
></textarea>
```

## Without resize debounce

```html
<textarea
    data-controller="autoresize"
    data-autoresize-resize-debounce-delay-value="0"
    name="notes"
></textarea>
```

## With initial content (editing)

The textarea automatically adjusts its height on connect, even with pre-existing content:

```html
<textarea
    data-controller="autoresize"
    name="bio"
>{{ $user->bio }}</textarea>
```
