# Form Optimistic

Trigger wrapper that dispatches Optimistic UI updates when a Turbo form is
submitted. Pair it on the same `<form>` with the `optimistic--dispatch` core
controller.

**Identifier:** `form--optimistic`

## Requirements

- `optimistic--dispatch` must be on the same element.
- Turbo 8+ (morphs) is recommended for seamless reconciliation.

## Usage

```html
<form
    data-controller="optimistic--dispatch form--optimistic"
    action="/posts/1/favorite"
    method="post"
>
    @csrf

    <x-hwc::optimistic target="post_1_favorite">
        <button class="favorited">❤️ Favorited</button>
    </x-hwc::optimistic>

    <div id="post_1_favorite">
        <button>🤍 Favorite</button>
    </div>
</form>
```

On `turbo:submit-start`, this controller invokes `optimistic--dispatch#dispatch()`,
which materialises every `<x-hwc::optimistic>` template inside the form as a
live `<turbo-stream>`.

## Values

| Value   | Type      | Default | Description                                                                 |
|---------|-----------|---------|-----------------------------------------------------------------------------|
| `reset` | `Boolean` | `false` | When `true`, resets the form after a successful submission (`turbo:submit-end` with `success=true`). |

```html
<form
    data-controller="optimistic--dispatch form--optimistic"
    data-form--optimistic-reset-value="true"
    action="/messages"
    method="post"
>
    …
</form>
```

## Behaviour

- Reacts only to submissions originating from its own `<form>` (`event.target === this.element`).
- Fires before the network request (instant DOM update).
- Passes the form's `FormData` to `optimistic--dispatch` so templates can populate `[data-field]` descendants with user input.
- Optionally resets the form on successful submission.
- Removes its listeners on `disconnect()` (Turbo cache friendly).

## Notes

- Reconciliation is the server's job. Respond with
  `turbo_stream()->refresh(method: 'morph', scroll: 'preserve')` and the
  optimistic mutation converges to the authoritative state on success or
  reverts on failure.
- See [`optimistic--dispatch`](../optimistic/dispatch.md) for template
  attributes and direct API.
- See [`<x-hwc::optimistic>`](../../components/optimistic/readme.md) for the
  Blade component and a complete worked example.
