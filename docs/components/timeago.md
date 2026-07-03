# Timeago

A `<time>` element that displays a relative time (e.g. "3 minutes ago") and progressively enhances into a
self-refreshing label once Stimulus connects. Wraps the [`timeago` controller](../controllers/timeago.md).

## Requirements

- `date-fns` installed in the project
- Controller published: `php artisan hotwire:controllers timeago`

> `php artisan hotwire:check` detects both automatically — `--fix` publishes the controller and adds `date-fns`
> to your `package.json` `devDependencies`.

## Props

| Prop               | Type                        | Default       | Description                                                |
|--------------------|-----------------------------|---------------|------------------------------------------------------------|
| `datetime`         | `DateTimeInterface\|string` | —             | The date/time to display (Carbon, DateTime, or ISO string) |
| `add-suffix`       | `bool`                      | `true`        | Appends "ago" / "in" to the output                         |
| `include-seconds`  | `bool`                      | `false`       | More granular output for differences under a minute        |
| `refresh-interval` | `int\|null`                 | `null`        | Milliseconds between auto-refreshes. Omit to disable       |
| `title-format`     | `string`                    | `'d M Y H:i'` | PHP date format used for the `title` tooltip               |

The default slot is rendered as a server-side fallback before Stimulus connects. Pass
`$model->created_at->diffForHumans()` for a seamless progressive-enhancement experience.

Additional HTML attributes (e.g. `class`, `id`) are merged onto the `<time>` element.

## Basic usage

```blade
<hw:timeago :datetime="$post->created_at" />
```

Renders as e.g. `3 hours ago` with a hover tooltip showing `19 Apr 2026 14:30`.

## With server-rendered fallback

```blade
<hw:timeago :datetime="$comment->created_at">
    {{ $comment->created_at->diffForHumans() }}
</hw:timeago>
```

The slot content is displayed immediately on page load and replaced by the JS-formatted string once Stimulus
connects.

## With auto-refresh

```blade
<hw:timeago
    :datetime="$post->created_at"
    :refresh-interval="60000"
>
    {{ $post->created_at->diffForHumans() }}
</hw:timeago>
```

## With seconds precision

```blade
<hw:timeago
    :datetime="$event->started_at"
    :include-seconds="true"
    :refresh-interval="10000"
>
    {{ $event->started_at->diffForHumans() }}
</hw:timeago>
```

## Custom title format

```blade
<hw:timeago
    :datetime="$order->placed_at"
    title-format="d/m/Y H:i:s"
>
    {{ $order->placed_at->diffForHumans() }}
</hw:timeago>
```

## Without suffix

```blade
<hw:timeago :datetime="$file->updated_at" :add-suffix="false" />
```

Renders as e.g. `3 hours` instead of `3 hours ago`.

## Localization

Localization happens at the controller level — see
[the `timeago` controller doc](../controllers/timeago.md#localization) for how to subclass it and inject a
`date-fns` locale.

## See also

- [`timeago` controller](../controllers/timeago.md) — the underlying Stimulus controller, including all values
  and the localization hook
