# Combobox

Custom select/combobox with search filter, keyboard navigation and option groups. Wraps the `combobox` Stimulus controller.

## Quick example

```blade
<x-hwc::combobox name="fruit" :options="['apple' => 'Apple', 'banana' => 'Banana']" :value="$fruit" />
```

## Props

| Prop                | Type           | Default             | Description                                         |
|---------------------|----------------|----------------------|------------------------------------------------------|
| `name`              | `string\|null` | —                    | Name of the hidden input                             |
| `id`                | `string\|null` | auto-generated       | Base ID; derives `-trigger`, `-popover`, `-listbox`  |
| `value`             | `mixed`        | `null`               | Selected value                                       |
| `options`           | `array`        | `[]`                 | Flat `[value => label]` or grouped `[group => [...]]`|
| `searchable`        | `bool`         | `true`               | Shows/hides the search input                         |
| `placeholder`       | `string\|null` | `null`               | Text when nothing is selected                        |
| `search-placeholder`| `string\|null` | `"Search entries..."`| Placeholder for the search input                     |
| `class`             | `string`       | `""`                 | Merged on the wrapper `<div>`                        |
| `trigger-class`     | `string`       | `""`                 | Merged on the trigger `<button>`                     |

## Flat options

```blade
<x-hwc::combobox name="fruit" :options="['apple' => 'Apple', 'banana' => 'Banana']" placeholder="Pick a fruit..." />
```

## Grouped options

When a value in the options array is itself an array, the component renders groups with headings:

```blade
<x-hwc::combobox name="item" :options="[
    'Fruits' => ['apple' => 'Apple', 'banana' => 'Banana'],
    'Veggies' => ['carrot' => 'Carrot'],
]" />
```

## Without search

```blade
<x-hwc::combobox name="status" :options="[1 => 'Active', 2 => 'Inactive']" :searchable="false" />
```

## With placeholder

```blade
<x-hwc::combobox name="country" :options="$countries" placeholder="Select a country..." />
```

## Inheriting from `<x-hwc::field>`

```blade
<x-hwc::field name="fruit">
    <x-hwc::combobox :options="['apple' => 'Apple', 'banana' => 'Banana']" />
</x-hwc::field>
```

## Required controllers

`hotwire:check` looks for `combobox`.
