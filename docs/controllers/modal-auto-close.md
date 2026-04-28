# Modal Auto Close

Self-removing controller that closes the nearest `modal` ancestor on connect. Designed to be appended to an
open `<x-hwc::modal>` via Turbo Stream so the server can dismiss the modal after a successful action.

**Identifier:** `modal-auto-close`  
**Install:** `php artisan hotwire:controllers modal-auto-close`

> Pairs naturally with the [`closeModal` macro](../components/modal.md#convenience-macro) on
> `TurboStreamBuilder`. Use the macro in controllers and let this controller handle the client-side close.

## Requirements

- The [`modal`](./modal.md) controller published in the same app.

## How it works

On `connect()`, the controller:

1. Walks up the DOM with `closest('[data-controller~="modal"]')` to find the modal root.
2. Resolves the modal Stimulus controller and calls `close()` on it.
3. Removes its own element from the DOM so it leaves no trace behind.

The element is meant to be ephemeral — it only exists long enough for Stimulus to fire `connect()`.

## Usage via Turbo Stream

Append the controller-bearing element to an open modal by id:

```php
return turbo_stream()->append('edit-post', '<span data-controller="modal-auto-close"></span>');
```

```html
<!-- The modal must have a stable id matching the stream target -->
<x-hwc::modal id="edit-post">
    {{-- ... --}}
</x-hwc::modal>
```

When the stream is processed, the `<span>` lands inside the modal, the controller connects, the modal
closes, and the `<span>` removes itself.

## Usage via raw HTML

Rarely useful on its own (you would just call `modal#close` from a button), but valid:

```html
<x-hwc::modal id="edit-post">
    {{-- ... --}}
    <span data-controller="modal-auto-close"></span>
</x-hwc::modal>
```

The modal closes as soon as Stimulus connects.

## Notes

- The modal must be **open** when the stream arrives — closing an already-closed modal is a no-op.
- For modals driven by a Turbo Frame, you can alternatively close them by clearing the frame:
  `turbo_stream()->update('frame-id', '')`. The modal's content observer will close it automatically.
  This controller is the right fit for **static** modals (no Turbo Frame) where there is no observer to
  trigger the close.
