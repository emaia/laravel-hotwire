# Tooltip

Adds tooltips to any element using [Tippy.js](https://atomiks.github.io/tippyjs/).

**Identifier:** `tooltip`
**Install:** `php artisan hotwire:controllers tooltip`

## Requirements

- `tippy.js` (`bun add tippy.js`)

> If any component in your views pulls this controller in, `php artisan hotwire:check --fix` will add `tippy.js` to your
> `package.json` `devDependencies` automatically.

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `content` | `String` | `"Tooltip"` | Tooltip content. Supports HTML |

## Basic usage

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Click to save"
>
    Save
</button>
```

## With HTML content

```html
<span
    data-controller="tooltip"
    data-tooltip-content-value="<strong>Required</strong><br>Fill in this field"
>
    Name *
</span>
```

## On help icons

```html
<label>
    Email
    <span
        data-controller="tooltip"
        data-tooltip-content-value="Used only for login and password recovery"
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
    data-controller="tooltip"
    data-tooltip-content-value="Please fill in all required fields"
>
    <button type="submit" disabled>Send</button>
</span>
```
