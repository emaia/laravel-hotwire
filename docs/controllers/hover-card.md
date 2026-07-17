# Hover Card

Controls a hover/focus preview card: delayed open/close state, `Escape` dismissal with focus return, Turbo cache cleanup
and Floating UI positioning.

**Identifier:** `hover-card`  
**Install:** `php artisan hotwire:controllers hover-card`

## Requirements

- `@floating-ui/dom` (`npm install @floating-ui/dom` or `bun add @floating-ui/dom`)

> If any component in your views pulls this controller in, `php artisan hotwire:check --fix` will add
> `@floating-ui/dom` to your `package.json` `devDependencies` automatically.

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

## Actions

| Action                    | Description                                      |
|---------------------------|--------------------------------------------------|
| `hover-card#pointerEnter` | Schedule opening from pointer hover.             |
| `hover-card#pointerLeave` | Schedule closing after pointer leaves.           |
| `hover-card#focusIn`      | Schedule opening from keyboard or program focus. |
| `hover-card#focusOut`     | Schedule closing after focus leaves.             |

## Markup

```html
<div data-controller="hover-card">
    <button
        type="button"
        data-hover-card-target="trigger"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        aria-describedby="user-preview"
        aria-expanded="false"
    >
        Jane Doe
    </button>

    <div
        id="user-preview"
        data-hover-card-target="content"
        data-action="mouseenter->hover-card#pointerEnter mouseleave->hover-card#pointerLeave focusin->hover-card#focusIn focusout->hover-card#focusOut"
        data-open="false"
        role="tooltip"
    >
        <!-- short preview content -->
    </div>
</div>
```

Use the `<hw:hover-card>` component for the server-rendered markup unless you need fully custom HTML.

## Behavior

- Opens after `openDelay` when the trigger receives pointer hover or focus.
- Stays open while the pointer or focus remains inside the trigger or content.
- Closes after `closeDelay` when both pointer and focus leave the trigger and content.
- Closes on `Escape` and returns focus to the active trigger.
- Marks itself as a nested Escape scope while open so parent overlays handle a later `Escape`, not the same one.
- Cleans up timers, Floating UI auto-updates and transition classes on disconnect and `turbo:before-cache`.
