# Dialog Auto Close

Self-removing controller that closes the nearest `dialog` ancestor on connect. Designed to be appended to an
open `<x-hwc::dialog>` via Turbo Stream so the server can dismiss the dialog after a successful action.

**Identifier:** `dialog-auto-close`

> Pairs naturally with the [`closeDialog` macro](../components/dialog/readme.md#convenience-macro) on
> `TurboStreamBuilder`. Use the macro in controllers and let this controller handle the client-side close.

## Requirements

- The [`dialog`](./dialog.md) controller published in the same app.

## How it works

On `connect()`, the controller:

1. Walks up the DOM with `closest('[data-controller~="dialog"]')` to find the dialog root.
2. Resolves the dialog Stimulus controller and calls `close()` on it.
3. Removes its own element from the DOM so it leaves no trace behind.

The element is meant to be ephemeral — it only exists long enough for Stimulus to fire `connect()`.

## Usage via Turbo Stream

Append the controller-bearing element to an open dialog by id:

```php
return turbo_stream()->append('edit-post', '<span data-controller="dialog-auto-close"></span>');
```

```html
<!-- The dialog must have a stable id matching the stream target -->
<x-hwc::dialog id="edit-post">
    {{-- ... --}}
</x-hwc::dialog>
```

When the stream is processed, the `<span>` lands inside the dialog, the controller connects, the dialog
closes, and the `<span>` removes itself.

## Usage via raw HTML

Rarely useful on its own (you would just call `dialog#close` from a button), but valid:

```html
<x-hwc::dialog id="edit-post">
    {{-- ... --}}
    <span data-controller="dialog-auto-close"></span>
</x-hwc::dialog>
```

The dialog closes as soon as Stimulus connects.

## Notes

- The dialog must be **open** when the stream arrives — closing an already-closed dialog is a no-op.
- For dialogs driven by a Turbo Frame, you can alternatively close them by clearing the frame:
  `turbo_stream()->update('frame-id', '')`. The dialog's content observer will close it automatically.
  This controller is the right fit for **static** dialogs (no Turbo Frame) where there is no observer to
  trigger the close.
