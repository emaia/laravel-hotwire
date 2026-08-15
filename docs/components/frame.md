# Frame

DX-friendly Turbo Frame wrapper for Laravel Hotwire apps.

Use `<hw:frame>` when you want to render a regular `<turbo-frame>` with concise boolean aliases for common Turbo
attributes. Use `<hw:frame-or-page>` when the same route should render as a frame payload for Turbo Frame requests and as
a full page for direct navigation.

## Basic Usage

```blade
<hw:frame id="results">
    ...
</hw:frame>
```

## Lazy loading

Use `lazy` as shorthand for `loading="lazy"`:

```blade
<hw:frame id="results" src="/tasks" lazy>
    Loading...
</hw:frame>
```

The native `loading` prop is still supported and wins over the shorthand when both are present:

```blade
<hw:frame id="results" src="/tasks" lazy loading="eager">
    Loading...
</hw:frame>
```

## URL history actions

Use `advance` when frame navigation should push the new URL into browser history, which is common for filterable or
bookmarkable sections:

```blade
<hw:frame id="results" advance>
    ...
</hw:frame>
```

Use `replace` when the frame navigation should update the current history entry instead:

```blade
<hw:frame id="results" replace>
    ...
</hw:frame>
```

For less common values, use `action="..."`, which maps to `data-turbo-action`. You can still pass the raw
`data-turbo-action` attribute directly; when present, that explicit native attribute wins over `advance`, `replace`, and
`action`. Passing `advance` and `replace` together without `action` or `data-turbo-action` is invalid because the history
behavior would be ambiguous.

Keep the default history policy on the frame even when links or buttons that target it live elsewhere on the page. A
single trigger can diverge by passing native `data-turbo-action` directly; see
[`<hw:button>`](./button.md#controlling-turbo-history) for a complete example.

## Controller integrations

Enable View Transitions for frame updates with `view-transition`:

```blade
<hw:frame id="results" src="/tasks" view-transition>
    ...
</hw:frame>
```

The controller only wraps the frame render in `document.startViewTransition()`. Customize the animation in CSS. For a
frame-specific animation, give the frame or an element inside it a unique `view-transition-name` and style that name:

```blade
<hw:frame id="results" src="/tasks" view-transition class="[view-transition-name:task-results]">
    ...
</hw:frame>
```

```css
::view-transition-old(task-results) {
    animation: fade-out 120ms ease-out;
}

::view-transition-new(task-results) {
    animation: fade-in 160ms ease-out;
}
```

Enable polling with `poll`; the controller reloads the frame on an interval:

```blade
<hw:frame id="stats" src="/dashboard/stats" poll poll-interval="30000">
    Loading...
</hw:frame>
```

Use `preserve-scroll` when a frame update replaces focused content near the bottom of the document, such as paginated
results. The controller blurs focused frame children before render and restores the previous window scroll position,
clamped to the new document height:

```blade
<hw:frame id="results" preserve-scroll>
    ...
</hw:frame>
```

## Props

| Prop              | Type                 | Default | Description                                                              |
|-------------------|----------------------|---------|--------------------------------------------------------------------------|
| `id`              | `string\|object`     | —       | Required DOM id. Objects are resolved with `dom_id()`                    |
| `src`             | `string\|null`       | `null`  | Native Turbo Frame `src`                                                 |
| `loading`         | `string\|null`       | `null`  | Native `loading`, usually `lazy` or `eager`                              |
| `target`          | `string\|null`       | `null`  | Native Turbo Frame `target`, including `_top`                            |
| `autoscroll`      | `bool\|string\|null` | `null`  | Native Turbo Frame `autoscroll` attribute                                |
| `action`          | `bool\|string\|null` | `null`  | Sets `data-turbo-action`, usually `advance` or `replace`                 |
| `advance`         | `bool\|string`       | `false` | Shorthand for `action="advance"` when `action` is not set                |
| `replace`         | `bool\|string`       | `false` | Shorthand for `action="replace"` when `action` and `advance` are not set |
| `lazy`            | `bool\|string`       | `false` | Shorthand for `loading="lazy"` when `loading` is not set                 |
| `poll`            | `bool\|string`       | `false` | Mounts the `turbo--polling` controller on the frame                      |
| `poll-interval`   | `int\|null`          | `null`  | Polling interval in milliseconds; controller default is `5000`           |
| `view-transition` | `bool\|string`       | `false` | Mounts the `turbo--view-transition` controller on the frame              |
| `preserve-scroll` | `bool\|string`       | `false` | Mounts the `turbo--preserve-scroll` controller on the frame              |

Any other HTML attribute (`class`, `data-*`, `aria-*`, `disabled`, `busy`, `complete`, `refresh`) passes through to the
`<turbo-frame>` element. Boolean alias props do not render as HTML attributes.

`autoscroll` is a native Turbo boolean attribute: any rendered `autoscroll` attribute enables it, including
`autoscroll="false"`. The component omits false-like string values (`"false"`, `"0"`, `"off"`, `"no"`) so bound config
values can safely disable it.

`action` and boolean alias props (`lazy`, `advance`, `replace`, `poll`, `view-transition`, `preserve-scroll`) also treat
those false-like strings as disabled, which keeps environment/config-driven values from accidentally enabling the
behavior.

## Model-aware ids

Passing an object uses the Turbo package's `dom_id()` helper:

```blade
<hw:frame :id="$task">
    ...
</hw:frame>
```

This keeps frame ids aligned with Turbo Stream targets that also use `dom_id($task)`.

## Destination `frame` props

This component's required `id` identifies the frame host. Other components use an optional `frame` prop to emit
`data-turbo-frame` on links, buttons, and forms that navigate to a host.

Those optional destination props accept a string or an object resolvable with `dom_id()`. `null`, `false`, empty strings,
and whitespace-only strings omit the metadata. Where a component also accepts native attributes, an explicit
`data-turbo-frame` wins; binding that native attribute to `false` suppresses the `frame` prop. Disabled, current, or
otherwise non-actionable elements omit frame metadata.

## See also

- [`<hw:button>`](./button.md#controlling-turbo-history) — target this frame and override its history policy per link.
- [`<hw:form>`](./form.md) — use `frame="results"` to submit a form into a frame.
- [`<hw:frame-or-page>`](./frame-or-page.md) — render a route as a frame payload or full page depending on the request.
- [`emaia/laravel-hotwire-turbo`](https://github.com/emaia/laravel-hotwire-turbo) — lower-level Turbo integration.
