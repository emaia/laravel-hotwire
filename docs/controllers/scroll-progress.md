# Scroll Progress

Displays a progress bar that fills based on the user's scroll position.

**Identifier:** `scroll-progress`

## Requirements

- No external dependencies.

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `throttle-delay` | `Number` | `15` | Throttle delay (ms) on the scroll event. Use `0` to disable |

## Basic usage

```html
<div
    data-controller="scroll-progress"
    class="fixed top-0 left-0 h-1 bg-indigo-500"
></div>
```

The bar fills horizontally as the user scrolls down the page.

## With custom throttle delay

```html
<div
    data-controller="scroll-progress"
    data-scroll-progress-throttle-delay-value="50"
    class="fixed top-0 left-0 h-2 bg-blue-500"
></div>
```

## Styling

The controller updates `width` from `0%` to `100%`. Use CSS for positioning and colors:

```html
<div
    data-controller="scroll-progress"
    class="fixed top-0 left-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500 transition-all"
></div>
```