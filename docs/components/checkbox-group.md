# Checkbox Group

Renders a group of checkboxes from an `options` array, with optional "select-all" master checkbox via the `checkbox-select-all` Stimulus controller.

## Quick example

```blade
<x-hwc::checkbox-group
    name="user_ids[]"
    :options="$users->pluck('name', 'id')->toArray()"
    :selected="old('user_ids', [])"
    select-all
/>
```

## Props

| Prop               | Type           | Default       | Description                                                    |
|--------------------|----------------|---------------|----------------------------------------------------------------|
| `name`             | `string`       | —             | Input name, typically `foo[]` for array submission              |
| `options`          | `array`        | `[]`          | `[value => label]` pairs                                       |
| `selected`         | `array`        | `[]`          | Values that should be checked                                  |
| `select-all`       | `bool`         | `false`       | Renders a master checkbox that toggles all items                |
| `select-all-label` | `string\|null` | `"Select all"` | Custom label for the master checkbox                           |
| `class`            | `string`       | `""`          | Merged on the wrapper `<div>`                                  |

## Without select-all

```blade
<x-hwc::checkbox-group
    name="roles[]"
    :options="['admin' => 'Admin', 'editor' => 'Editor', 'viewer' => 'Viewer']"
    :selected="$user->roles ?? []"
/>
```

Each checkbox renders inside a `<label>` with the option label as its text node.

## With select-all

When `select-all` is enabled, the wrapper gets `data-controller="checkbox-select-all"`, the master checkbox gets `data-checkbox-select-all-target="checkboxAll"`, and each item gets `data-checkbox-select-all-target="checkbox"`. The controller handles indeterminate state automatically.

```blade
<x-hwc::checkbox-group
    name="tags[]"
    :options="[1 => 'Laravel', 2 => 'Hotwire', 3 => 'Stimulus']"
    :selected="old('tags', [])"
    select-all
    select-all-label="All tags"
/>
```

## Required controllers

`hotwire:check` looks for `checkbox-select-all` when you use the `select-all` prop.
