# Server-driven dialogs

Open and close `<x-hwc::dialog>` instances from controller responses, without writing client-side JS.

## When to use what

| Scenario                                                | Recommended path                                      |
|---------------------------------------------------------|-------------------------------------------------------|
| User clicks a link → open dialog with server content    | Turbo Frame + dialog `dynamicContent` observer        |
| Form submission → close the open dialog                 | `turbo_stream()->closeDialog($id)`                    |
| Form submission → close + refresh underlying page       | `turbo_stream()->refresh(...)->closeDialog($id)`      |
| Frame content cleared → dialog closes itself            | `turbo_stream()->update($frameId, '')`                |

## Opening: Turbo Frame as the natural path

The dialog component watches its `dynamicContent` target for changes. When a Turbo Frame inside that
target receives content, the dialog opens automatically. When the content is cleared, it closes.

This is the path you want for most "open with content" flows. See the
[frame-or-page recipe](./frame-or-page.md) for the full setup.

```blade
<a href="{{ route('posts.edit', $post) }}"
   data-turbo-frame="modal"
   data-action="dialog#showLoading">
    Edit
</a>
```

The link issues a frame-scoped request, the response lands in the frame, the observer opens the
dialog. **No stream, no macro, no extra controller.**

## Closing from the server

Two equivalent paths, depending on whether the dialog is frame-driven or static.

### Frame-driven dialog — clear the frame

```php
return turbo_stream()->update('modal', '');
```

The `dynamicContent` observer sees the empty frame and closes the dialog. Cleanest option when the
dialog is content-driven.

### Static dialog — append the auto-close controller

For dialogs that don't have a Turbo Frame inside, append a self-removing
[`dialog-auto-close`](../controllers/dialog-auto-close.md) marker:

```php
return turbo_stream()->append('edit-post', '<span data-controller="dialog-auto-close"></span>');
```

Or use the [`closeDialog` macro](../components/dialog/readme.md#convenience-macro) for the same
effect with better DX:

```php
return turbo_stream()->closeDialog('edit-post');
```

The macro handles the `<span>` boilerplate and accepts the dialog id directly.

## Combining with other streams

The real value of server-driven dialogs is composing the close with everything else the response
needs to do — refresh the underlying list, fire a toast, etc. See
[composing streams](./composing-streams.md).

## See also

- [`<x-hwc::dialog>`](../components/dialog/readme.md) — the dialog component.
- [`dialog-auto-close`](../controllers/dialog-auto-close.md) — the controller that powers
  `closeDialog()`.
- [Frame-or-page views](./frame-or-page.md) — the canonical pattern for opening dialogs with server
  content.
