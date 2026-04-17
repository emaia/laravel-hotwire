# Optimistic Form

Dispatches Optimistic UI updates when a Turbo form is submitted. A single
controller on the `<form>` — no extra dispatch controller needed.

**Identifier:** `optimistic--form`

## Usage

```html
<form data-controller="optimistic--form" action="/posts/1/favorite" method="post">
    @csrf

    <x-hwc::optimistic target="post_1_favorite" action="update">
        ❤️ Favorited
    </x-hwc::optimistic>

    <button type="submit" id="post_1_favorite">
        🤍 Favorite
    </button>
</form>
```

On `turbo:submit-start`, the controller collects `FormData` from the form,
populates any `[data-field]` placeholders inside the optimistic templates,
tags the payload root with `data-optimistic`, and materialises `<turbo-stream>`
elements that Turbo executes immediately.

## Values

| Value   | Type      | Default | Description                                                                 |
|---------|-----------|---------|-----------------------------------------------------------------------------|
| `reset` | `Boolean` | `false` | Resets the form after a successful submission (`turbo:submit-end` with `success`). |

```html
<form data-controller="optimistic--form"
      data-optimistic--form-reset-value="true"
      action="/messages" method="post">
    @csrf
    <textarea name="content" placeholder="Write…" required></textarea>
    <button type="submit">Send</button>

    <x-hwc::optimistic target="messages" action="append">
        <article class="opacity-60">
            <p data-field="content"></p>
            <small>Sending…</small>
        </article>
    </x-hwc::optimistic>
</form>
```

## Behaviour

- Reacts only to submissions from its own `<form>` (`event.target === this.element`).
- Fires **before** the network request — instant DOM update.
- Passes `FormData` to the dispatcher for `[data-field]` population.
- Optionally resets the form on successful submission.
- Removes listeners on `disconnect()` (Turbo cache friendly).

## Notes

- Reconciliation is the server's job:
  `turbo_stream()->refresh(method: 'morph', scroll: 'preserve')`.
- See [`<x-hwc::optimistic>`](../../components/optimistic/readme.md) for the
  Blade component reference and a complete worked example.
- For link triggers use [`optimistic--link`](link.md).
- For custom triggers use [`optimistic--dispatch`](dispatch.md) or import
  `_dispatch.js` directly.
