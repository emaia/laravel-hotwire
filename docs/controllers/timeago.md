# Timeago

Converts a datetime into a human-readable relative string (e.g. "3 minutes ago"). Supports automatic refresh and localization.

**Identifier:** `timeago`  
**Install:** `php artisan hotwire:controllers timeago`

> Looking for the Blade component? See [`<hw:timeago />`](../components/timeago.md).

## Requirements

No external dependencies.

## Stimulus Values

| Value              | Type      | Default           | Description                                                            |
|--------------------|-----------|-------------------|------------------------------------------------------------------------|
| `datetime`         | `String`  | —                 | ISO 8601 datetime string to format (required)                          |
| `add-suffix`       | `Boolean` | `false`           | Appends "ago" or "in" to the output (e.g. "3 minutes ago")             |
| `include-seconds`  | `Boolean` | `false`           | Provides more granular output for differences under a minute           |
| `refresh-interval` | `Number`  | —                 | Milliseconds between automatic refreshes. Omit to disable auto-refresh |
| `locale`           | `String`  | Document language | BCP 47 locale used to format the relative time                         |

## Basic usage

```html
<time
    data-controller="timeago"
    data-timeago-datetime-value="2024-01-15T10:30:00Z"
    data-timeago-add-suffix-value="true"
>
</time>
```

Renders as e.g. `3 hours ago`.

## With auto-refresh

Useful for live feeds or recently-created records:

```html
<time
    data-controller="timeago"
    data-timeago-datetime-value="{{ $post->created_at->toIso8601String() }}"
    data-timeago-add-suffix-value="true"
    data-timeago-refresh-interval-value="60000"
>
</time>
```

The text is recalculated every 60 seconds without a page reload.

## With seconds precision

```html
<time
    data-controller="timeago"
    data-timeago-datetime-value="{{ now()->toIso8601String() }}"
    data-timeago-add-suffix-value="true"
    data-timeago-include-seconds-value="true"
    data-timeago-refresh-interval-value="10000"
>
</time>
```

## Localization

Set a BCP 47 locale explicitly with the `locale` value:

```html
<time
    data-controller="timeago"
    data-timeago-datetime-value="2026-08-04T12:00:00Z"
    data-timeago-add-suffix-value="true"
    data-timeago-locale-value="pt-BR"
></time>
```

When omitted, the controller uses the document's `<html lang>` value and then the browser default. Formatting is
provided by the browser's `Intl.RelativeTimeFormat` and `Intl.NumberFormat` implementations. With suffixes enabled,
locale-specific terms such as `yesterday`, `ontem`, or `昨日` can replace a numeric phrase.

## With Blade (raw)

```blade
<time
    data-controller="timeago"
    data-timeago-datetime-value="{{ $comment->created_at->toIso8601String() }}"
    data-timeago-add-suffix-value="true"
    title="{{ $comment->created_at->format('d M Y H:i') }}"
>
    {{ $comment->created_at->diffForHumans() }}
</time>
```

Setting a `title` attribute provides the absolute date on hover, while the element body shows the relative time.
The initial text from `diffForHumans()` acts as a server-rendered fallback before Stimulus connects.

For a ready-made wrapper that handles all of this — including the `title` tooltip and slot fallback — use the
[`<hw:timeago />`](../components/timeago.md) Blade component.
