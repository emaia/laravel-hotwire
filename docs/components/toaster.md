# Toaster

Hosts the toast stack once per page and persists it across Turbo Drive navigations. It is the host element for every
toast fired from a redirect's flash message, by appended Turbo Streams, by [`<hw:toast />`](./toast.md), or from your
own JavaScript.

The component maps to the `toaster` Stimulus controller, which creates the toast manager on connect and publishes it
as `window.toaster`. Rendering, stacking and timers are owned by the package — there is no third-party
dependency.

## Setup

Place the viewport once in your main layout, typically before `</body>`:

```html
<!DOCTYPE html>
<html lang="en">
<head>...</head>
<body>
{{ $slot }}

<hw:toaster />
</body>
</html>
```

That single tag is the whole layout-side setup: the viewport reads the session flash itself, so a redirect carrying a
message shows a toast with nothing else on the page.

The viewport defaults to `id="toaster"` and ships with `data-turbo-permanent`, so it survives Turbo Drive
navigations and keeps the stack alive. The default id is also what Turbo Streams target:

```php
return turbo_stream()->update('task_1', $view)->toast('success', 'Saved!');
```

Order does not matter: a toast emitted before the viewport connects is buffered and drained as soon as it does.

## Session flash

A redirect flashes the toast; the viewport renders it on the page that follows.

```php
return to_route('tasks.show', $task)
    ->toast('success', 'Task updated', 'Your changes are now live.');
```

The `toast()` macro takes the same arguments as [its Turbo Stream twin](./toast.md#the-toast-stream-macro) —
`type`, `message`, and the optional `description` and `position` — so both branches of a controller read alike:

```php
if ($request->wasFromTurboFrame('modal')) {
    return turbo_stream()->update('modal')->toast('success', 'Task updated', 'Your changes are now live.');
}

return to_route('tasks.show', $task)->toast('success', 'Task updated', 'Your changes are now live.');
```

Laravel's conventional flash keys work too, and need no macro:

| Session key | Toast type | Message                                     |
|-------------|------------|---------------------------------------------|
| `toast`     | from payload | `['type' => …, 'message' => …, 'description' => …, 'position' => …]`, or a bare string |
| `success`   | `success`  | The flashed string                          |
| `error`     | `error`    | The flashed string                          |
| `errors`    | `error`    | The first message in the `MessageBag`       |
| `warning`   | `warning`  | The flashed string                          |
| `info`      | `info`     | The flashed string                          |

```php
return redirect()->back()->with('success', 'Item created successfully!');
```

The keys are read in the order above and the first usable message wins. A failed validation therefore surfaces its
first error as a toast on its own, with no extra wiring, named bags (`validateWithBag`, `withErrors(…, 'login')`)
included. An empty message — including `withErrors([])` — renders nothing rather than an empty card.

The keys are read from the session whether they were flashed or stored, so put them there with `->with(…)` or
`session()->flash(…)`. A value written with `session([…])` persists across requests and would raise its toast again
on each one.

Exactly one toast is rendered per request from the flash. If your layout still carries a standalone
[`<hw:toast />`](./toast.md), whichever of the two renders first claims the message and the other stays silent, so
upgrading does not double up. Set `:flash="false"` to hand the session back to `<hw:toast />` entirely.

## Props

| Prop                   | Type      | Default           | Description                                                                     |
|------------------------|-----------|-------------------|---------------------------------------------------------------------------------|
| `id`                   | `string`  | `toaster`         | Element id — also the default target for Turbo Stream appends                   |
| `position`             | `string`  | `bottom-center`   | `top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end` |
| `duration`             | `int`     | `4000`            | Milliseconds before a toast dismisses itself; `0` keeps it until dismissed      |
| `visible-toasts`       | `int`     | `3`               | Maximum number of toasts visible at once                                        |
| `close-button`         | `bool`    | `true`            | Renders a close button on each toast                                            |
| `expand`               | `bool`    | `false`           | Keeps the stack expanded instead of collapsing it when the pointer leaves       |
| `auto-disconnect`      | `bool`    | `false`           | Destroys the manager when the controller disconnects                            |
| `turbo-permanent`      | `bool`    | `true`            | Renders `data-turbo-permanent` on the viewport                                  |
| `flash`                | `bool`    | `true`            | Renders the session flash message as a toast; disable to place `<hw:toast />` yourself |
| `class`                | `string`  | `''`              | CSS class applied to the viewport `<div>` itself                                |
| `class-name`           | `?string` | `null`            | Extra classes applied to every rendered toast                                   |
| `container-aria-label` | `?string` | `Notifications`   | `aria-label` on the viewport landmark                                           |

### On `position`

The first segment is a physical side, the second a *logical* alignment — the same split as `side` and `align` on
Popover, Dropdown and Hover Card. `start` and `end` follow the document's writing direction, so `bottom-end` sits on
the right in a left-to-right document and on the left in a right-to-left one.

## Customization examples

Top-right, 5s duration:

```html
<hw:toaster position="top-end" :duration="5000" />
```

Expanded stack, no close button:

```html
<hw:toaster :close-button="false" :expand="true" />
```

Custom landmark label:

```html
<hw:toaster container-aria-label="Alerts" />
```

Custom id, useful for a second Turbo Stream anchor:

```html
<hw:toaster id="my-toaster" />
```

## Emitting from JavaScript

The manager is published as `window.toaster`. Nothing needs to be imported:

```js
window.toaster.success("Saved");
window.toaster.error("Upload failed", { description: "The file was rejected." });
window.toaster.warning("Storage almost full");
window.toaster.info("Heads up", { position: "top-end" });

const id = window.toaster.toast("Working…", { duration: 0 });
window.toaster.dismiss(id);
```

| Method                       | Description                                                        |
|------------------------------|--------------------------------------------------------------------|
| `toast(message, options?)`   | Emits an untyped toast and returns its id                          |
| `success(message, options?)` | Same, typed `success`                                              |
| `error(message, options?)`   | Same, typed `error` — announced assertively                        |
| `warning(message, options?)` | Same, typed `warning`                                              |
| `info(message, options?)`    | Same, typed `info`                                                 |
| `dismiss(id)`                | Plays the exit animation for one toast and removes it              |
| `destroy()`                  | Clears the stack and removes every listener, timer and observer    |

`options` accepts `description`, `duration`, `position` and `className`. Anything else on the instance is internal
and may change without notice.

## Styling hooks

Appearance lives in the preset, keyed on `data-slot`; the geometry of the stack lives in `structural.css`. Both are
driven by custom properties you can override anywhere in your own CSS:

| Property                 | Default   | Controls                                              |
|--------------------------|-----------|-------------------------------------------------------|
| `--toast-width`          | `22rem`   | Card width, used to centre the `-center` positions    |
| `--toast-offset`         | `1rem`    | Distance from the viewport edges                      |
| `--toast-mobile-offset`  | `1rem`    | Same, below 600px                                     |
| `--toast-gap`            | `0.75rem` | Space between cards when the stack is expanded        |
| `--toast-peek`           | `0.75rem` | How much of each card behind shows when collapsed     |
| `--toast-depth-scale`    | `0.1`     | How much each card behind shrinks per step            |

```css
/* Nudge the stack away from the edge and tighten the collapsed fan */
[data-slot="toaster"] {
    --toast-offset: 2rem;
    --toast-peek: 0.5rem;
}
```

Slots: `toaster`, `toast`, `toast-icon`, `toast-content`, `toast-body`, `toast-title`, `toast-description`,
`toast-close`.

The flash message renders as an empty `data-slot="toast-trigger"` element next to the viewport. It carries the payload,
hands it to the manager on connect and removes itself, so it is never visible and needs no styling.

## Accessibility

- The viewport is a labelled landmark (`role="region"`), so screen readers can jump to it with their landmark
  commands. `container-aria-label` sets the name.
- <kbd>F6</kbd> moves focus to the viewport when at least one toast is on screen, matching Radix and Base UI. This
  intercepts a key Chrome otherwise uses to cycle its own panes.
- Each toast announces its own content — `role="status"` and `aria-live="polite"`, or `role="alert"` and
  `aria-live="assertive"` for `error`. There is deliberately no `aria-label` on the card, which would override the
  message.
- Timers pause while the pointer is over the stack, while focus is inside it, and while the document is hidden.
- Swipe-to-dismiss is not implemented. Toasts are dismissed by the close button or by their timer.
- Entry and exit motion is skipped under `prefers-reduced-motion`.

## Turbo integration

- The viewport uses `data-turbo-permanent` by default, so the stack stays alive across Turbo Drive navigations and
  a toast fired before a visit keeps counting down after it.
- Turbo Streams append rendered `<hw:toast />` markup to the viewport — see the
  [`toast()` macro](./toast.md#the-toast-stream-macro).
- The flash trigger renders as a *sibling* of the viewport, never inside it. Turbo swaps the new page's
  `data-turbo-permanent` element — subtree included — for the current one, so anything nested in the viewport would be
  dropped on the very Drive visit that follows a redirect. For the same reason, do not wrap the viewport in a
  `data-turbo-permanent` element of your own: that would discard the trigger along with it.
- A response consumed as a Turbo Frame renders only the frame, so a flash message left in the layout never reaches the
  page. Use `turbo_stream()->toast(...)` for frame-driven flows.

## See also

- [`<hw:toast />`](./toast.md) — fires individual toasts from session or props
