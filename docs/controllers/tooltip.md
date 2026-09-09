# Tooltip

Clones an application- or component-authored template into an accessible hover/focus tooltip positioned with Floating
UI.

**Identifier:** `tooltip`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers tooltip`.

## Requirements

- `@floating-ui/dom` for viewport-aware anchored positioning.
- Ships with `_composition.js`, `_floating.js`, `_presence.js`, and `_top_layer.js`; publishing the controller publishes
  these helpers too.
- The trigger must contain exactly one `template` target with one root element. Missing or malformed templates report
  an error and fail closed instead of generating fallback markup.
- A descendant marked `data-tooltip-arrow` is optional. At most one may be present.
- Without the selected preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }`, set
  `[data-hotwire-top-layer][popover] { overflow: visible; }` when an arrow crosses the surface, and define the custom
  surface's complete visual styling.

Escape dismissal is suspended during IME composition.

## Standalone Template

```blade
<button
    type="button"
    data-controller="tooltip"
>
    Save
    <template data-tooltip-target="template">
        <div class="app-tooltip">
            Click to save
            <span data-tooltip-arrow class="app-tooltip-arrow"></span>
        </div>
    </template>
</button>
```

The root element and arrow are application-owned; neither `data-slot` nor package CSS is required or added. The
controller clones the root, applies runtime `role`, id, motion, state, visibility and placement attributes, appends it to
`document.body`, then positions it with Floating UI. The optional arrow may be nested anywhere under the root.

Selecting the standalone controller does not select the package Tooltip visual module. Custom classes and attributes
on the root are retained by every clone, so the application owns its appearance completely.

## Package Component

Use `<hw:tooltip>` when the package preset should style the surface and arrow:

```blade
<button type="button" data-controller="tooltip">
    Save
    <hw:tooltip>Click to save</hw:tooltip>
</button>
```

Button, Color Scheme Toggle and Sidebar Menu Button can emit this component through their `tooltip` string prop. Both
standalone and component usage follow the same clone, positioning, accessibility and lifecycle path; the controller
never builds fallback anatomy.

## With HTML content

```blade
<span data-controller="tooltip">
    Name *
    <hw:tooltip><strong>Required</strong><br>Fill in this field</hw:tooltip>
</span>
```

Tooltips are hoverable and dismissible with Escape. They retain `role="tooltip"` on the cloned surface and add
`aria-describedby` to the trigger while open. Tooltip content should not contain links, buttons or form controls; use
Popover for interactive content. String convenience props on Button, Color Scheme Toggle and Sidebar Menu Button are
escaped as text; rich content exists only through explicit Blade composition.

## Values

| Value         | Type      | Default     | Description                                                                                                   |
|---------------|-----------|-------------|---------------------------------------------------------------------------------------------------------------|
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
| `motion`      | `String`  | `"default"` | Presence motion: `default` or `none`. Rendered as `data-motion` on the cloned tooltip.                         |

## Custom position

```blade
<button
    data-controller="tooltip"
    data-tooltip-side-value="bottom"
    data-tooltip-align-value="end"
>
    Save
    <hw:tooltip>Saved</hw:tooltip>
</button>
```

## Motion

Set `data-tooltip-motion-value="none"` when the tooltip should show and hide immediately:

```blade
<button
    data-controller="tooltip"
    data-tooltip-motion-value="none"
>
    Save
    <hw:tooltip>Saved</hw:tooltip>
</button>
```

Each fresh clone starts as `role="tooltip" data-state="closed" hidden inert`, regardless of attributes on the source.
Presence removes `hidden`, waits for Floating UI's first placement, then changes the state to `open` and removes `inert`.
During exit it changes the state back to `closed` and applies `inert` immediately, but does not add `hidden` or remove
the generated node until the CSS motion finishes.

The selected preset transitions only `opacity`, `scale`, and `translate` on `[data-slot="tooltip"]`. Standalone CSS can
apply transitions or finite animations to the custom root class, keyed by `data-state="open|closed"`. Never set
`display: none` or otherwise hide the element in the closed-state rule; Presence owns `hidden`. Rapid re-entry cancels
stale teardown, and `prefers-reduced-motion: reduce` skips motion automatically.

## Top Layer And Placement

The cloned tooltip is appended to `document.body` and promoted to the browser's native top layer when supported. It
can therefore appear correctly above an open Modal or Drawer. The Toaster manages its own top-layer viewport and does
not participate in this floating Presence lifecycle.

While native top layer is active, the default `fixed` strategy uses viewport-relative coordinates and `absolute` uses
page/document coordinates. Without native Popover support, `absolute` falls back to normal offset-parent behavior.

The tooltip does not enter until `_floating.js` resolves its first placement. `data-side` and `data-align` report the
resolved placement after any flip, while `--transform-origin` follows that result. Superseded asynchronous placement
results are ignored. Browsers without native Popover API support use the normal DOM fallback.

## Conditional display

Use `enabledWhen` when the tooltip should only be active in a specific DOM state. The value is a CSS selector checked with `element.closest(selector)`:

```blade
<button
    data-controller="tooltip"
    data-tooltip-side-value="right"
    data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
>
    Map
    <hw:tooltip>Map</hw:tooltip>
</button>
```

This is useful for custom icon-only rails. For package Sidebar menu buttons, prefer `tooltip="Map"`; the component supplies
this selector and right-side placement. The tooltip appears when the desktop Sidebar is collapsed to icons and hides
when the label is visible, including while the mobile drawer is open. Invalid selectors fail closed.

## On help icons

```blade
<label>
    Email
    <span data-controller="tooltip" class="cursor-help">
        (?)
        <hw:tooltip>Used only for login and password recovery</hw:tooltip>
    </span>
</label>
<input type="email" name="email" />
```

## On disabled buttons

Tooltips on disabled elements need a wrapper, as the browser blocks events on `disabled` elements:

```blade
<span data-controller="tooltip">
    <button type="submit" disabled>Send</button>
    <hw:tooltip>Please fill in all required fields</hw:tooltip>
</span>
```

## Styling hooks

- `data-state="open|closed"` on the cloned tooltip
- `data-motion="default|none"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `data-anchor-hidden`
- `--transform-origin`
- `data-tooltip-arrow` on an optional application-owned arrow

`<hw:tooltip>` additionally emits the package visual hooks `data-slot="tooltip"` and
`data-slot="tooltip-arrow"`. Standalone templates should use their own classes and omit those slots when package preset
styling is not wanted.

`disconnect()`, source removal, an inert/hidden containing overlay and `turbo:before-cache` remove the clone immediately,
cancel timers and pending positioning, clear its `aria-describedby` token and leave the top layer. Escape closes the
Tooltip before a containing Modal, Drawer, Sheet or mobile Sidebar and does not move focus.
