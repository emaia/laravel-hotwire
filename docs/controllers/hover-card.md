# Hover Card

Controls a hover/focus preview card: delayed open/close state, `Escape` dismissal with focus return, Turbo cache cleanup
and Floating UI positioning.

**Identifier:** `hover-card`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers hover-card`.

## Requirements

- `@floating-ui/dom` (`npm install @floating-ui/dom` or `bun add @floating-ui/dom`)
- Ships with `_composition.js`, `_floating.js`, `_presence.js`, and `_top_layer.js`; publishing the controller publishes
  these helpers too.
- Without the selected preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define the floating element's border and padding.

> If any component in your views pulls this controller in, `php artisan hotwire:check --fix` will add
> `@floating-ui/dom` to your `package.json` `devDependencies` automatically.

Escape dismissal is suspended during IME composition.

## Basic Usage

```html
<div data-controller="hover-card">
    <button
        type="button"
        data-hover-card-target="trigger"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        aria-describedby="user-preview"
        aria-expanded="false"
        data-hover-card-state="closed"
    >
        Jane Doe
    </button>

    <div
        id="user-preview"
        data-hover-card-target="content"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        data-state="closed"
        data-motion="default"
        hidden
        inert
        role="tooltip"
    >
        <strong>Jane Doe</strong>
        <p>Product designer on the platform team.</p>
    </div>
</div>
```

Use the `<hw:hover-card>` component for the server-rendered markup unless you need fully custom HTML. The controller
opens after hover or focus delay, stays open while pointer or focus remains inside the trigger or card, and closes before
Turbo caches the page.

## Targets

| Target    | Description                                      |
|-----------|--------------------------------------------------|
| `trigger` | One or more elements that open the preview card. |
| `content` | The floating preview card.                       |

## Values

| Value         | Type      | Default      | Description                                            |
|---------------|-----------|--------------|--------------------------------------------------------|
| `open`        | `Boolean` | `false`      | Initial open state.                                    |
| `openDelay`   | `Number`  | `10`         | Delay in milliseconds before opening.                  |
| `closeDelay`  | `Number`  | `100`        | Delay in milliseconds before closing.                  |
| `side`        | `String`  | `"bottom"`   | Preferred side: `top`, `right`, `bottom` or `left`.    |
| `align`       | `String`  | `"start"`    | Alignment on that side: `start`, `center` or `end`.    |
| `sideOffset`  | `Number`  | `4`          | Main-axis gap between trigger and content.             |
| `alignOffset` | `Number`  | `0`          | Cross-axis offset along the trigger edge.              |
| `strategy`    | `String`  | `"fixed"`    | Floating UI strategy: `fixed` or `absolute`.           |
| `flip`        | `Boolean` | `true`       | Flip when there is not enough space.                   |
| `shift`       | `Boolean` | `true`       | Shift within the viewport when content would overflow. |

Motion is configured on the content target with `data-motion="default|none"`, not as a Stimulus value. The
`hover-card.content` Blade component renders it from the `motion` prop.

## Actions

| Action                    | Description                                      |
|---------------------------|--------------------------------------------------|
| `hover-card#pointerEnter` | Schedule opening from pointer hover.             |
| `hover-card#pointerLeave` | Schedule closing after pointer leaves.           |
| `hover-card#focusIn`      | Schedule opening from keyboard or program focus. |
| `hover-card#focusOut`     | Schedule closing after focus leaves.             |

## Copyable Minimal Markup

```html
<div data-controller="hover-card">
    <button
        type="button"
        data-hover-card-target="trigger"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        aria-describedby="user-preview"
        aria-expanded="false"
        data-hover-card-state="closed"
    >
        Jane Doe
    </button>

    <div
        id="user-preview"
        data-hover-card-target="content"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        data-state="closed"
        data-motion="default"
        hidden
        inert
        role="tooltip"
    >
        <!-- short preview content -->
    </div>
</div>
```

The content target starts closed, hidden and inert so the preview never flashes before Stimulus connects.

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
adding `hidden`. Trigger state is exposed separately as `data-hover-card-state="open|closed"`.

The selected preset transitions only `opacity`, `scale`, and `translate`. Custom CSS can style `data-state="open|closed"`,
but the closed rule must not set `display: none` or otherwise hide the element. Set `data-motion="none"` for immediate
presence changes. Reduced-motion preference skips motion, and reopening during exit cancels stale hiding and top-layer
teardown.

## Behavior

- Opens after `openDelay` when the trigger receives pointer hover or focus.
- Stays open while the pointer or focus remains inside the trigger or content.
- Closes after `closeDelay` when both pointer and focus leave the trigger and content.
- Closes on `Escape` and returns focus to the active trigger.
- Marks itself as a nested Escape scope while open so parent overlays handle a later `Escape`, not the same one.
- Replacing content tears down Presence, Floating UI, and top-layer state for the old node; replacing a trigger clears
  pending trigger timers and re-anchors open content to the new target.
- `disconnect()` and `turbo:before-cache` synchronously apply `hidden inert`, cancel timers and pending positioning, and
  leave the top layer so Turbo never caches an open Hover Card.
