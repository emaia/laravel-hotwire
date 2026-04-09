# Toaster

Initializes the [Sonner](https://sonner.emilkowal.ski/) container once on the page. Should be added to the global layout so the `notification--toast` controller can fire toasts.

**Identifier:** `notification--toaster`

## Requirements

- `@emaia/sonner`

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `close-button` | `Boolean` | `true` | Shows X button on each toast |
| `duration` | `Number` | `4000` | Duration of each toast (ms) |
| `expand` | `Boolean` | `false` | Expands all toasts instead of stacking |
| `invert` | `Boolean` | `false` | Inverts the theme colors |
| `position` | `String` | `"bottom-center"` | Position on screen |
| `rich-colors` | `Boolean` | `true` | Uses rich colors for toast types |
| `theme` | `String` | `"light"` | Theme: `light`, `dark`, `system` |
| `visible-toasts` | `Number` | `3` | Maximum number of toasts visible at once |

## Basic usage

Add once in the application layout, before the closing `</body>`:

```html
<body>
    ...

    <div data-controller="notification--toaster"></div>
</body>
```

## With custom configuration

```html
<div
    data-controller="notification--toaster"
    data-notification--toaster-position-value="top-right"
    data-notification--toaster-duration-value="6000"
    data-notification--toaster-theme-value="dark"
    data-notification--toaster-rich-colors-value="true"
></div>
```

## Available positions

`top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`

## How it works

The controller creates the Sonner instance at `window.toaster` the first time it connects. If `window.toaster` already exists (e.g. hot reload), initialization is skipped to avoid duplication.
