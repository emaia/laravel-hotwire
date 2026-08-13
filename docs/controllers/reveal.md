# Reveal

Coordinate CSS-owned entrance cascades with per-item viewport observation, finite animation settlement, Turbo lifecycle
safety, and JavaScript effects that must begin when an item is actually shown.

Use [`<hw:reveal>`](../components/reveal.md) for automatic indexing, first-paint structural CSS, validated options, and
Nova motion presets.

**Identifier:** `reveal`

**Load:** Automatically after `php artisan hotwire:install`; publish with
`php artisan hotwire:controllers reveal` only to customize it.

## Requirements

- No external dependencies.
- `IntersectionObserver` is required only for `trigger="scroll"`. Without the controller, CSS falls back to the load
  cascade and never strands content hidden.
- `Element.getAnimations()` is used to wait for finite subtree animations where available.

## Values

| Value        | Type    | Default                 | Description |
| ------------ | ------- | ----------------------- | ----------- |
| `trigger`    | String  | `load`                  | `scroll` arms only items outside the current viewport. |
| `threshold`  | Number  | `0.15`                  | Intersection ratio needed to release an armed item, capped at the ratio the viewport can show for tall items. |
| `rootMargin` | String  | `0px 0px -10% 0px`     | Margin applied to the item observer. |
| `once`       | Boolean | `true`                  | Stop observing an item after its first reveal. |

## Events

| Event          | Detail | Description |
| -------------- | ------ | ----------- |
| `reveal:shown` | none   | Fires for an initial visible batch and each coalesced scroll batch. |

## Markup contract

Use either direct-child mode:

```html
<section data-controller="reveal" data-reveal-children>
    <article>First</article>
    <article>Second</article>
</section>
```

or explicit item mode for nested units:

```html
<section data-controller="reveal">
    <header data-reveal-item style="--reveal-index: 0">Header</header>
    <form>
        <label data-reveal-item style="--reveal-index: 1">Title</label>
    </form>
</section>
```

Explicit items belonging to a nested `data-controller~="reveal"` root are excluded from the outer controller. CSS
supplies indexes for the first direct children before JavaScript loads; the controller fills missing indexes and keeps
new items synchronized after DOM mutations.

## Scroll lifecycle

For `trigger="scroll"`, the controller checks each item rather than the root. It arms only offscreen items by setting
`data-reveal-armed`, observes them individually, and removes the attribute when they intersect. Items released in the
same observer delivery are reindexed from zero. Calls to `reveal:shown` are coalesced into one animation frame.

A passive scroll listener releases any remaining armed items within two pixels of the document end. This prevents a
negative `rootMargin` from creating an unreachable dead zone below the final scroll position. With `once=false`, visible
items remain observed and are armed only after leaving the viewport.

## Settlement

After the final armed item is released, Reveal waits for the root to become renderable. Attribute mutations, native
toggle events, intersection changes, and window resizes recheck deferred roots without running a continuous animation
frame loop. This covers content inserted inside a modal or sheet before that overlay opens without completing its
cascade while hidden. Reveal then forces style resolution, collects subtree animations, and waits only for animations
whose computed iteration count is finite. Spinners, skeletons, and other infinite effects therefore cannot hold the
root open indefinitely.

Once the finite animations finish, the root receives `data-reveal-state="done"`. Structural CSS then removes stagger
from content inserted later. A generation token prevents asynchronous settlement from writing to a disconnected root.

## Turbo lifecycle

- The first `turbo:visit` marks `<html data-reveal-booted>` so `scope="document"` CSS does not replay persistent chrome.
- Before `replace`, `update`, or `morph` streams render, incoming Reveal items receive `data-reveal-skip`. Unrelated
  stream content is unchanged.
- Before Turbo caches the document, `data-reveal-state` and every armed item are cleared so a restored snapshot cannot
  remain invisible.
- Global visit and stream listeners are shared across instances and removed after the last Reveal disconnects.

## Controller composition

`fire()` restarts descendant `animated-number` controllers after dispatching `reveal:shown`. A one-frame retry handles
the race where that lazy controller chunk connects immediately after Reveal. Counters whose own lazy observer is still
pending keep that independent viewport policy instead of being forced to start by Reveal. Reduced motion skips the
restart.

Other controllers should listen to the event rather than be coupled into Reveal:

```html
<section
    data-controller="reveal chart-coordinator"
    data-action="reveal:shown->chart-coordinator#refresh"
>
    ...
</section>
```

## Cleanup

`disconnect()` removes document and window listeners, disconnects intersection and mutation observers, cancels pending
batch and counter frames, resolves tracked settlement frames, and invalidates any asynchronous settle operation. This
keeps Turbo morphs and revisits from accumulating callbacks.
