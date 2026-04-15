# Link Optimistic

Trigger wrapper that dispatches Optimistic UI updates when a Turbo-driven link
is clicked. Pair it on the same `<a>` with the `optimistic--dispatch` core
controller.

**Identifier:** `link--optimistic`

## Requirements

- `optimistic--dispatch` must be on the same element.
- Designed for links handled by Turbo: `data-turbo-frame`, `data-turbo-method`,
  or regular Turbo Drive navigation.

## Usage — frame navigation with skeleton

```html
<a
    href="/posts/42"
    data-turbo-frame="detail"
    data-controller="optimistic--dispatch link--optimistic"
>
    Ver detalhes

    <x-hwc::optimistic target="detail" action="update">
        <div class="animate-pulse p-4">Carregando…</div>
    </x-hwc::optimistic>
</a>

<turbo-frame id="detail"></turbo-frame>
```

The skeleton is painted into `#detail` the moment the link is clicked. The
real frame response replaces it.

## Usage — method link with optimistic remove

```html
<a
    href="/notifications/{{ $n->id }}/dismiss"
    data-turbo-method="delete"
    data-controller="optimistic--dispatch link--optimistic"
>
    Dispensar

    <x-hwc::optimistic :target="dom_id($n)" action="remove" />
</a>
```

The notification disappears immediately. The server's `turbo-stream refresh`
(or explicit `remove`) confirms the deletion. If the request fails, the morph
restores the row.

## Click guard

The optimistic update is **only** dispatched when Turbo will actually handle
the click. The dispatch is skipped for:

- Modifier clicks (Cmd/Ctrl/Shift/Alt) — the browser opens a new tab.
- Middle-click / non-primary buttons.
- Links with `target="_blank"` or any non-`_self` target.
- Links with `data-turbo="false"`.
- Events whose `defaultPrevented` is already `true`.

This keeps the optimistic state from drifting from real navigation.

## Notes

- See [`optimistic--dispatch`](../optimistic/dispatch.md) for the template
  attributes and direct API.
- For form submissions use [`form--optimistic`](../form/optimistic.md).
