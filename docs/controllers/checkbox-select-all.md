# Checkbox Select All

Adds a "select all" checkbox that controls a group of checkboxes. Supports indeterminate state to indicate partial selection.

**Identifier:** `checkbox-select-all`
**Install:** `php artisan hotwire:controllers checkbox-select-all`

## Requirements

- No external dependencies.

## Targets

| Target        | Description                                  |
|---------------|----------------------------------------------|
| `checkboxAll` | The master checkbox that selects/deselects all |
| `checkbox`    | Each individual checkbox in the group        |

## Stimulus Values

| Value                  | Type      | Default | Description                                                                              |
|------------------------|-----------|---------|------------------------------------------------------------------------------------------|
| `disable-indeterminate` | `Boolean` | `false` | When `true`, skips indeterminate state — the master is only checked when all are checked |

## Basic usage

```html
<div data-controller="checkbox-select-all">
    <label>
        <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
        Select all
    </label>

    <label>
        <input type="checkbox" name="ids[]" value="1" data-checkbox-select-all-target="checkbox" />
        Item 1
    </label>
    <label>
        <input type="checkbox" name="ids[]" value="2" data-checkbox-select-all-target="checkbox" />
        Item 2
    </label>
    <label>
        <input type="checkbox" name="ids[]" value="3" data-checkbox-select-all-target="checkbox" />
        Item 3
    </label>
</div>
```

The master checkbox becomes indeterminate when some (but not all) items are checked.

## Without indeterminate state

```html
<div
    data-controller="checkbox-select-all"
    data-checkbox-select-all-disable-indeterminate-value="true"
>
    <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />

    <input type="checkbox" name="ids[]" value="1" data-checkbox-select-all-target="checkbox" />
    <input type="checkbox" name="ids[]" value="2" data-checkbox-select-all-target="checkbox" />
    <input type="checkbox" name="ids[]" value="3" data-checkbox-select-all-target="checkbox" />
</div>
```

In this mode the master checkbox is only checked when every item is checked; it never shows the indeterminate state.

## In a table

```html
<table>
    <thead>
        <tr data-controller="checkbox-select-all">
            <th>
                <input type="checkbox" data-checkbox-select-all-target="checkboxAll" />
            </th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <input
                    type="checkbox"
                    name="ids[]"
                    value="1"
                    data-checkbox-select-all-target="checkbox"
                />
            </td>
            <td>Alice</td>
        </tr>
        <tr>
            <td>
                <input
                    type="checkbox"
                    name="ids[]"
                    value="2"
                    data-checkbox-select-all-target="checkbox"
                />
            </td>
            <td>Bob</td>
        </tr>
    </tbody>
</table>
```

> The controller element must be an ancestor of both `checkboxAll` and `checkbox` targets. In the table example the controller lives on `<tr>` inside `<thead>`, but the `checkbox` targets are in `<tbody>` — this works because `data-controller` looks up descendants at any depth.
