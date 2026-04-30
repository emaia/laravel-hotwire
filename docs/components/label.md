# Label

Form `<label>` with optional required/optional markers and an inline tooltip.

## Quick example

```blade
<x-hwc::label for="email" required>E-mail</x-hwc::label>
```

## Props

| Prop             | Type           | Default | Description                                                    |
|------------------|----------------|---------|----------------------------------------------------------------|
| `for`            | `string\|null` | —       | Sets `<label for="...">`                                       |
| `name`           | `string\|null` | —       | Derives `for` when `for` is omitted (handles bracket notation) |
| `value`          | `string\|null` | —       | Label text (alternative to slot)                               |
| `required`       | `bool`         | `false` | Renders required marker (decorative — does not set `required` on the input) |
| `required-label` | `string`       | `"*"`   | Marker text                                                    |
| `optional`       | `bool`         | `false` | Renders `(opcional)` marker                                    |
| `info`           | `string\|null` | `null`  | Renders a tooltip (Stimulus `tooltip` controller)              |
| `class`          | `string`       | `""`    | Merged                                                         |

Additional HTML attributes pass through.

## Inheriting from `<x-hwc::field>`

When inside `<x-hwc::field>`, `for` is derived from the field's `name` automatically and `required` propagates:

```blade
<x-hwc::field name="email" label="E-mail" required>
    {{-- Label and Input both pick up name + required via @aware --}}
</x-hwc::field>
```

## Info tooltip

Renders `data-controller="tooltip"` only when `info` is provided. Requires the `tooltip` controller to be published (`php artisan hotwire:controllers tooltip`).
