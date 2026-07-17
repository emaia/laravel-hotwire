# Tooltip

Adds accessible hover/focus tooltips to any element using Floating UI positioning.

**Identifier:** `tooltip`  
**Install:** `php artisan hotwire:controllers tooltip`

## Stimulus Values

| Value         | Type      | Default     | Description                                                                                                   |
|---------------|-----------|-------------|---------------------------------------------------------------------------------------------------------------|
| `content`     | `String`  | `"Tooltip"` | Tooltip content. Supports HTML.                                                                               |
| `side`        | `String`  | `"top"`     | Side where the tooltip appears: `top`, `right`, `bottom`, or `left`.                                          |
| `align`       | `String`  | `"center"`  | Alignment on that side: `start`, `center`, or `end`.                                                          |
| `sideOffset`  | `Number`  | `8`         | Distance between trigger and tooltip.                                                                         |
| `alignOffset` | `Number`  | `0`         | Cross-axis offset.                                                                                            |
| `strategy`    | `String`  | `"fixed"`   | Floating UI positioning strategy: `fixed` or `absolute`.                                                      |
| `flip`        | `Boolean` | `true`      | Allow Floating UI to flip to another side when there is not enough room.                                      |
| `shift`       | `Boolean` | `true`      | Allow Floating UI to shift the tooltip inside the viewport.                                                   |
| `delay`       | `Number`  | `0`         | Delay before opening, in milliseconds.                                                                        |
| `closeDelay`  | `Number`  | `100`       | Delay before closing after hover/focus leaves, in milliseconds.                                               |
| `enabledWhen` | `String`  | `""`        | Optional ancestor selector. When set, the tooltip only opens while the element is inside a matching ancestor. |

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

Tooltips are hoverable and dismissible with Escape. They set `role="tooltip"` on the generated tooltip element and add `aria-describedby` to the trigger while open. Tooltip content should not contain links, buttons or form controls; use Popover for interactive content.

## Custom position

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Saved"
    data-tooltip-side-value="bottom"
    data-tooltip-align-value="end"
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
    data-tooltip-side-value="right"
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
