# Frame-or-page

Render a view as a Turbo Frame payload when the request came from a frame, or as a full page wrapped
in a layout when the user loaded the URL directly. One view, one controller, no duplication.

This component is the declarative form of the [frame-or-page recipe](../recipes/frame-or-page.md).

## Basic usage

```blade
{{-- resources/views/messages/edit.blade.php --}}
<hw:frame-or-page frame="modal" layout="dashboard">
    <hw:form :action="route('messages.update', $message)" method="put">
        <hw:field name="body" label="Message">
            <hw:textarea :value="$message->body" auto-resize />
        </hw:field>

        <hw:button type="submit">Save</hw:button>
    </hw:form>
</hw:frame-or-page>
```

- A request with `Turbo-Frame: modal` renders only `<turbo-frame id="modal">…</turbo-frame>` — Turbo
  swaps it into the matching frame in the receiving page.
- A direct navigation renders the layout (`<x-layouts.dashboard>`) wrapping the slot **directly** —
  no extra `<turbo-frame>` around the content. The standalone page is refresh-safe and bookmarkable,
  and any `<turbo-frame>` that should host modal/sidebar content lives in the layout itself.

The view itself stays oblivious to how it was requested.

> **Why no `<turbo-frame>` around the slot on direct nav?** A dashboard layout typically already
> hosts the receiving frame globally — e.g. `<hw:modal frame="modal">` renders a
> `<turbo-frame id="modal">` once per page. If the component also wrapped the slot in
> `<turbo-frame id="modal">` on direct nav, the page would carry two elements with the same `id`:
> invalid HTML, and Turbo would aim subsequent navigations at the wrong frame.

## Triggering the frame vs. the page

```blade
<a href="{{ route('messages.edit', $message) }}" data-turbo-frame="modal">Edit (modal)</a>
<a href="{{ route('messages.edit', $message) }}">Edit (page)</a>
```

The first link asks Turbo to scope the request to the `modal` frame, sending the `Turbo-Frame`
request header. The second link navigates normally; the layout renders the standalone page.

Pair with a frame host in your dashboard layout so the overlay opens automatically when the frame receives content. The
common hosts are [`<hw:modal frame="modal">`](./modal.md), [`<hw:sheet frame="settings-panel">`](./sheet.md), and
[`<hw:drawer frame="drawer-panel">`](./drawer.md).

## Props

| Prop     | Type             | Default | Description                                                                                    |
|----------|------------------|---------|------------------------------------------------------------------------------------------------|
| `frame`  | `string\|object` | —       | DOM id of the frame. Accepts a string or any object resolvable via `dom_id()`.                 |
| `layout` | `?string`        | `null`  | Blade component name (e.g. `dashboard` or `layouts.dashboard`) or class-string of the wrapper. |

`frame` is required. Passing an empty string or whitespace throws `InvalidArgumentException`.

When `layout` is `null` the component always renders just the raw `<turbo-frame>`, regardless of the
request header. Useful for nested frames that never need a standalone presentation.

Simple layout names resolve ergonomically: `layout="dashboard"` uses an existing `dashboard` component
when one is registered, otherwise it tries `layouts.dashboard` before falling back to the original value.
Names that already contain `.`, `::`, or `\` are used as-is.

## Context-specific content

Use `frameContent` when the frame payload should be smaller than the standalone page. Keep the full
page content in the default slot so direct navigation stays natural:

```blade
{{-- resources/views/parks/topics/edit.blade.php --}}
<hw:frame-or-page frame="modal" layout="dashboard">
    <x-slot:frameContent>
        @include('parks.topics._form')
    </x-slot:frameContent>

    @include('parks._edit_header')
    @include('parks._edit_navigation', ['active' => 'topics'])
    @include('parks.topics._form')
    @include('parks.topics._list')
</hw:frame-or-page>
```

In this example, opening the route in `<hw:modal frame="modal">` renders only the form. Opening the
same URL directly renders the dashboard page with header, navigation, form, and list.

The selection rules are:

- Frame requests render `frameContent` when present, otherwise the default slot.
- Direct navigation with a layout renders `pageContent` when present, otherwise the default slot.
- When `layout` is omitted, the component still renders as a frame and uses `frameContent` when present.

Use `pageContent` only when naming both contexts makes the view clearer, or when the default slot is
better treated as shared fallback content:

```blade
<hw:frame-or-page frame="modal" layout="dashboard">
    Shared fallback content

    <x-slot:frameContent>
        Modal-only content
    </x-slot:frameContent>

    <x-slot:pageContent>
        Full-page content
    </x-slot:pageContent>
</hw:frame-or-page>
```

Partials included inside either slot use the same Blade scope as the surrounding view, so variables like
models, option lists, selected values, and validation state remain available. Pass data explicitly to
`@include` when you want to make the dependency clear or override a value for one context.

## Forwarded attributes

When the component renders **as a frame** — that is, when the request came from a Turbo Frame OR
when `layout` is omitted — extra HTML attributes on `<hw:frame-or-page>` are forwarded to the
inner [`<hw:frame>`](./frame.md). This includes native Turbo Frame attributes (`src`, `loading`,
`target`, `refresh`, `autoscroll`, …), frame aliases like `lazy`, `advance`, `replace`, `poll`,
and arbitrary `data-*` hooks:

```blade
<hw:frame-or-page frame="messages" src="{{ route('messages.index') }}" loading="lazy">
    <div class="loading">Loading…</div>
</hw:frame-or-page>
```

On **direct navigation with a `layout`**, the slot is rendered directly inside the layout component
with no surrounding `<turbo-frame>`, so frame-specific attributes like `src` / `loading` have no
target and are dropped. If you need a frame around your content on direct nav (rare — usually the
layout's host frame is enough), add an explicit `<x-turbo::frame>` inside the slot.

The component does **not** forward attributes to the layout. The layout is your own component —
configure it the way you'd configure any other Blade layout:

- **Per-route props in the view itself** if the value is fixed per route — wrap the frame-or-page
  in your layout directly when you need props, and skip the `layout` prop:
  ```blade
  <x-layouts.dashboard title="Edit message" :fixed-top="true">
      <hw:frame-or-page frame="modal">
          @include('messages._edit-form')
      </hw:frame-or-page>
  </x-layouts.dashboard>
  ```
  Frame requests still skip the layout (the `<hw:frame-or-page>` branch handles that); only the
  direct-navigation case renders the dashboard.
- **`@push` / `@stack`** for cross-branch values (page title, breadcrumbs) — these survive both
  branches because Blade resolves the stack at render time.

## Model-aware frame ids

When `frame` is an object, the component calls `dom_id()` (from
[`emaia/laravel-hotwire-turbo`](https://github.com/emaia/laravel-hotwire-turbo)) to derive the id:

```blade
<hw:frame-or-page :frame="$message" layout="layouts.dashboard">
    {{-- renders <turbo-frame id="message_42"> for a Message #42 --}}
</hw:frame-or-page>
```

This pairs naturally with `dom_id($message)` calls in your list views and stream responses, keeping
ids consistent across server-rendered, frame-targeted and Turbo Stream contexts.

## Closing on success

When the form inside the frame submits successfully, the typical flow is: close the overlay and refresh
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
layout setup and frame host wiring.

## Influencing the overlay host from a frame payload

A common confusion: trying to configure shared chrome (a modal, sheet, drawer, or sidebar host) from within a view
that opens *as a frame*. That chrome lives in the host page's layout — it was already rendered with
its own settings before the frame request fired. The frame payload only swaps content inside the
matching `<turbo-frame>`; it cannot retroactively change elements outside the frame.

Three options, in order of how much they cost you:

1. **Use multiple hosts**. Render two hosts, such as `<hw:modal frame="modal-edit" :fixed-top="true">`
   and `<hw:sheet frame="settings-panel" side="right">`, in the layout and pick per link via `data-turbo-frame`.
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
3. **Render `<hw:modal frame="modal">` or another frame host inside the view itself** instead of relying on a shared
   host. More flexible per-view, but you lose the convenience of one host serving every link.

## See also

- [Frame-or-page recipe](../recipes/frame-or-page.md) — the manual pattern this component encapsulates.
- [`<hw:modal>`](./modal.md), [`<hw:sheet>`](./sheet.md), and [`<hw:drawer>`](./drawer.md) — frame hosts that receive
  dynamic content.
- [`<hw:frame>`](./frame.md) — render a regular Turbo Frame with ergonomic aliases like `lazy` and `advance`.
- [`<hw:form>`](./form.md) — the `track-frame-src` variant that preserves the originating frame URL.
- [`turbo--frame-src` controller](../controllers/turbo/frame-src.md) — client-side fallback for frame-aware redirects.
