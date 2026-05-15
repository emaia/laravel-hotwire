# Combobox

Custom select/combobox with search filter, keyboard navigation and option groups. Wraps the `combobox` Stimulus controller.

## Quick example

```blade
<x-hwc::combobox name="fruit" :options="['apple' => 'Apple', 'banana' => 'Banana']" :value="$fruit" />
```

## Props

| Prop                | Type           | Default                  | Description                                                  |
|---------------------|----------------|--------------------------|--------------------------------------------------------------|
| `name`              | `string\|null` | —                        | Name of the hidden input                                     |
| `id`                | `string\|null` | auto-generated           | Base ID; derives `-trigger`, `-popover`, `-listbox`          |
| `value`             | `mixed`        | `null`                   | Selected value                                               |
| `options`           | `array`        | `[]`                     | Flat `[value => label]` or grouped `[group => [...]]`        |
| `searchable`        | `bool`         | `true`                   | Shows/hides the search input                                 |
| `placeholder`       | `string\|null` | `null`                   | Text when nothing is selected                                |
| `search-placeholder`| `string\|null` | `"Search entries..."`    | Placeholder for the search input                             |
| `class`             | `string`       | `""`                     | Merged on the wrapper `<div>`                                |
| `trigger-class`     | `string`       | `""`                     | Merged on the trigger `<button>`                             |
| `active-class`      | `string`       | `"active"`               | Class applied to the keyboard/hover-active option            |
| `placeholder-class` | `string`       | `"text-muted-foreground"`| Class applied to the label when nothing is selected (multi)  |
| `placement`         | `string`       | `"left"`                 | Anchors the popover to the wrapper's `left` or `right` edge  |

`active-class` and `placeholder-class` accept space-separated lists (e.g. `"bg-accent ring-2"`). Invalid `placement` values fall back to `"left"`.

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

## Right-anchored popover

When the trigger sits near the right edge of the viewport, the default left-anchored popover overflows the page horizontally. Use `placement="right"` so the popover anchors to the wrapper's right edge and expands leftward into the viewport:

```blade
<x-hwc::combobox name="filter" :options="$opts" placement="right" />
```

This applies `style="right: 0; left: auto;"` inline on the popover element, overriding the `left: 0` default. A `data-placement="left|right"` attribute is also emitted for any CSS hooks you want to add.

## Customizing active and placeholder classes

The Stimulus controller uses two configurable classes:

- `active-class` is added to the option currently highlighted by keyboard or hover.
- `placeholder-class` is added to the trigger label when no value is selected (multiple-mode only).

```blade
<x-hwc::combobox
    name="fruit"
    :options="$fruits"
    active-class="bg-accent ring-2"
    placeholder-class="opacity-60"
/>
```

## Required controllers

`hotwire:check` looks for `combobox`.
