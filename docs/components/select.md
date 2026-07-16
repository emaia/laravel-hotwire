# Select

Select dropdown with auto-derived `id`/`errorKey` from `name`, automatic `old()` merge, ARIA wiring, and optional
placeholder.

## Quick example

```blade
<hw:select name="status" :options="[1 => 'Active', 2 => 'Inactive']" :selected="$status" />
```

## Props

| Prop                | Type                | Default             | Description                                                                                                    |
|---------------------|---------------------|---------------------|----------------------------------------------------------------------------------------------------------------|
| `name`              | `string\|null`      | —                   | Pass-through. Drives `id` and `errorKey` if those aren't set                                                   |
| `id`                | `string\|null`      | derived from `name` | Override the auto-derived id                                                                                   |
| `options`           | `array`             | `[]`                | `[value => label]` pairs                                                                                       |
| `selected`          | `mixed`             | `null`              | Selected value (or array when `multiple` — see [Multiple](#multiple)), merged with `old($errorKey, $selected)` |
| `errorKey`          | `string\|null`      | derived from `name` | Override for arrays where HTML `name` ≠ validation key                                                         |
| `old`               | `bool`              | `true`              | Disable `old()` auto-merge                                                                                     |
| `placeholder`       | `string\|null`      | `null`              | Placeholder option as the first item (re-selectable)                                                           |
| `nullable`          | `bool`              | `false`             | Render an empty first option even without a placeholder string                                                 |
| `auto-submit`       | `bool\|string`      | `false`             | Add auto-submit wiring; selects default to immediate `change` submit                                           |
| `auto-submit-delay` | `int\|string\|null` | `null`              | Per-field debounce override when `auto-submit="debounced"` is used                                             |
| `class`             | `string`            | `""`                | Merged on `<select>`                                                                                           |

Any other HTML attribute (`disabled`, `multiple`, `data-*`, `aria-*`) passes through. `multiple` changes how `selected`
is interpreted — see [Multiple](#multiple).

## Auto-derivation

Same convention as `<hw:input>`:

```blade
<hw:select name="variables[0][status]" :options="[...]" />
{{-- id="variables-0-status", aria-describedby="variables-0-status-error", errorKey="variables.0.status" --}}
```

## Placeholder

```blade
<hw:select name="status" :options="$statuses" placeholder="Select a status..." />
```

Renders a re-selectable `<option value="" selected>` as the first item. When a `selected` value is provided, the
placeholder is rendered without `selected`. Users can return to the placeholder after making a selection — ideal for
optional fields.

## Nullable

```blade
<hw:select name="status" :options="$statuses" :nullable="true" />
```

When no `placeholder` string is provided, renders an empty `<option value=""></option>` so no option is pre-selected.
Combine with `placeholder` for a labeled empty choice:

```blade
<hw:select name="status" :options="$statuses" :nullable="true" placeholder="No status" />
```

## Multiple

```blade
<hw:select name="ids[]" :options="$users" :selected="[1, 3]" multiple />
```

When `multiple` is set, `selected` accepts an array and each matching option gets the `selected` attribute. The
placeholder and nullable options are skipped (they don't apply to multi-select). Remember to use `name="ids[]"` —
without `[]`, PHP only receives the last selected value.

`old()` integrates naturally: after a validation redirect, the previously selected values are restored from the flashed
array.

## Inheriting from `<hw:field>`

```blade
<hw:field name="status" label="Status" required>
    <hw:select :options="[1 => 'Active', 2 => 'Inactive']" />
</hw:field>
```

## Auto-submit

Select controls submit immediately by default:

```blade
<hw:form method="get" action="/items" auto-submit>
    <hw:select name="category" :options="$categories" auto-submit />
</hw:form>
```

To debounce a select, make the mode explicit:

```blade
<hw:select name="category" :options="$categories" auto-submit="debounced" auto-submit-delay="500" />
```

## Required controllers

`auto-submit` is only required when the `auto-submit` prop is used.
