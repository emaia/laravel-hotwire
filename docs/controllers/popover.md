# Popover

Controls a click-triggered popover panel: open/close state, outside-click dismissal, `Escape` dismissal with focus
return, Turbo cache cleanup and Floating UI positioning.

**Identifier:** `popover`
**Install:** `php artisan hotwire:controllers popover`

## Requirements

- `@floating-ui/dom` (`npm install @floating-ui/dom` or `bun add @floating-ui/dom`)

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
| `strategy`    | `String`  | `"absolute"` | Floating UI strategy: `absolute` or `fixed`.           |
| `flip`        | `Boolean` | `true`       | Flip when there is not enough space.                   |
| `shift`       | `Boolean` | `true`       | Shift within the viewport when content would overflow. |

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
    >
        Filters
    </button>

    <div
        id="filters-popover"
        data-popover-target="content"
        data-open="false"
        role="dialog"
        tabindex="-1"
    >
        <!-- arbitrary content -->
    </div>
</div>
```

Use the `<hw:popover>` component for the server-rendered markup unless you need fully custom HTML.

## Behavior

- Opens and closes from the trigger.
- Focuses the first focusable element in the content, or the content itself.
- Keeps the panel open when clicking inside it.
- Closes on outside click.
- Closes on `Escape` and returns focus to the trigger.
- Marks itself as a nested Escape scope while open so parent overlays handle a later `Escape`, not the same one.
- Cleans up Floating UI auto-updates and transition classes on disconnect and `turbo:before-cache`.
