# Scroll Progress

Fixed progress bar that fills based on the user's scroll position.

## Usage

Render it once in your layout:

```blade
<x-hwc::scroll-progress />
```

The component renders a fixed top bar and wires the `scroll-progress` Stimulus controller.

## Custom styling

Use regular Blade attributes for visual customization:

```blade
<x-hwc::scroll-progress class="h-2 bg-blue-500" />
```

The component sets `data-controller="scroll-progress"` itself. Passing a `data-controller` attribute is ignored so the
required controller binding cannot be replaced accidentally.

## Throttle delay

The scroll listener is throttled by default:

```blade
<x-hwc::scroll-progress :throttle-delay="50" />
```

Set it to `0` to disable throttling:

```blade
<x-hwc::scroll-progress :throttle-delay="0" />
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `throttle-delay` | `int` | `15` | Throttle delay in milliseconds. Use `0` to disable throttling. |

## Controller

This component depends on the [`scroll-progress`](../controllers/scroll-progress.md) controller.
