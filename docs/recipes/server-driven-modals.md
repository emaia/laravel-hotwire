# Server-driven modals

Open and close `<x-hwc::modal>` instances from controller responses, without writing client-side JS.

## When to use what

| Scenario                                                | Recommended path                                      |
|---------------------------------------------------------|-------------------------------------------------------|
| User clicks a link → open modal with server content     | `<x-hwc::modal frame="modal">` + Turbo Frame link     |
| Form submission → close the open frame-driven modal     | `turbo_stream()->update('modal', '')`                 |
| Form submission → close + refresh underlying page       | `turbo_stream()->refresh(...)->update('modal', '')`   |
| Frame content cleared → modal closes itself             | `turbo_stream()->update($frameId, '')`                |

## Opening: Turbo Frame as the natural path

The modal component watches the Turbo Frame rendered by `frame="modal"`. When that frame receives
content, the modal opens automatically. When the content is cleared, it closes.

This is the path you want for most "open with content" flows. See the
[frame-or-page recipe](./frame-or-page.md) for the full setup.

```blade
<x-hwc::modal frame="modal">
    <x-slot:loading_template>
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </x-slot:loading_template>
</x-hwc::modal>
```

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
return turbo_stream()->update('modal');
```

The modal closes with its transition, then the frame is cleared. Cleanest option when the modal is
content-driven.

### Remove an open modal root after closing

If the stream targets the modal root with empty content, the modal controller lets the close
animation run before Turbo clears the element:

```php
return turbo_stream()->update('edit-post');
```

Use this when the modal markup is disposable. For reusable layout modals, clear the frame instead.

### Static modal — close without removing markup

For modals that don't have a Turbo Frame inside and should stay in the DOM, append a self-removing
[`modal-auto-close`](../controllers/modal-auto-close.md) marker:

```php
return turbo_stream()->append('edit-post', '<span data-controller="modal-auto-close"></span>');
```

For disposable static modal markup, you can also target the modal root with an empty update:

```php
return turbo_stream()->update('edit-post');
```

## Combining with other streams

The real value of server-driven modals is composing the close with everything else the response
needs to do — refresh the underlying list, fire a toast, etc. See
[composing streams](./composing-streams.md).

## See also

- [`<x-hwc::modal>`](../components/modal.md) — the modal component.
- [`modal-auto-close`](../controllers/modal-auto-close.md) — closes reusable static modals without
  removing their markup.
- [Frame-or-page views](./frame-or-page.md) — the canonical pattern for opening modals with server
  content.
