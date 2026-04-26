# Cookbook

Patterns and recipes for combining Laravel Hotwire components with Turbo Streams to build server-driven UIs.

Each recipe is self-contained — pick the one that matches your use case.

## Recipes

- [Frame-or-page views](./frame-or-page.md) — render the same view as a full page **or** as a Turbo Frame
  modal payload. URL-addressable, refresh-safe, no view duplication.
- [Server-driven dialogs](./server-driven-dialogs.md) — when to open/close dialogs via Turbo Frames vs
  Turbo Streams, with examples for both paths.
- [Composing streams](./composing-streams.md) — chain `refresh`, `closeDialog`, `flash` and friends to
  describe the full UI transition in a single response.

## See also

- [Components catalog](../../README.md#components) — the Blade primitives the recipes build on.
- [Registry](../registry.md) — where the package metadata lives.
