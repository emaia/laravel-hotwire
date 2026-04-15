# Optimistic Dispatch (core)

Core dispatcher for Optimistic UI. Scans its element subtree for
`<template data-optimistic-stream>` nodes and converts each into a live
`<turbo-stream>` that Turbo executes synchronously, updating the DOM
**before** any network round-trip.

This controller does **not** bind to any event by itself. It exposes a single
public method, `dispatch()`, called by *trigger wrappers* (`form--optimistic`,
`link--optimistic`) that decide *when* to fire the optimistic update.

**Identifier:** `optimistic--dispatch`

## Architecture

```
optimistic--dispatch  ← scans <template data-optimistic-stream> and emits <turbo-stream>
        ↑ dispatch()
   ┌────┴─────────┐
form--optimistic   link--optimistic
(turbo:submit-start)   (click)
```

The Blade component `<x-hwc::optimistic>` declares a dependency on
`optimistic--dispatch` only — the trigger wrapper is your choice and you add it
yourself on the host element (`<form>` or `<a>`).

## Template attributes (read by `dispatch()`)

| Attribute                      | Description                                                              |
|--------------------------------|--------------------------------------------------------------------------|
| `data-optimistic-stream`       | Marker — required for the dispatcher to pick up the template             |
| `data-optimistic-action`       | Turbo Stream action (default `replace`)                                  |
| `data-optimistic-target-id`    | DOM id of the element to act on                                          |
| `data-optimistic-targets`      | CSS selector (alternative to `target-id`)                                |

Most users emit these via `<x-hwc::optimistic>` instead of writing them by hand.

## Direct usage (advanced)

If neither `form--optimistic` nor `link--optimistic` fits, you can call the
dispatcher manually from your own Stimulus controller:

```js
const dispatcher = this.application.getControllerForElementAndIdentifier(
    this.element,
    "optimistic--dispatch",
);
dispatcher?.dispatch();
```

## Reconciliation

The optimistic mutation is provisional. The server's Turbo Stream response
reconciles the DOM. Use `turbo_stream()->refresh(method: 'morph')` so the morph
algorithm converges to the authoritative state on success **and** reverts on
failure — no manual rollback.
