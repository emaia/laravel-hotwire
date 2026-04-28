# Lazy Image

Polls an image URL until it becomes available, then displays it automatically. Useful for asynchronously generated images (thumbnails, conversions, background processing).

**Identifier:** `lazy-image`
**Install:** `php artisan hotwire:controllers lazy-image`

## Requirements

- No external dependencies.

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `url` | `String` | — | Image URL (required) |
| `alt` | `String` | `""` | Image alt text |
| `interval` | `Number` | `3000` | Interval between attempts (ms) |
| `width` | `Number` | `0` | Image width (0 = not set) |
| `height` | `Number` | `0` | Image height (0 = not set) |
| `max-attempts` | `Number` | `20` | Maximum number of attempts |
| `img-class` | `String` | `""` | CSS classes applied to the `<img>` tag |
| `sources` | `Array` | `[]` | Array of `{ media, srcset }` for `<source>` (responsive) |

## Basic usage

```html
<picture
    data-controller="lazy-image"
    data-lazy-image-url-value="/storage/thumbnails/abc123.webp"
    data-lazy-image-alt-value="Document thumbnail"
>
    <p>Generating thumbnail...</p>
</picture>
```

The initial content (placeholder) is replaced by the image when it becomes available.

## With classes and dimensions

```html
<picture
    data-controller="lazy-image"
    data-lazy-image-url-value="/storage/avatars/user-42.jpg"
    data-lazy-image-alt-value="User avatar"
    data-lazy-image-width-value="128"
    data-lazy-image-height-value="128"
    data-lazy-image-img-class-value="rounded-full"
>
    <div class="w-32 h-32 bg-gray-200 animate-pulse rounded-full"></div>
</picture>
```

## With more frequent polling

```html
<picture
    data-controller="lazy-image"
    data-lazy-image-url-value="/storage/exports/chart.png"
    data-lazy-image-interval-value="1000"
    data-lazy-image-max-attempts-value="30"
>
    <p>Generating chart...</p>
</picture>
```

## With responsive sources

```html
<picture
    data-controller="lazy-image"
    data-lazy-image-url-value="/storage/photos/landscape.jpg"
    data-lazy-image-sources-value='[{"media":"(min-width: 768px)","srcset":"/storage/photos/landscape-lg.webp"},{"media":"(max-width: 767px)","srcset":"/storage/photos/landscape-sm.webp"}]'
>
    <div class="aspect-video bg-gray-200 animate-pulse"></div>
</picture>
```

## How it works

1. On connect, starts polling by creating a `new Image()` with the URL.
2. If `onload` fires, replaces the element's content with the image (and `<source>` elements if provided).
3. If `onerror` fires, schedules a new attempt after the interval.
4. Stops after `maxAttempts` unsuccessful attempts.
5. On disconnect, cancels any pending timer.
