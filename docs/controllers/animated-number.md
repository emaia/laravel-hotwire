# Animated Number

Animates a number from a start value to an end value over a given duration. Supports lazy mode — the animation only starts when the element scrolls into view.

**Identifier:** `animated-number`  
**Install:** `php artisan hotwire:controllers animated-number`

## Requirements

- No external dependencies. Uses `requestAnimationFrame` and (for lazy mode) `IntersectionObserver`.

## Stimulus Values

| Value              | Type      | Default | Description                                                                  |
|--------------------|-----------|---------|------------------------------------------------------------------------------|
| `start`            | `Number`  | —       | The number the animation begins from                                         |
| `end`              | `Number`  | —       | The number the animation ends at                                             |
| `duration`         | `Number`  | —       | Animation duration in milliseconds                                           |
| `lazy`             | `Boolean` | `false` | When `true`, defers the animation until the element enters the viewport      |
| `lazy-threshold`   | `Number`  | —       | IntersectionObserver `threshold` (0–1). Only used when `lazy` is `true`     |
| `lazy-root-margin` | `String`  | `"0px"` | IntersectionObserver `rootMargin`. Only used when `lazy` is `true`          |

## Basic usage

```html
<span
    data-controller="animated-number"
    data-animated-number-start-value="0"
    data-animated-number-end-value="1500"
    data-animated-number-duration-value="1000"
>
    1500
</span>
```

The element content is replaced with the animated value on connect. Setting the final number as initial content provides a server-rendered fallback before Stimulus loads.

## Lazy mode — animate on scroll

```html
<span
    data-controller="animated-number"
    data-animated-number-start-value="0"
    data-animated-number-end-value="42000"
    data-animated-number-duration-value="1500"
    data-animated-number-lazy-value="true"
>
    42000
</span>
```

The animation starts only once the element becomes visible, not on page load.

## With custom intersection threshold

```html
<span
    data-controller="animated-number"
    data-animated-number-start-value="0"
    data-animated-number-end-value="99"
    data-animated-number-duration-value="800"
    data-animated-number-lazy-value="true"
    data-animated-number-lazy-threshold-value="0.5"
    data-animated-number-lazy-root-margin-value="-100px"
>
    99
</span>
```

A threshold of `0.5` means the animation starts when 50% of the element is visible.

## Stats section example

```html
<div class="grid grid-cols-3 gap-8 text-center">
    <div>
        <p class="text-4xl font-bold">
            <span
                data-controller="animated-number"
                data-animated-number-start-value="0"
                data-animated-number-end-value="12000"
                data-animated-number-duration-value="1200"
                data-animated-number-lazy-value="true"
            >12000</span>+
        </p>
        <p>Users</p>
    </div>
    <div>
        <p class="text-4xl font-bold">
            <span
                data-controller="animated-number"
                data-animated-number-start-value="0"
                data-animated-number-end-value="350"
                data-animated-number-duration-value="1000"
                data-animated-number-lazy-value="true"
            >350</span>
        </p>
        <p>Projects</p>
    </div>
    <div>
        <p class="text-4xl font-bold">
            <span
                data-controller="animated-number"
                data-animated-number-start-value="0"
                data-animated-number-end-value="99"
                data-animated-number-duration-value="800"
                data-animated-number-lazy-value="true"
            >99</span>%
        </p>
        <p>Satisfaction</p>
    </div>
</div>
```
