# Toaster

Renders and manages the toast stack. Add it once to the global layout so the `toast` controller has somewhere to
emit into. It owns the DOM, the queue, timers, presence and cleanup; there is no third-party dependency.

**Identifier:** `toaster`

## Values

| Value                  | Type      | Default           | Description                                             |
|------------------------|-----------|-------------------|---------------------------------------------------------|
| `position`             | `String`  | `"bottom-center"` | Where the stack anchors                                 |
| `duration`             | `Number`  | `4000`            | Milliseconds before a toast dismisses itself; `0` pins it |
| `visible-toasts`       | `Number`  | `3`               | Maximum number of toasts visible at once                |
| `close-button`         | `Boolean` | `true`            | Renders a close button on each toast                    |
| `expand`               | `Boolean` | `false`           | Keeps the stack expanded instead of collapsing it       |
| `auto-disconnect`      | `Boolean` | `false`           | Destroys the manager when the controller disconnects    |
| `class-name`           | `String`  | `""`              | Extra classes applied to every rendered toast           |
| `container-aria-label` | `String`  | `"Notifications"` | `aria-label` on the viewport landmark                   |

## Basic Usage

```html
<body>
    ...

    <div data-controller="toaster"></div>
</body>
```

## With custom configuration

```html
<div
    data-controller="toaster"
    data-toaster-position-value="top-end"
    data-toaster-duration-value="6000"
    data-toaster-visible-toasts-value="5"
></div>
```

## Available positions

`top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end`

The second segment is a logical alignment, as with `align` on Popover and Dropdown: `start` and `end` follow the
document's writing direction.

## How it works

The controller creates the manager on connect and publishes it as `window.toaster` — see
[the component docs](../components/toaster.md#emitting-from-javascript) for that surface. If an instance already
exists it is reused, so a second viewport on the page does not replace the first.

The guard checks for a real instance rather than merely a truthy `window.toaster`, because an element carrying
`id="toaster"` is published under that name by the browser before any script runs.

Emissions that arrive before the controller connects are buffered by the manager module and drained on connect, so
a trigger placed above the viewport in the document — or arriving over a Turbo Stream mid-navigation — is never
dropped.

The viewport joins the top layer, so toasts stay above Modal, Drawer, Sheet and Sidebar.
