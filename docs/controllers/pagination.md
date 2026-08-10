# Pagination Controller

Loads the next server-rendered paginator page without replacing the current page of results.

The controller is mounted automatically by `<hw:pagination incremental>` or `<hw:pagination infinite>`. It keeps the
next-page link as a real `<a href>` so the control still works without JavaScript. With JavaScript enabled, clicking the
link fetches the next page as HTML, appends the response children from the configured append target, then replaces the
current control with the response control. If the response has no pagination control, the current control is removed and
the load is treated as terminal.

## Values

| Value           | Type      | Default               | Description                                                    |
|-----------------|-----------|-----------------------|----------------------------------------------------------------|
| `append-to`     | `String`  | —                     | Selector for the container that receives new items.             |
| `infinite`      | `Boolean` | `false`               | Observe the next link with `IntersectionObserver`.              |
| `loading-label` | `String`  | `Loading more`        | Visible loading text and live-region message while a page loads. |
| `loaded-label`  | `String`  | `More results loaded` | Live-region message after new items are appended.               |
| `error-label`   | `String`  | `Loading failed`      | Live-region message when a request or response fails.           |
| `scroll-to`     | `String`  | —                     | Optional selector to scroll into view after manual loading.     |
| `root-margin`   | `String`  | `300px`               | IntersectionObserver `rootMargin` for infinite mode.           |
| `threshold`     | `Number`  | `1`                   | IntersectionObserver `threshold` for infinite mode.            |

When `<hw:pagination>` mounts this controller it emits every controller value explicitly, so PHP props remain the source
of truth. The defaults below only apply when the controller is used standalone with handwritten markup.

## States

| State     | Description                                      |
|-----------|--------------------------------------------------|
| `loading` | A request is in flight and the trigger is busy.  |
| `error`   | The last request failed or the response was invalid. |

## Targets

| Target   | Description              |
|----------|--------------------------|
| `next`   | The next-page/load-more link. |
| `status` | Live region for loading/error/success announcements. |

## Actions

| Action | Description                         |
|--------|-------------------------------------|
| `load` | Fetch and append the next page HTML. |

## Response Shape

Render the new page items in a stable container and point `append-to` at that container. The response must include the
same append target. When the current pagination lives inside a Turbo Frame, the response should include a matching frame
ID.

```blade
<turbo-frame id="users">
    <div id="users-list">
        @include('users.rows', ['users' => $users])
    </div>

    <hw:pagination :paginator="$users" incremental append-to="#users-list" />
</turbo-frame>
```

The controller appends the response children of `#users-list` to the current `#users-list`. It then replaces the current
pagination with the response pagination, or removes the current pagination when the response is the last page. It never
copies arbitrary siblings before the pagination control, so headings, filters and summaries are not duplicated.

In infinite mode, a failed automatic load leaves the current link visible and stops observing it. The user can retry by
clicking the link manually; a successful retry replaces the control and the next control can be observed again.
