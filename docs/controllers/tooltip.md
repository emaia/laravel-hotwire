# Tooltip

Adds accessible hover/focus tooltips to any element using Floating UI positioning.

**Identifier:** `tooltip`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers tooltip`.

## Requirements

- `@floating-ui/dom` for viewport-aware anchored positioning.
- Ships with `_composition.js`, `_floating.js`, `_presence.js`, and `_top_layer.js`; publishing the controller publishes
  these helpers too.
- Without the selected preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }`, set
  `[data-hotwire-top-layer][popover][data-slot="tooltip"] { overflow: visible; }` for arrow clipping, and define the
  generated tooltip's border and padding.

Escape dismissal is suspended during IME composition.

## Basic Usage

```html
<button
    type="button"
    data-controller="tooltip"
    data-tooltip-content-value="Click to save"
>
    Save
</button>
```

The controller creates the tooltip element automatically, appends it to `document.body`, positions it with Floating UI,
and removes it on `disconnect()` or `turbo:before-cache`.

## With HTML content

```html
<span
    data-controller="tooltip"
    data-tooltip-content-value="<strong>Required</strong><br>Fill in this field"
>
    Name *
</span>
```

Tooltips are hoverable and dismissible with Escape. They set `role="tooltip"` on the generated tooltip element and add
`aria-describedby` to the trigger while open. Tooltip content should not contain links, buttons or form controls; use
Popover for interactive content.

## Values

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
| `motion`      | `String`  | `"default"` | Presence motion: `default` or `none`. Rendered as `data-motion` on the generated tooltip.                      |

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

## Motion

Set `data-tooltip-motion-value="none"` when the tooltip should show and hide immediately:

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Saved"
    data-tooltip-motion-value="none"
>
    Save
</button>
```

The generated tooltip starts as `data-state="closed" hidden inert`. Presence removes `hidden`, waits for Floating UI's
first placement, then changes the state to `open` and removes `inert`. During exit it changes the state back to `closed`
and applies `inert` immediately, but does not add `hidden` or remove the generated node until the CSS motion finishes.

The selected preset transitions only `opacity`, `scale`, and `translate`. Custom CSS can use transitions or finite animations
on `[data-slot="tooltip"]`, keyed by `data-state="open|closed"`. Never set `display: none` or otherwise hide the element
in the closed-state rule; Presence owns `hidden`. Rapid re-entry cancels stale teardown, and
`prefers-reduced-motion: reduce` skips motion automatically.

## Top Layer And Placement

The generated tooltip is appended to `document.body` and promoted to the browser's native top layer when supported. It
can therefore appear correctly above an open Modal or Drawer. The Toaster manages its own top-layer viewport and does
not participate in this floating Presence lifecycle.

While native top layer is active, the default `fixed` strategy uses viewport-relative coordinates and `absolute` uses
page/document coordinates. Without native Popover support, `absolute` falls back to normal offset-parent behavior.

The tooltip does not enter until `_floating.js` resolves its first placement. `data-side` and `data-align` report the
resolved placement after any flip, while `--transform-origin` follows that result. Superseded asynchronous placement
results are ignored. Browsers without native Popover API support use the normal DOM fallback.

## Conditional display

Use `enabledWhen` when the tooltip should only be active in a specific DOM state. The value is a CSS selector checked with `element.closest(selector)`:

```html
<button
    data-controller="tooltip"
    data-tooltip-content-value="Map"
    data-tooltip-side-value="right"
    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
>
    Map
</button>
```

This is useful for icon-only sidebar rails: the tooltip appears when the desktop sidebar is collapsed to icons and hides
when the label is visible, including while the mobile drawer is open. Invalid selectors fail closed, so the tooltip will
not open.

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

## Styling hooks

- `data-slot="tooltip"`
- `data-state="open|closed"` on the generated tooltip
- `data-motion="default|none"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `data-anchor-hidden`
- `--transform-origin`
- `data-slot="tooltip-arrow"`

`disconnect()` and `turbo:before-cache` remove the generated tooltip immediately, cancel timers and pending positioning,
clear its `aria-describedby` token, and leave the top layer.
