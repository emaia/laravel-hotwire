# Select

Select dropdown with auto-derived `id`/`errorKey` from `name`, automatic `old()` merge, ARIA wiring, and optional placeholder.

## Quick example

```blade
<x-hwc::select name="status" :options="[1 => 'Active', 2 => 'Inactive']" :selected="$status" />
```

## Props

| Prop          | Type           | Default                        | Description                                                       |
|---------------|----------------|--------------------------------|-------------------------------------------------------------------|
| `name`        | `string\|null` | —                              | Pass-through. Drives `id` and `errorKey` if those aren't set       |
| `id`          | `string\|null` | derived from `name`            | Override the auto-derived id                                      |
| `options`     | `array`        | `[]`                           | `[value => label]` pairs                                          |
| `selected`    | `mixed`        | `null`                         | Selected value, merged with `old($errorKey, $selected)`            |
| `errorKey`    | `string\|null` | derived from `name`            | Override for arrays where HTML `name` ≠ validation key            |
| `old`         | `bool`         | `true`                         | Disable `old()` auto-merge                                        |
| `placeholder` | `string\|null` | `null`                         | Disabled placeholder option as the first item                     |
| `class`       | `string`       | `""`                           | Merged on `<select>`                                              |

Any other HTML attribute (`disabled`, `data-*`, `aria-*`) passes through.

## Auto-derivation

Same convention as `<x-hwc::input>`:

```blade
<x-hwc::select name="variables[0][status]" :options="[...]" />
{{-- id="variables-0-status", aria-describedby="variables-0-status-error", errorKey="variables.0.status" --}}
```

## Placeholder

```blade
<x-hwc::select name="status" :options="$statuses" placeholder="Select a status..." />
```

Renders a `<option value="" disabled selected>` as the first item. When a `selected` value is provided, the placeholder is rendered without `selected`.

## Inheriting from `<x-hwc::field>`

```blade
<x-hwc::field name="status" required>
    <x-hwc::select :options="[1 => 'Active', 2 => 'Inactive']" />
</x-hwc::field>
```

## Required controllers

This component does not depend on any Stimulus controller.
