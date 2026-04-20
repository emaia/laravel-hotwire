# Timeago

Converts a datetime into a human-readable relative string (e.g. "3 minutes ago"). Supports automatic refresh and localization.

**Identifier:** `utils--timeago`

## Requirements

- `date-fns` (`bun add date-fns`)

## Stimulus Values

| Value              | Type      | Default | Description                                                         |
|--------------------|-----------|---------|---------------------------------------------------------------------|
| `datetime`         | `String`  | —       | ISO 8601 datetime string to format (required)                       |
| `add-suffix`       | `Boolean` | `false` | Appends "ago" or "in" to the output (e.g. "3 minutes ago")         |
| `include-seconds`  | `Boolean` | `false` | Provides more granular output for differences under a minute        |
| `refresh-interval` | `Number`  | —       | Milliseconds between automatic refreshes. Omit to disable auto-refresh |

## Basic usage

```html
<time
    data-controller="utils--timeago"
    data-utils--timeago-datetime-value="2024-01-15T10:30:00Z"
    data-utils--timeago-add-suffix-value="true"
>
</time>
```

Renders as e.g. `3 hours ago`.

## With auto-refresh

Useful for live feeds or recently-created records:

```html
<time
    data-controller="utils--timeago"
    data-utils--timeago-datetime-value="{{ $post->created_at->toIso8601String() }}"
    data-utils--timeago-add-suffix-value="true"
    data-utils--timeago-refresh-interval-value="60000"
>
</time>
```

The text is recalculated every 60 seconds without a page reload.

## With seconds precision

```html
<time
    data-controller="utils--timeago"
    data-utils--timeago-datetime-value="{{ now()->toIso8601String() }}"
    data-utils--timeago-add-suffix-value="true"
    data-utils--timeago-include-seconds-value="true"
    data-utils--timeago-refresh-interval-value="10000"
>
</time>
```

## Localization

Override the `locale` property in a subclass to display relative times in a different language:

```ts
import Timeago from "./utils/timeago_controller"
import { ptBR } from "date-fns/locale"

export default class extends Timeago {
    connect() {
        this.locale = ptBR
        super.connect()
    }
}
```

Register the subclass under a custom identifier and use it instead of `utils--timeago`.

## With Blade (raw)

```blade
<time
    data-controller="utils--timeago"
    data-utils--timeago-datetime-value="{{ $comment->created_at->toIso8601String() }}"
    data-utils--timeago-add-suffix-value="true"
    title="{{ $comment->created_at->format('d M Y H:i') }}"
>
    {{ $comment->created_at->diffForHumans() }}
</time>
```

Setting a `title` attribute provides the absolute date on hover, while the element body shows the relative time. The initial text from `diffForHumans()` acts as a server-rendered fallback before Stimulus connects.

## Blade Component

The `<x-hwc::timeago>` component wraps the controller into a reusable `<time>` element.

### Props

| Prop               | Type                          | Default        | Description                                                     |
|--------------------|-------------------------------|----------------|-----------------------------------------------------------------|
| `datetime`         | `DateTimeInterface\|string`   | —              | The date/time to display (Carbon, DateTime, or ISO string)      |
| `add-suffix`       | `bool`                        | `true`         | Appends "ago" / "in" to the output                              |
| `include-seconds`  | `bool`                        | `false`        | More granular output for differences under a minute             |
| `refresh-interval` | `int\|null`                   | `null`         | Milliseconds between auto-refreshes. Omit to disable            |
| `title-format`     | `string`                      | `'d M Y H:i'` | PHP date format used for the `title` tooltip                    |

The default slot is rendered as a server-side fallback before Stimulus connects. Pass `$model->created_at->diffForHumans()` for a seamless progressive-enhancement experience.

Additional HTML attributes (e.g. `class`, `id`) are merged onto the `<time>` element.

### Basic usage

```blade
<x-hwc::timeago :datetime="$post->created_at" />
```

Renders as e.g. `3 hours ago` with a hover tooltip showing `19 Apr 2026 14:30`.

### With server-rendered fallback

```blade
<x-hwc::timeago :datetime="$comment->created_at">
    {{ $comment->created_at->diffForHumans() }}
</x-hwc::timeago>
```

The slot content is displayed immediately on page load and replaced by the JS-formatted string once Stimulus connects.

### With auto-refresh

```blade
<x-hwc::timeago
    :datetime="$post->created_at"
    :refresh-interval="60000"
>
    {{ $post->created_at->diffForHumans() }}
</x-hwc::timeago>
```

### With seconds precision

```blade
<x-hwc::timeago
    :datetime="$event->started_at"
    :include-seconds="true"
    :refresh-interval="10000"
>
    {{ $event->started_at->diffForHumans() }}
</x-hwc::timeago>
```

### Custom title format

```blade
<x-hwc::timeago
    :datetime="$order->placed_at"
    title-format="d/m/Y H:i:s"
>
    {{ $order->placed_at->diffForHumans() }}
</x-hwc::timeago>
```

### Without suffix

```blade
<x-hwc::timeago :datetime="$file->updated_at" :add-suffix="false" />
```

Renders as e.g. `3 hours` instead of `3 hours ago`.
