# Optimistic

Apply Optimistic UI updates when a Turbo form is submitted. The controller listens for `turbo:submit-start` and
materializes any `<template data-form--optimistic-target="stream">` children as live `<turbo-stream>` elements, so the
DOM updates **before** the server round-trip.

Reconciliation is handled by the server response (a `turbo-stream` `refresh` using Turbo 8 morphs works best): on
success the DOM converges to the authoritative state, on failure it reverts — no manual rollback.

**Identifier:** `form--optimistic`

## Requirements

- Turbo 8+ (morphs) is recommended for seamless reconciliation.
- The server must respond with a Turbo Stream (or morph refresh) that matches the same targets.

## Targets

| Target   | Description                                                              |
|----------|--------------------------------------------------------------------------|
| `stream` | A `<template>` containing the optimistic HTML for a single stream action |

Each `stream` template declares the action via data attributes:

| Attribute                      | Description                                                      |
|--------------------------------|------------------------------------------------------------------|
| `data-optimistic-action`       | Turbo Stream action (`replace`, `append`, `remove`, …). Default `replace`. |
| `data-optimistic-target-id`    | DOM id of the element to act on                                  |
| `data-optimistic-targets`      | CSS selector (alternative to `target-id`)                        |

> Prefer the `<x-hwc::optimistic>` Blade component — it emits the template with these attributes for you.

## Basic usage (raw markup)

```html
<form data-controller="form--optimistic" action="/posts/1/favorite" method="post">
    @csrf

    <template
        data-form--optimistic-target="stream"
        data-optimistic-action="replace"
        data-optimistic-target-id="post_1_favorite"
    >
        <button class="favorited">❤️ Favorited</button>
    </template>

    <div id="post_1_favorite">
        <button>🤍 Favorite</button>
    </div>
</form>
```

## How it works

1. User submits the form.
2. The controller clones every `stream` template into `document.body` as a `<turbo-stream>` element.
3. Turbo executes the stream synchronously → the UI updates immediately.
4. The server responds. Two conventional flows:
    - **Success:** respond with a `turbo-stream` `refresh` (or explicit `replace`) that morphs the DOM to the authoritative state.
    - **Failure:** respond with a `refresh` as well — the morph reverts the optimistic mutation. Surface an error
      via `<x-hwc::flash-message>` or a `notification--toast` stream.

## Notes and caveats

- The controller only reacts to submissions originating from its own `<form>` element (scoped to `event.target === this.element`).
- Idiomorph does **not** morph `<template>` contents (see [idiomorph#15](https://github.com/bigskysoftware/idiomorph/issues/15)).
  If you need the optimistic template to flip (e.g. toggle favorite/unfavorite), re-render the full form from the server
  so the template arrives with fresh content. The Blade component makes this trivial.
- Use stable, authorization-gated DOM ids (`dom_id($model, 'favorite')`). Never build stream targets from untrusted input.
- Works with `append`, `prepend`, `replace`, `update`, `remove`, `before`, `after`, and `refresh` actions.
