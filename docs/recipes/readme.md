# Cookbook

Patterns and recipes for combining Laravel Hotwire components with Turbo Streams to build server-driven UIs.

Each recipe is self-contained — pick the one that matches your use case.

## Recipes

- [Modal patterns](./modal-patterns.md) — three real-world ways to wire a modal (inline,
  layout-shared, static) and when to drop to the raw controller.
- [Frame-or-page views](./frame-or-page.md) — render the same view as a full page **or** as a Turbo Frame
  modal payload. URL-addressable, refresh-safe, no view duplication.
- [Server-driven modals](./server-driven-modals.md) — when to open/close modals via Turbo Frames vs
  Turbo Streams, with examples for both paths.
- [Server-driven confirmation](./server-driven-confirmation.md) — destructive actions where the
  server paints the confirmation modal with computed context (counts, policy warnings, type-the-name
  guards).
- [Multi-stage forms](./multi-stage-forms.md) — wizards built on a persistent draft model and a
  single Turbo Frame. Per-step validation, browser back, resume-where-you-left-off, no client state.
- [Composing streams](./composing-streams.md) — chain `refresh`, `update`, `flash` and friends to
  describe the full UI transition in a single response.
- [Carousel patterns](./carousel-patterns.md) — thumbnail nav, lightbox modal, infinite Turbo Stream slides,
  URL-deep-linked slides — built on the `carousel` controller.
- [Carousel as a primitive](./carousel-as-primitive.md) — multi-step wizard, server-driven signage, swipe
  deck and real-time presence — when the carousel is the snap engine, not the gallery.
- [Accordion](./accordion.md) — compose multiple `disclosure` controllers into an accordion, with
  patterns for independent or single-open behavior and server-driven initial state.

## See also

- [Components catalog](../../README.md#blade-components) — the Blade primitives the recipes build on.
- [Registry](../registry.md) — where the package metadata lives.
