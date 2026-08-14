# Checkbox

Applies the native checkbox `indeterminate` DOM property from a Stimulus value. Most apps should use `<hw:checkbox indeterminate>` instead of wiring this controller manually.

**Identifier:** `checkbox`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers checkbox`.

## Requirements

- No external dependencies.

## Basic Usage

```html
<input
    type="checkbox"
    data-controller="checkbox"
    data-checkbox-indeterminate-value="true"
>
```

## Behavior

The controller sets `element.indeterminate` whenever the `indeterminate` value changes.

The controller re-syncs on `turbo:render`, which keeps the visual state correct after Turbo morphs update server-rendered checkbox markup.

## Values

| Value           | Type      | Default | Description                                      |
|-----------------|-----------|---------|--------------------------------------------------|
| `indeterminate` | `Boolean` | `false` | Sets `element.indeterminate` whenever it changes |
