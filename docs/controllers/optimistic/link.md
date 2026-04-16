# Optimistic Link

Dispatches Optimistic UI updates when a Turbo-driven link is clicked. A single
controller on the `<a>` — no extra dispatch controller needed.

**Identifier:** `optimistic--link`

## Usage — frame navigation with skeleton

```html
<a href="/posts/42" data-turbo-frame="detail"
   data-controller="optimistic--link">
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

The `remove` target should be the list item wrapper, **not** the link itself:

```html
<li id="{{ dom_id($n) }}">
    {{ $n->title }}

    <a href="/notifications/{{ $n->id }}/dismiss"
       data-turbo-method="delete"
       data-controller="optimistic--link">
        Dispensar

        <x-hwc::optimistic :target="dom_id($n)" action="remove" />
    </a>
</li>
```

The `<li>` disappears immediately. The server's morph `refresh` confirms the
deletion; on failure it restores the row.

## Click guard

The dispatch is **only** triggered when Turbo will actually handle the click.
It is skipped for:

- Modifier clicks (Cmd/Ctrl/Shift/Alt) — the browser opens a new tab.
- Middle-click / non-primary buttons.
- Links with `target="_blank"` or any non-`_self` target.
- Links with `data-turbo="false"`.
- Events whose `defaultPrevented` is already `true`.

## Notes

- Unlike `optimistic--form`, no `FormData` is passed — `[data-field]`
  population does not apply.
- For form submissions use [`optimistic--form`](form.md).
- For custom triggers use [`optimistic--dispatch`](dispatch.md) or import
  `_dispatch.js` directly.
