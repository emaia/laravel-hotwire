# Roadmap

Ideas under consideration for future releases. Nothing here is committed —
each item lists the **trigger** (when it pays off) and a **proposed shape** so
contributors and users can weigh in.

## Optimistic UI extensions

The current Optimistic UI surface (`optimistic--form` + `optimistic--link`
+ `optimistic--dispatch` + `<x-hwc::optimistic>`) covers the most common cases:
forms, frame links and method links reconciled via Turbo 8 morph. The items
below extend the same model.

### 1. Rollback opt-in (snapshot)

**Trigger.** Endpoints that cannot return `turbo_stream()->refresh()` — fire-and-forget
actions, broadcast-only flows, integrations that respond with `204 No Content`.

**Proposed shape.** A new prop on the Blade component:

```blade
<x-hwc::optimistic :target="dom_id($todo)" action="remove" :snapshot="true" />
```

When `:snapshot="true"` is set, `optimistic--dispatch` captures the
`outerHTML` of the target *before* applying the optimistic stream. On
`turbo:fetch-request-error` (or a configurable signal), it restores the
snapshot. No retry, no state machine — just one undo step.

**Risk.** Dual sources of truth (snapshot vs. server response) can flicker if
both arrive. Mitigation: only restore when no successful stream landed first.

### 2. Client-side ULID for optimistic inserts

**Trigger.** Lists where a new row is appended optimistically (chat messages,
comments, kanban cards, audit log entries) and the server later returns a
record with a real id.

**Proposed shape.** Two pieces:

- A Blade helper `dom_id_optimistic($class, $clientId)` that produces the same
  id as `dom_id()` would given the persisted record.
- A `:client-id` prop on `<x-hwc::optimistic>` that auto-generates a ULID and
  injects a hidden `client_id` input into the surrounding form.

```blade
<x-hwc::optimistic :target="dom_id_optimistic(Message::class, $ulid)" action="append">
    <div id="...">{{ $body }}</div>
</x-hwc::optimistic>
```

The server reads `client_id`, persists the record with that ULID as the key
suffix, and the morph response converges on the exact same DOM id — no
duplication, no flicker.

### 3. Sortable / drag-and-drop persistence

**Trigger.** Reorderable lists (kanban columns, todo priorities, gallery
ordering). Different mental model from the current optimistic flow: the
mutation already happened locally (Sortable moved the node) and the wire
operation is just *persistence*.

**Proposed shape.** A new dedicated controller, **not** part of the
`optimistic` family:

```html
<ul data-controller="sortable--persist"
    data-sortable--persist-url-value="/boards/1/columns/reorder"
    data-sortable--persist-handle-value=".drag-handle">
    @foreach ($cards as $card)
        <li data-sortable-id="{{ $card->id }}">…</li>
    @endforeach
</ul>
```

`onEnd` posts the new order and, on error, restores the previous arrangement.
SortableJS becomes an optional peer dependency.

### 4. Prefetch + optimistic compounding

**Trigger.** Latency-sensitive flows where Turbo Drive's preload-on-hover
already brings the destination HTML before the click. Optimistic UI complements
it: paint the target state on hover *intent*, navigate on click.

**Proposed shape.** An `optimistic--link-on-hover` variant that fires
`dispatch()` on `mouseenter` after a configurable intent delay, and rolls back
if the click does not happen within a window.

**Risk.** Easy to overuse. Documented as a power-user opt-in only.

### 5. Broadcast-aware dispatch (cross-tab safety)

**Trigger.** Apps using Reverb / Mercure to broadcast streams to all sessions.
Without coordination, the originating tab applies the optimistic stream *and*
receives the broadcast — risk of double-apply.

**Proposed shape.** `optimistic--dispatch` tags every emitted `<turbo-stream>`
with a `data-optimistic-request-id` matching the value Turbo sends as
`Turbo-Request-Id`. The server forwards the id when broadcasting, and a small
listener on the originating tab swallows broadcasts whose id matches a
recently-applied optimistic stream.

This also pairs naturally with `turbo_stream()->refresh(requestId: ...)` for
debouncing.

### 6. External template reference

**Trigger.** Lists where the same optimistic fragment is reused across many
forms (e.g. chat threads with one input per conversation). Today each form
must inline its own `<x-hwc::optimistic>`, which is DRY-hostile.

**Proposed shape.** A `:from` prop that references the id of a sibling
`<template>`, following the pattern from Rails Designer's `data-optimistic-template`:

```blade
<template id="message-template">
    <article>
        <p data-field="content"></p>
        <small>Sending…</small>
    </article>
</template>

<x-hwc::optimistic target="messages" action="append" from="message-template" />
```

The Stimulus dispatcher reads the template from the document instead of from
its own subtree. Keeps the current "inline" form as the default.

### 7. `data-field` populating attributes (not just textContent)

**Trigger.** Optimistic fragments that include links, images, or form-linked
metadata where the dynamic piece belongs in an attribute, not text content.

**Proposed shape.** Extend the `data-field` convention:

```html
<a data-field-attr="href:permalink"><span data-field="title"></span></a>
<img data-field-attr="src:avatar_url" data-field-attr-alt="title">
```

Values come from FormData like today. Still `textContent` for plain fields,
`setAttribute` for the attribute-scoped variant. Must document carefully —
attributes built from untrusted input are a larger XSS surface than text.

### 8. Generic event-driven trigger

**Trigger.** Triggers that aren't `<form>` submissions or link clicks —
keyboard shortcuts, custom Stimulus events, `change` on inputs, `intersect`
observers.

**Proposed shape.** A thin wrapper `event--optimistic` that takes
`data-event--optimistic-on-value="custom:event-name"` and forwards to
`optimistic--dispatch`. Keeps the surface tiny while covering the long tail.

## Other ideas

### View Transitions composition

The existing `frame--view-transition` controller already wraps individual
frame renders. A higher-level helper that wires View Transitions across an
optimistic → reconciled swap (so the morph crossfades smoothly) would polish
the experience for free on supporting browsers.

### TypeScript-first controller scaffolding

`hotwire:make-controller --ts` already exists. A follow-up: ship every package
controller as `.ts` source with generated `.js` so adopters can import either
flavour without a transpile step.

### `hotwire:doctor` command

A diagnostic command that inspects the app's published controllers, Stimulus
loader configuration, Vite glob, and Blade component prefix to surface common
misconfigurations (missing controller, stale published copy, identifier typo).

---

Have a use case that doesn't fit any of the above? Open an issue describing
the trigger and the desired DX — concrete examples shape the API.
