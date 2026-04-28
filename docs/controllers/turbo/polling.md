# Polling

Automatically reloads a Turbo Frame at regular intervals. Useful for dashboards, feeds and areas that need up-to-date data without user interaction.

**Identifier:** `polling`
**Install:** `php artisan hotwire:controllers turbo/polling`

## Requirements

- `@hotwired/turbo`

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `frame` | `String` | — | ID of the Turbo Frame to reload (required) |
| `timeout` | `Number` | `5000` | Interval between refreshes (ms) |
| `enabled` | `Boolean` | `true` | Enables/disables polling |

## Actions

| Action | Description |
|--------|-------------|
| `polling#refresh` | Forces an immediate refresh and schedules the next one |

## Basic usage

```html
<div
    data-controller="polling"
    data-polling-frame-value="notifications"
    data-polling-timeout-value="10000"
>
    <turbo-frame id="notifications" src="/notifications">
        ...
    </turbo-frame>
</div>
```

The `notifications` frame will reload every 10 seconds.

## With polling toggle

```html
<div
    data-controller="polling"
    data-polling-frame-value="feed"
    data-polling-enabled-value="true"
>
    <button data-action="polling#refresh">
        Refresh now
    </button>

    <turbo-frame id="feed" src="/feed">
        ...
    </turbo-frame>
</div>
```

## Dashboard with multiple frames

```html
<div
    data-controller="polling"
    data-polling-frame-value="stats"
    data-polling-timeout-value="30000"
>
    <turbo-frame id="stats" src="/dashboard/stats">
        ...
    </turbo-frame>
</div>

<div
    data-controller="polling"
    data-polling-frame-value="activity"
    data-polling-timeout-value="5000"
>
    <turbo-frame id="activity" src="/dashboard/activity">
        ...
    </turbo-frame>
</div>
```

## How it works

1. On connect, schedules a `setTimeout` with the defined interval.
2. On expiry, uses `Turbo.visit()` with `frame` to reload only the target frame.
3. If `enabled` changes to `false`, cancels the timer. If it changes back to `true`, reschedules.
4. If the refresh fails, automatically reschedules to try again.
