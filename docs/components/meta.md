# Meta

The `<head>` tags the Hotwire stack reads, as components with defaults, validation and an umbrella that covers the
typical page in one line.

## Usage

```blade
<head>
    <hw:meta csrf color-scheme prefetch refresh />
</head>
```

renders

```html

<meta name="turbo-prefetch" content="true">
<meta name="turbo-refresh-method" content="morph">
<meta name="turbo-refresh-scroll" content="preserve">
<meta name="csrf-token" content="…">
<script>/* applies data-theme before paint */</script>
<meta name="color-scheme" content="light dark">
```

A prop you leave out renders nothing, so the head states only what the application opted into. A bare attribute takes
the granular component's own default; pass a value to override it.

```blade
<hw:meta csrf prefetch="false" cache="no-cache" root="/app" />
```

## Umbrella props

| Prop              | Bare value        | Renders                |
|-------------------|-------------------|------------------------|
| `prefetch`        | `true`            | `turbo-prefetch`       |
| `refresh`         | `morph`           | `turbo-refresh-method` |
| `scroll`          | `preserve`        | `turbo-refresh-scroll` |
| `cache`           | `no-preview`      | `turbo-cache-control`  |
| `visit-control`   | `reload`          | `turbo-visit-control`  |
| `root`            | `/`               | `turbo-root`           |
| `view-transition` | `same-origin`     | `view-transition`      |
| `csrf`            | the session token | `csrf-token`           |
| `color-scheme`    | `light dark`      | color-scheme script + `color-scheme` |

`refresh` and `scroll` are two props over one component: writing either renders both metas, so
`<hw:meta refresh />` states `morph` and `preserve` together, and `<hw:meta scroll="reset" />` keeps `morph` while
changing the scroll. If one half is `false` while the other asks for the pair, the false half falls back to its default:
`<hw:meta refresh="replace" scroll="false" />` still renders `scroll="preserve"`.

`false` means "leave this meta out" for every prop whose content is an enumeration. `prefetch` is the exception:
`false` is the value that meta exists to state, so `prefetch="false"` renders `content="false"` and disables link
prefetching.

## Granular components

Each one renders on its own, with the same default the umbrella uses:

```blade
<hw:meta.prefetch enabled="false" />
<hw:meta.refresh method="morph" scroll="preserve" />
<hw:meta.cache control="no-cache" />
<hw:meta.visit-control />
<hw:meta.root path="/app" />
<hw:meta.view-transition />
<hw:meta.csrf />
<hw:meta.color-scheme schemes="dark" />
```

## Validation

Values are checked against the tag's allowlist and a wrong one throws `InvalidArgumentException` naming what is
supported, rather than rendering a meta the browser silently ignores:

```blade
<hw:meta.refresh method="morf" />
{{-- Unsupported meta.refresh method value. Supported values: replace, morph. --}}
```

| Component              | Supported values                                                    |
|------------------------|---------------------------------------------------------------------|
| `meta.prefetch`        | `true`, `false`                                                     |
| `meta.refresh`         | method `replace`, `morph` — scroll `reset`, `preserve`              |
| `meta.cache`           | `no-cache`, `no-preview`                                            |
| `meta.visit-control`   | `reload`                                                            |
| `meta.view-transition` | `same-origin`                                                       |
| `meta.color-scheme`    | `light`, `dark`, `light dark`, `dark light`, `normal`, `only light` |

Booleans accept the bare attribute, the bound bool and the string spelling alike — `prefetch`, `:prefetch="false"`
and `prefetch="false"` all behave as you would expect.

## Why these metas

- **`csrf-token`** is read by the [File Upload](file-upload.md) controller for its requests.
- **`color-scheme`** makes native form controls and scrollbars follow the active theme, alongside the `data-theme`
  attribute the [Color Scheme](color-scheme.md) component manages. On the umbrella `<hw:meta>`, this also renders
  `<hw:color-scheme.script />` so the theme is applied before CSS paints.
- **`turbo-refresh-method`** set to `morph` is what lets [Optimistic](optimistic.md) updates and the morph-aware
  controllers survive a page refresh; `turbo-refresh-scroll` set to `preserve` keeps the viewport where it was.
- **`turbo-cache-control`** opts a page out of Turbo's cache, or only out of its preview.

## Relationship to the Turbo directives

`emaia/laravel-hotwire-turbo` also ships Blade directives for these tags (`@turboPrefetch('false')`,
`@turboRefreshMethod('morph')` and friends). They keep working. The components add defaults, validation and a single
umbrella tag, and read consistently beside the rest of the package. When the umbrella includes `color-scheme`, it also
renders the colour-scheme script; granular meta components stay single-purpose and only render their own tag.
