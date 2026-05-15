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

## Implicit labeling (wrapping pattern)

When the slot contains an `<input>`, `<select>`, or `<textarea>`, the component omits the `for` attribute and relies on HTML's implicit labeling: a labeled control that is a descendant of the label. This keeps the label clickable for checkbox/switch/radio wrap patterns where each item has a unique id (auto-derived for radio groups and array checkboxes):

```blade
<x-hwc::field name="size">
    <x-hwc::label>
        <x-hwc::input type="radio" name="size" value="default" />
        Default
    </x-hwc::label>
    {{-- ... --}}
</x-hwc::field>
```

Pass an explicit `for` (or `id` via `<x-hwc::label id="...">`-style ancestor) to override the detection. Pass `for=""` to disable explicitly.

## Inheriting from `<x-hwc::field>`

When inside `<x-hwc::field>`, `for` is derived from the field's `name` automatically and `required` propagates:

```blade
<x-hwc::field name="email" label="E-mail" required>
    {{-- Label and Input both pick up name + required via @aware --}}
</x-hwc::field>
```

## Info tooltip

Renders `data-controller="tooltip"` only when `info` is provided. Requires the `tooltip` controller to be published (`php artisan hotwire:controllers tooltip`).
