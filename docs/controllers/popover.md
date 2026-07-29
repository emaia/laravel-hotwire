# Popover

Controls a click-triggered popover panel: open/close state, outside-click dismissal, `Escape` dismissal with focus
return, Turbo cache cleanup and Floating UI positioning.

**Identifier:** `popover`
**Install:** `php artisan hotwire:controllers popover`

## Requirements

- `@floating-ui/dom` (`npm install @floating-ui/dom` or `bun add @floating-ui/dom`)
- Ships with `_floating.js`, `_presence.js`, and `_top_layer.js`; publishing the controller publishes these helpers too.
- Without the Nova preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define the floating element's border and padding.

> If any component in your views pulls this controller in, `php artisan hotwire:check --fix` will add
> `@floating-ui/dom` to your `package.json` `devDependencies` automatically.

## Targets

| Target    | Description                                   |
|-----------|-----------------------------------------------|
| `trigger` | One or more elements that toggle the popover. |
| `content` | The floating content panel.                   |

## Values

| Value         | Type      | Default      | Description                                            |
|---------------|-----------|--------------|--------------------------------------------------------|
| `open`        | `Boolean` | `false`      | Initial open state.                                    |
| `side`        | `String`  | `"bottom"`   | Preferred side: `top`, `right`, `bottom` or `left`.    |
| `align`       | `String`  | `"start"`    | Alignment on that side: `start`, `center` or `end`.    |
| `sideOffset`  | `Number`  | `4`          | Main-axis gap between trigger and content.             |
| `alignOffset` | `Number`  | `0`          | Cross-axis offset along the trigger edge.              |
| `strategy`    | `String`  | `"fixed"`    | Floating UI strategy: `fixed` or `absolute`.           |
| `flip`        | `Boolean` | `true`       | Flip when there is not enough space.                   |
| `shift`       | `Boolean` | `true`       | Shift within the viewport when content would overflow. |

Motion is configured on the content target with `data-motion="default|none"`, not as a Stimulus value. The
`popover.content` Blade component renders it from the `motion` prop.

## Actions

| Action           | Description       |
|------------------|-------------------|
| `popover#toggle` | Toggle the panel. |
| `popover#open`   | Open the panel.   |
| `popover#close`  | Close the panel.  |

## Markup

```html
<div data-controller="popover">
    <button
        type="button"
        data-popover-target="trigger"
        data-action="popover#toggle"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="filters-popover"
        data-popover-state="closed"
    >
        Filters
    </button>

    <div
        id="filters-popover"
        data-popover-target="content"
        data-state="closed"
        data-motion="default"
        hidden
        inert
        role="dialog"
        tabindex="-1"
    >
        <!-- arbitrary content -->
    </div>
</div>
```

Use the `<hw:popover>` component for the server-rendered markup unless you need fully custom HTML.

## Positioning And Top Layer

The controller uses Floating UI `offset`, `flip`, `shift`, and `size` middleware and promotes content to the native top
layer when the browser supports the Popover API. The normal DOM fallback remains available in older browsers and can be
clipped by ancestors.

While native top layer is active, `fixed` uses viewport-relative coordinates and `absolute` uses page/document
coordinates; `absolute` does not use the nearest positioned ancestor in that mode. Without native Popover support,
`absolute` falls back to normal offset-parent behavior.

Presence waits for the first placement before entering. `data-side`, `data-align`, `--anchor-width`,
`--anchor-height`, `--available-width`, `--available-height`, and `--transform-origin` are written to the content.
`data-side` and `data-align` represent the resolved placement after any flip. Results from superseded positioning runs
are ignored.

## Presence And Motion

Server-render content with `data-state="closed" hidden inert`, including when the initial `open` value is `true`.
Opening removes `hidden` but leaves the content closed and inert until its first placement resolves. Closing applies
`data-state="closed"` and `inert` immediately, then waits for the element's CSS transition or finite animation before
adding `hidden`. Trigger state is exposed separately as `data-popover-state="open|closed"`.

The Nova preset transitions only `opacity`, `scale`, and `translate`. Custom CSS can style `data-state="open|closed"`,
but the closed rule must not set `display: none` or otherwise hide the element. Set `data-motion="none"` for immediate
presence changes. Reduced-motion preference skips motion, and reopening during exit cancels stale hiding and top-layer
teardown.

## Behavior

- Opens and closes from the trigger.
- Focuses the first focusable element in the content, or the content itself.
- Keeps the panel open when clicking inside it.
- Closes on outside click.
- Closes on `Escape` and returns focus to the trigger.
- Marks itself as a nested Escape scope while open so parent overlays handle a later `Escape`, not the same one.
- Replacing content tears down Presence, Floating UI, and top-layer state for the old node; replacing a trigger re-anchors
  open content to the new target.
- `disconnect()` and `turbo:before-cache` synchronously apply `hidden inert`, cancel pending positioning, and leave the
  top layer so Turbo never caches an open popover.
