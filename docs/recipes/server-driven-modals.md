# Server-driven modals

Open and close `<hw:modal>` instances from controller responses, without writing client-side JS.

## When to use what

| Scenario                                                | Recommended path                                      |
|---------------------------------------------------------|-------------------------------------------------------|
| User clicks a link → open modal with server content     | `<hw:modal frame="modal">` + Turbo Frame link     |
| Form submission → close the requesting overlay          | `turbo_stream()->closeOverlay($request->overlayOrigin())` |
| Update background content without discarding drafts     | Targeted `replace`/`update` + `closeOverlay($origin)` |
| Server decision → open a new modal                       | `response()->modal()->open(view(...), source: ...)` |
| Static reusable modal → close without erasing content    | `turbo_stream()->closeOverlay('static-modal-id')` |

## Opening: Turbo Frame as the natural path

The modal component declares the canonical host `frame="modal"`. Each link opening creates a concrete Frame instance;
when its loading template or response arrives, that instance opens. Unqualified navigation inside it reuses the same
Frame. A second link explicitly targeting the canonical host starts a child instead of replacing the parent's draft.

This is the path you want for most "open with content" flows. See the
[frame-or-page recipe](./frame-or-page.md) for the full setup.

```blade
<hw:modal frame="modal">
    <x-slot:loading_template>
        <hw:modal.title>Loading post</hw:modal.title>
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </x-slot:loading_template>
</hw:modal>
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

Use the same explicit close operation for frame-driven instances and reusable static overlays.

### Frame-driven modal — close the requesting instance

```php
return turbo_stream()->closeOverlay($request->overlayOrigin());
```

The modal closes through its normal lifecycle and the generated instance is discarded. A delayed response cannot close
a newer opening. An empty `update($request->turboFrameId())` remains supported for a primary Frame, but the canonical
`modal` host is not the child's id. See [overlay responses](./overlay-responses.md).

### Remove an open modal root after closing

If the stream targets the modal root with empty content, the modal controller lets the close
animation run before Turbo clears the element:

```php
return turbo_stream()->update('edit-post');
```

Use this when the modal markup is disposable. For reusable layout modals, clear the frame instead.

### Static modal — close without removing markup

For modals without a Turbo Frame that should stay in the DOM, name the static root explicitly:

```php
return turbo_stream()->closeOverlay('edit-post');
```

For disposable static modal markup, you can also target the modal root with an empty update:

```php
return turbo_stream()->update('edit-post');
```

## Combining with other streams

The real value of server-driven modals is composing the close with everything else the response
needs to do — update the affected row, fire a toast, etc. A native `refresh` is a page-wide operation, not an implicit
close; an accepted refresh can discard the whole overlay stack. See
[composing streams](./composing-streams.md).

## See also

- [`<hw:modal>`](../components/modal.md) — the modal component.
- [Overlay responses](./overlay-responses.md) — family-specific openings, common closing, contextual returns and refresh semantics.
- [`closeOverlay()`](./overlay-responses.md#return-to-a-parent-and-close) — explicit closing for instances and static roots.
- [Frame-or-page views](./frame-or-page.md) — the canonical pattern for opening modals with server
  content.
