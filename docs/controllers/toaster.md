# Toaster

Clones and manages the component-authored toast stack. Add `<hw:toaster>` once to the global layout so the `toast`
controller has somewhere to emit into. It owns the queue, timers, presence and cleanup; there is no third-party
dependency.

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

    <hw:toaster />
</body>
```

The raw controller is not a standalone empty-element API. It requires the private source template emitted by the Blade
component; use `<hw:toaster>` rather than duplicating that package anatomy.

## With custom configuration

```html
<hw:toaster position="top-end" :duration="6000" :visible-toasts="5" />
```

## Available positions

`top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end`

The second segment is a logical alignment, as with `align` on Popover and Dropdown: `start` and `end` follow the
document's writing direction.

## How it works

The controller validates the component's single card template before installing listeners or joining the top layer. It
then creates the manager and publishes it as `window.toaster` — see
[the component docs](../components/toaster.md#emitting-from-javascript) for that surface. If an instance already
exists it is reused, so a second viewport on the page does not replace the first.

The guard checks for a real instance rather than merely a truthy `window.toaster`, because an element carrying
`id="toaster"` is published under that name by the browser before any script runs.

Emissions that arrive before the controller connects are buffered by the manager module and drained on connect, so
a trigger placed above the viewport in the document — or arriving over a Turbo Stream mid-navigation — is never
dropped.

`window.toaster` is available only after the component mounts. The `toast` trigger handles pre-connect buffering; app
code should not assume the global already exists earlier in page startup.

The viewport joins the top layer, so toasts stay above Modal, Drawer, Sheet and Sidebar.
