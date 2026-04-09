# Tippy

Adds tooltips to any element using [Tippy.js](https://atomiks.github.io/tippyjs/).

**Identifier:** `lib--tippy`

## Requirements

- `tippy.js` (`bun add tippy.js`)

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `content` | `String` | `"Tooltip"` | Tooltip content. Supports HTML |

## Basic usage

```html
<button
    data-controller="lib--tippy"
    data-lib--tippy-content-value="Click to save"
>
    Save
</button>
```

## With HTML content

```html
<span
    data-controller="lib--tippy"
    data-lib--tippy-content-value="<strong>Required</strong><br>Fill in this field"
>
    Name *
</span>
```

## On help icons

```html
<label>
    Email
    <span
        data-controller="lib--tippy"
        data-lib--tippy-content-value="Used only for login and password recovery"
        class="cursor-help"
    >
        (?)
    </span>
</label>
<input type="email" name="email" />
```

## On disabled buttons

Tooltips on disabled elements need a wrapper, as the browser blocks events on `disabled` elements:

```html
<span
    data-controller="lib--tippy"
    data-lib--tippy-content-value="Please fill in all required fields"
>
    <button type="submit" disabled>Send</button>
</span>
```
