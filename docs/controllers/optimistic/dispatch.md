# Optimistic Dispatch (escape hatch)

Thin Stimulus controller wrapping the shared `_dispatch.js` module. Exposes a
single `dispatch()` action for triggers that don't fit `optimistic--form` or
`optimistic--link`.

**Identifier:** `optimistic--dispatch`

## When to use

Most apps only need `optimistic--form` or `optimistic--link`. Use
`optimistic--dispatch` when you have a custom Stimulus controller and want to
trigger the optimistic update in response to an arbitrary event:

```html
<div data-controller="optimistic--dispatch my-custom"
     data-action="my-custom:complete->optimistic--dispatch#dispatch">
    <template data-optimistic-stream data-optimistic-action="replace"
              data-optimistic-target-id="x">…</template>
</div>
```

Or call the shared module directly in your own controller:

```js
import { dispatchOptimistic } from "./optimistic/_dispatch";

// Inside any Stimulus controller
dispatchOptimistic(this.element, { formData });
```

## Template attributes

| Attribute                      | Description                                                              |
|--------------------------------|--------------------------------------------------------------------------|
| `data-optimistic-stream`       | Marker — required for the dispatcher to pick up the template             |
| `data-optimistic-action`       | Turbo Stream action (default `replace`)                                  |
| `data-optimistic-target-id`    | DOM id of the element to act on                                          |
| `data-optimistic-targets`      | CSS selector (alternative to `target-id`)                                |

## Features

- **`[data-field]` population** — when `formData` is provided, descendants
  with `data-field="<name>"` are populated via `textContent` (XSS-safe).
- **`data-optimistic` marker** — automatically added to every top-level
  payload element for CSS styling hooks.

## Reconciliation

The server's Turbo Stream response reconciles the DOM.
`turbo_stream()->refresh(method: 'morph')` works best — the morph converges to
the authoritative state on success **and** reverts on failure.
