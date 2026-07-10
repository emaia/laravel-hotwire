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

## Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `openDuration` | `number` | `300` | Open transition duration in milliseconds. |
| `closeDuration` | `number` | `300` | Close transition duration in milliseconds. |
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

## Events

| Event | When |
|-------|------|
| `drawer:opened` | After the open transition completes. |
| `drawer:closed` | After the close transition completes. |
