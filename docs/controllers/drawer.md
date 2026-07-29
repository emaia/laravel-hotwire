# Drawer Controller

Controls an off-canvas drawer overlay.

**Identifier:** `drawer`

## Targets

| Target | Description |
|--------|-------------|
| `trigger` | Optional trigger used for focus return. |
| `modal` | Full-screen overlay container. |
| `backdrop` | Backdrop click target. |
| `dialog` | Sliding drawer panel. |
| `dynamicContent` | Optional Turbo Frame that opens the drawer when content loads. |
| `loadingTemplate` | Optional loading template used while the dynamic frame fetches. |

## Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `lockScroll` | `boolean` | `true` | Lock body scroll while open. |
| `closeOnEscape` | `boolean` | `true` | Close on Escape. |
| `closeOnClickOutside` | `boolean` | `true` | Close on backdrop click. |

## Actions

| Action | Description |
|--------|-------------|
| `open` | Open the drawer. |
| `close` | Close the drawer. |
| `toggle` | Toggle the drawer. |
| `clickOutside` | Close from the backdrop when enabled. |
| `closeForCache` | Close immediately before Turbo caches the page. |

## Dynamic Frame Behavior

When `dynamicContent` is present, the controller opens the drawer after the frame receives content and clears the frame after close. It injects `loadingTemplate` during `turbo:before-fetch-request`, supports per-link `data-loading-template`, and delays empty `update`/`replace` streams for the drawer root or frame, plus `refresh` streams, until the close animation finishes.

The `modal` target must start as `data-state="closed" data-motion="default" hidden inert`. Presence derives lifecycle
completion from CSS motion on `backdrop` and `dialog`; `data-motion="none"` and reduced motion skip it. Use direct-child
state selectors when drawers can be nested.

## Events

| Event | When |
|-------|------|
| `drawer:opened` | After the open transition completes. |
| `drawer:closed` | After the close transition completes. |
