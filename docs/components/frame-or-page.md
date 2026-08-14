# Frame-or-page

Render a view as a Turbo Frame payload when the request came from a frame, or as a full page wrapped
in a layout when the user loaded the URL directly. One view, one controller, no duplication.

This component is the declarative form of the [frame-or-page recipe](../recipes/frame-or-page.md).

## Basic Usage

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

| Prop     | Type                    | Default | Description                                                                                    |
|----------|-------------------------|---------|------------------------------------------------------------------------------------------------|
| `frame`  | `string\|object\|null` | `null`  | One frame id. Accepts a string or any object resolvable via `dom_id()`.                         |
| `frames` | `?iterable`             | `null`  | A non-empty list of frame id strings or objects resolvable via `dom_id()`.                     |
| `layout` | `?string`               | `null`  | Blade component name (e.g. `dashboard` or `layouts.dashboard`) or class-string of the wrapper. |

Provide exactly one of `frame` or `frames`. Empty ids and non-list `frames` values throw
`InvalidArgumentException`. Configuring more than one frame requires `layout` because direct navigation
and unknown frame headers need a page presentation. Arrays, Collections, and other iterables are accepted;
their keys must form a zero-based list.

When one frame is configured and `layout` is `null`, the component preserves frame-only mode: it always
renders the configured `<turbo-frame>`, regardless of the request header. This is useful for nested frames
that never need a standalone presentation.

Simple layout names resolve ergonomically: `layout="dashboard"` uses an existing `dashboard` component
when one is registered, otherwise it tries `layouts.dashboard` before falling back to the original value.
Names that already contain `.`, `::`, or `\` are used as-is. A blank layout is normalized to `null` and
therefore enables frame-only mode.

## Lazy context content

Content directly inside the parent is shared. Put context-only content in the renderless
`<hw:frame-or-page.frame>` and `<hw:frame-or-page.page>` subcomponents:

```blade
{{-- resources/views/parks/topics/edit.blade.php --}}
<hw:frame-or-page frame="modal" layout="dashboard">
    <hw:frame-or-page.page>
        @include('parks._edit_header')
        @include('parks._edit_navigation', ['active' => 'topics'])
    </hw:frame-or-page.page>

    @include('parks.topics._form')

    <hw:frame-or-page.frame>
        <hw:modal.close as="button">Cancel</hw:modal.close>
    </hw:frame-or-page.frame>

    <hw:frame-or-page.page>
        @include('parks.topics._list')
    </hw:frame-or-page.page>
</hw:frame-or-page>
```

These are class components whose `shouldRender()` decision runs before Blade evaluates their body.
Includes, queries, pushed assets, and other side effects in the discarded branch do not run. They emit no
wrapper element around a branch that does render, so HTML attributes such as `class`, `id`, and `data-*`
are rejected when that branch is active. Put those attributes on an element inside the branch. A discarded
branch is not evaluated, so its attributes are ignored together with its body.

The selection rules are:

- Shared parent content renders in both modes.
- `.frame` without `target` renders for any configured frame when a frame is active.
- `.frame target="..."` renders only for that active frame. The target must be declared by the parent's
  `frame` or `frames` prop.
- `.page` renders for direct navigation or a `Turbo-Frame` header that does not match a configured frame.
- With one frame and no layout, frame-only mode is always active and `.page` is not evaluated.
- Contextual subcomponents use their nearest `<hw:frame-or-page>` ancestor and throw when used without one.

The removed `frameContent` and `pageContent` named slots are not compatibility aliases. Replace them with
the lazy subcomponents; stale slots throw with migration guidance.

## Multiple frame hosts

One route can serve different frame hosts while sharing its main content:

```blade
<hw:frame-or-page :frames="['modal', 'settings-panel']" layout="dashboard">
    <hw:frame-or-page.frame target="modal">
        <hw:modal.close as="button">Close modal</hw:modal.close>
    </hw:frame-or-page.frame>

    <hw:frame-or-page.frame target="settings-panel">
        <hw:sheet.close as="button">Close sheet</hw:sheet.close>
    </hw:frame-or-page.frame>

    <hw:frame-or-page.page>
        <x-page-heading>Edit message</x-page-heading>
    </hw:frame-or-page.page>

    @include('messages._edit-form')

    <hw:frame-or-page.frame>
        <hw:toast />
    </hw:frame-or-page.frame>
</hw:frame-or-page>
```

`Turbo-Frame: modal` emits `<turbo-frame id="modal">`; `Turbo-Frame: settings-panel` emits
`<turbo-frame id="settings-panel">`. Direct navigation and unknown headers render `dashboard` instead.

## Forwarded attributes

When the component renders **as a frame** — that is, when the request came from a Turbo Frame OR
when `layout` is omitted — extra HTML attributes on `<hw:frame-or-page>` are forwarded to the
inner [`<hw:frame>`](./frame.md). This includes native Turbo Frame attributes (`src`, `loading`,
`target`, `refresh`, `autoscroll`, …), frame aliases like `lazy`, `advance`, `replace`, `poll`,
`view-transition`, and arbitrary `data-*` hooks:

```blade
<hw:frame-or-page frame="messages" src="{{ route('messages.index') }}" loading="lazy" view-transition>
    <div class="loading">Loading…</div>
</hw:frame-or-page>
```

Forwarding configures the frame emitted by this response. It does not configure a pre-existing frame host: Turbo's
`FrameRenderer` preserves that host and replaces only its children instead of copying response-frame attributes. For a
layout-owned host, enable the integration there, for example
`<hw:modal frame="modal" view-transition>`, `<hw:sheet frame="settings-panel" view-transition>`, or
`<hw:drawer frame="drawer-panel" view-transition>`.

On **direct navigation with a `layout`**, the slot is rendered directly inside the layout component
with no surrounding `<turbo-frame>`, so frame-specific attributes like `src` / `loading` have no
target and are dropped. If you need a frame around your content on direct nav (rare — usually the
layout's host frame is enough), add an explicit `<x-turbo::frame>` inside the slot.

The component does **not** forward attributes to the layout. The layout is your own component —
configure it the way you'd configure any other Blade layout:

- **A route-specific layout component** if the value is fixed per route. Keep that wrapper in the
  `layout` prop so frame requests can omit it:
  ```blade
  {{-- resources/views/components/layouts/message-edit.blade.php --}}
  <x-layouts.dashboard title="Edit message" :fixed-top="true">
      {{ $slot }}
  </x-layouts.dashboard>

  {{-- resources/views/messages/edit.blade.php --}}
  <hw:frame-or-page frame="modal" layout="layouts.message-edit">
      @include('messages._edit-form')
  </hw:frame-or-page>
  ```
  Do not wrap `<hw:frame-or-page>` externally: an outer layout is evaluated before the component and
  therefore cannot be omitted from a frame response.
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

## Returning validation errors to the frame source

The visible page URL is not necessarily the URL that rendered a frame. A task board at `/tasks` may load an edit form
from `/tasks/42/edit` into `modal`, then submit the mutation to `PUT /tasks/42`. On validation failure, redirecting to
the board would make the form disappear because the response does not repopulate the modal frame.

Use `track-frame-src` on the form so the frame-rendering request URL is sent as `_turbo_frame_src`:

```blade
<hw:frame-or-page frame="modal" layout="dashboard">
    <hw:form :action="route('tasks.update', $task)" method="put" track-frame-src>
        {{-- fields --}}
    </hw:form>
</hw:frame-or-page>
```

`TurboFormRequest` redirects validation failures back to that explicit, sanitized source. The
[`turbo--frame-src`](../controllers/turbo/frame-src.md) controller can provide the same context through a header when
the hidden input is unavailable, but the server-rendered input remains the deterministic primary source.

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
