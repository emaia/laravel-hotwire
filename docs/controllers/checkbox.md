# Checkbox

Applies the native checkbox `indeterminate` DOM property from a Stimulus value.

**Identifier:** `checkbox`  
**Install:** `php artisan hotwire:controllers checkbox`

## Requirements

- No external dependencies.

## Stimulus Values

| Value           | Type      | Default | Description                                      |
|-----------------|-----------|---------|--------------------------------------------------|
| `indeterminate` | `Boolean` | `false` | Sets `element.indeterminate` whenever it changes |

## Basic usage

```html
<input
    type="checkbox"
    data-controller="checkbox"
    data-checkbox-indeterminate-value="true"
>
```

The controller re-syncs on `turbo:render`, which keeps the visual state correct after Turbo morphs update server-rendered
checkbox markup.

Most apps should use `<hw:checkbox indeterminate>` instead of wiring this controller manually.
