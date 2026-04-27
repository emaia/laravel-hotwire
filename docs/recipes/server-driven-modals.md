# Server-driven modals

Open and close `<x-hwc::modal>` instances from controller responses, without writing client-side JS.

## When to use what

| Scenario                                                | Recommended path                                      |
|---------------------------------------------------------|-------------------------------------------------------|
| User clicks a link → open modal with server content     | Turbo Frame + modal `dynamicContent` observer         |
| Form submission → close the open modal                  | `turbo_stream()->closeModal($id)`                     |
| Form submission → close + refresh underlying page       | `turbo_stream()->refresh(...)->closeModal($id)`       |
| Frame content cleared → modal closes itself             | `turbo_stream()->update($frameId, '')`                |

## Opening: Turbo Frame as the natural path

The modal component watches its `dynamicContent` target for changes. When a Turbo Frame inside that
target receives content, the modal opens automatically. When the content is cleared, it closes.

This is the path you want for most "open with content" flows. See the
[frame-or-page recipe](./frame-or-page.md) for the full setup.

```blade
<a href="{{ route('posts.edit', $post) }}" data-turbo-frame="modal">
    Edit
</a>
```

The link issues a frame-scoped request, the response lands in the frame, the observer opens the
modal. **No stream, no macro, no extra controller.**

The modal controller picks up clicks on `a[data-turbo-frame="<its frame id>"]` globally, so the
loading template fires even when the trigger is outside the modal element (typical when the modal
lives in a shared layout).

## Closing from the server

Two equivalent paths, depending on whether the modal is frame-driven or static.

### Frame-driven modal — clear the frame

```php
return turbo_stream()->update('modal', '');
```

The `dynamicContent` observer sees the empty frame and closes the modal. Cleanest option when the
modal is content-driven.

### Static modal — append the auto-close controller

For modals that don't have a Turbo Frame inside, append a self-removing
[`modal-auto-close`](../controllers/modal-auto-close.md) marker:

```php
return turbo_stream()->append('edit-post', '<span data-controller="modal-auto-close"></span>');
```

Or use the [`closeModal` macro](../components/modal.md#convenience-macro) for the same
effect with better DX:

```php
return turbo_stream()->closeModal('edit-post');
```

The macro handles the `<span>` boilerplate and accepts the modal id directly.

## Combining with other streams

The real value of server-driven modals is composing the close with everything else the response
needs to do — refresh the underlying list, fire a toast, etc. See
[composing streams](./composing-streams.md).

## See also

- [`<x-hwc::modal>`](../components/modal.md) — the modal component.
- [`modal-auto-close`](../controllers/modal-auto-close.md) — the controller that powers
  `closeModal()`.
- [Frame-or-page views](./frame-or-page.md) — the canonical pattern for opening modals with server
  content.
