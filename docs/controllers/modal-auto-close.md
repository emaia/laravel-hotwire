# Modal Auto Close

Self-removing controller that closes the nearest `modal` ancestor on connect. Designed to be appended to an
open `<hw:modal>` via Turbo Stream so the server can dismiss the modal after a successful action.

**Identifier:** `modal-auto-close`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers modal-auto-close`.

For new code, prefer the package's common [`closeOverlay()` macro](../recipes/overlay-responses.md#return-to-a-parent-and-close):
it closes Modal, Sheet or Drawer by explicit instance/static root id without depending on marker ancestry. This
Modal-only controller remains available for existing integrations; no custom `closeModal` macro is required.

## Requirements

- The [`modal`](./modal.md) controller loaded in the same app.

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
<hw:modal id="edit-post">
    {{-- ... --}}
</hw:modal>
```

When the stream is processed, the `<span>` lands inside the modal, the controller connects, the modal
closes, and the `<span>` removes itself.

## Usage via raw HTML

Rarely useful on its own (you would just call `modal#close` from a button), but valid:

```html
<hw:modal id="edit-post">
    {{-- ... --}}
    <span data-controller="modal-auto-close"></span>
</hw:modal>
```

The modal closes as soon as Stimulus connects.

## Notes

- The modal must be **open** when the stream arrives — closing an already-closed modal is a no-op.
- For frame-backed instances, target their concrete primary Frame, not the canonical host. An empty
  `update`/`replace` is delayed until exit motion finishes; `closeOverlay($request->overlayOrigin())` avoids clearing
  reusable markup directly. For a static modal, use `closeOverlay('static-root-id')` to preserve its content.
