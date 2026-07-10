# Tooltip

Adds tooltips to any element using [Tippy.js](https://atomiks.github.io/tippyjs/).

**Identifier:** `tooltip`  
**Install:** `php artisan hotwire:controllers tooltip`

## Requirements

- `tippy.js` (`npm install tippy.js` or `bun add tippy.js`)

> If any component in your views pulls this controller in, `php artisan hotwire:check --fix` will add `tippy.js` to your
> `package.json` `devDependencies` automatically.

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `content` | `String` | `"Tooltip"` | Tooltip content. Supports HTML |
| `placement` | `String` | `"top"` | Where the tooltip appears relative to the element. See [tippy placements](https://atomiks.github.io/tippyjs/v6/all-props/#placement) |
| `enabledWhen` | `String` | `""` | Optional ancestor selector. When set, the tooltip only opens while the element is inside a matching ancestor. |

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

## Custom placement

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Saved"
    data-tooltip-placement-value="bottom-end"
>
    Save
</button>
```

## Conditional display

Use `enabledWhen` when the tooltip should only be active in a specific DOM state. The value is a CSS selector checked with `element.closest(selector)`:

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Map"
    data-tooltip-placement-value="right"
    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"
>
    Map
</button>
```

This is useful for icon-only sidebar rails: the tooltip appears when the sidebar is collapsed to icons and hides when the label is visible again. Invalid selectors fail closed, so the tooltip will not open.

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
