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
- [Composing streams](./composing-streams.md) — chain `refresh`, `closeModal`, `flash` and friends to
  describe the full UI transition in a single response.

## See also

- [Components catalog](../../README.md#components) — the Blade primitives the recipes build on.
- [Registry](../registry.md) — where the package metadata lives.
