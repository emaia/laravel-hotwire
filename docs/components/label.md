# Label

Form `<label>` with optional required marker. When inside `<x-hwc::field>`, inherits `name` and `required` via `@aware`.

## Quick example

```blade
<x-hwc::label for="email" required>E-mail</x-hwc::label>
```

## Props

| Prop             | Type           | Default   | Description                                                    |
|------------------|----------------|-----------|----------------------------------------------------------------|
| `for`            | `string\|null` | —         | Sets `<label for="...">`                                       |
| `name`           | `string\|null` | —         | Derives `for` when `for` is omitted (handles bracket notation) |
| `value`          | `string\|null` | —         | Label text (alternative to slot)                               |
| `required`       | `bool\|null`   | `null`    | Renders required marker when `true`. Inherited via `@aware` from `<x-hwc::field>`. |
| `required-label` | `string`       | `"*"`     | Marker text                                                    |
| `class`          | `string`       | `""`      | Merged                                                         |

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

When inside `<x-hwc::field>`, `name` and `required` are inherited via `@aware`. The field's `label` prop auto-renders `<x-hwc::label>` with the right `for` and required marker — no need to write `<x-hwc::label>` manually for simple cases:

```blade
{{-- Field auto-renders the label --}}
<x-hwc::field name="email" label="E-mail" required>
    <x-hwc::input type="email" />
</x-hwc::field>

{{-- Manual label — still works --}}
<x-hwc::field name="email" required>
    <x-hwc::label>E-mail</x-hwc::label>
    <x-hwc::input type="email" />
</x-hwc::field>
```
