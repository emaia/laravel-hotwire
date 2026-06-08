# Frame-or-page

Render a view as a Turbo Frame payload when the request came from a frame, or as a full page wrapped
in a layout when the user loaded the URL directly. One view, one controller, no duplication.

This component is the declarative form of the [frame-or-page recipe](../recipes/frame-or-page.md).

## Basic usage

```blade
{{-- resources/views/messages/edit.blade.php --}}
<x-hwc::frame-or-page frame="modal" layout="layouts.dashboard">
    <form method="POST" action="{{ route('messages.update', $message) }}">
        @csrf
        @method('PUT')
        <textarea name="body">{{ old('body', $message->body) }}</textarea>
        <button type="submit">Save</button>
    </form>
</x-hwc::frame-or-page>
```

- A request with `Turbo-Frame: modal` renders only `<turbo-frame id="modal">…</turbo-frame>` — Turbo
  swaps it into the matching frame in the receiving page.
- A direct navigation renders `<x-layouts.dashboard>` wrapping the same frame, so the page is
  standalone, refresh-safe and bookmarkable.

The view itself stays oblivious to how it was requested.

## Triggering the frame vs. the page

```blade
<a href="{{ route('messages.edit', $message) }}" data-turbo-frame="modal">Edit (modal)</a>
<a href="{{ route('messages.edit', $message) }}">Edit (page)</a>
```

The first link asks Turbo to scope the request to the `modal` frame, sending the `Turbo-Frame`
request header. The second link navigates normally; the layout renders the standalone page.

Pair with [`<x-hwc::modal frame="modal">`](./modal.md) in your dashboard layout so the modal opens
automatically when the frame receives content.

## Props

| Prop     | Type             | Default | Description                                                                       |
|----------|------------------|---------|-----------------------------------------------------------------------------------|
| `frame`  | `string\|object` | —       | DOM id of the frame. Accepts a string or any object resolvable via `dom_id()`.    |
| `layout` | `?string`        | `null`  | Blade component name (e.g. `layouts.dashboard`) or class-string of the wrapper.   |

`frame` is required. Passing an empty string or whitespace throws `InvalidArgumentException`.

When `layout` is `null` the component always renders just the raw `<turbo-frame>`, regardless of the
request header. Useful for nested frames that never need a standalone presentation.

## Forwarded attributes

Any extra HTML attribute on `<x-hwc::frame-or-page>` is forwarded to the inner `<turbo-frame>`. This
includes the named props of [`<x-turbo::frame>`](https://github.com/emaia/laravel-hotwire-turbo)
(`src`, `loading`, `target`, `refresh`, `autoscroll`, …) and arbitrary `data-*` hooks:

```blade
<x-hwc::frame-or-page frame="messages" src="{{ route('messages.index') }}" loading="lazy">
    <div class="loading">Loading…</div>
</x-hwc::frame-or-page>
```

The component does **not** forward attributes to the layout. The layout is your own component —
configure it the way you'd configure any other Blade layout:

- **Per-route props in the view itself** if the value is fixed per route — wrap the frame-or-page
  in your layout directly when you need props, and skip the `layout` prop:
  ```blade
  <x-layouts.dashboard title="Edit message" :fixed-top="true">
      <x-hwc::frame-or-page frame="modal">
          @include('messages._edit-form')
      </x-hwc::frame-or-page>
  </x-layouts.dashboard>
  ```
  Frame requests still skip the layout (the `<x-hwc::frame-or-page>` branch handles that); only the
  direct-navigation case renders the dashboard.
- **`@push` / `@stack`** for cross-branch values (page title, breadcrumbs) — these survive both
  branches because Blade resolves the stack at render time.

## Model-aware frame ids

When `frame` is an object, the component calls `dom_id()` (from
[`emaia/laravel-hotwire-turbo`](https://github.com/emaia/laravel-hotwire-turbo)) to derive the id:

```blade
<x-hwc::frame-or-page :frame="$message" layout="layouts.dashboard">
    {{-- renders <turbo-frame id="message_42"> for a Message #42 --}}
</x-hwc::frame-or-page>
```

This pairs naturally with `dom_id($message)` calls in your list views and stream responses, keeping
ids consistent across server-rendered, frame-targeted and Turbo Stream contexts.

## Closing on success

When the form inside the frame submits successfully, the typical flow is: close the modal and refresh
the underlying page. Return a Turbo Stream:

```php
public function update(Request $request, Message $message)
{
    $message->update($request->validate([...]));

    return turbo_stream()
        ->refresh(method: 'morph')
        ->update('modal')
        ->flash('success', 'Saved');
}
```

See the [frame-or-page recipe](../recipes/frame-or-page.md) for the full pattern including dashboard
layout setup and modal host wiring.

## Influencing the modal host from a frame payload

A common confusion: trying to configure shared chrome (a modal host, a sidebar) from within a view
that opens *as a frame*. That chrome lives in the host page's layout — it was already rendered with
its own settings before the frame request fired. The frame payload only swaps content inside the
matching `<turbo-frame>`; it cannot retroactively change elements outside the frame.

Three options, in order of how much they cost you:

1. **Use multiple modal hosts**. Render two `<x-hwc::modal frame="modal-edit" :fixed-top="true">`
   and `<x-hwc::modal frame="modal-quick">` in the layout and pick per link via `data-turbo-frame`.
   Zero JS, zero stream gymnastics, the choice is explicit at the call site.
2. **Return a Turbo Stream with `morph`** when you detect the frame request:
   ```php
   if ($request->wasFromTurboFrame('modal')) {
       return turbo_stream()->morph('modal', view('messages._edit-modal', compact('message')));
   }
   ```
   `morph` diffs the DOM and only patches changed attributes/classes, so the modal stays mounted and
   open — no flicker. Avoid `->replace('modal', ...)`: it destroys the open modal and the new one
   won't auto-open, since the modal's content-mutation observer never fires on a fresh mount.
3. **Render `<x-hwc::modal frame="modal">` inside the view itself** instead of relying on a shared
   host. More flexible per-view, but you lose the convenience of one host serving every link.

## See also

- [Frame-or-page recipe](../recipes/frame-or-page.md) — the manual pattern this component encapsulates.
- [`<x-hwc::modal>`](./modal.md) — the modal host that receives the frame content.
- [`<x-hwc::form>`](./form.md) — the `track-frame-src` variant that preserves the originating frame URL.
- [`turbo--frame-src` controller](../controllers/turbo/frame-src.md) — client-side fallback for frame-aware redirects.
