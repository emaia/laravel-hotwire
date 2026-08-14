# Checkbox Select All

Adds a "select all" checkbox that controls a group of checkboxes. Use it for bulk action lists, tables and filter groups that need an indeterminate state for partial selection.

**Identifier:** `checkbox-select-all`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers checkbox-select-all`.

## Requirements

- No external dependencies.
- Ships with the `_frame_events.js` shared helper.

## Basic Usage

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

The master checkbox becomes indeterminate when some, but not all, items are checked.

## Without An Indeterminate State

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

## In A Table

```html
<table data-controller="checkbox-select-all">
    <thead>
        <tr>
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

The controller element must be an ancestor of both `checkboxAll` and `checkbox` targets. In the table example it therefore lives on `<table>`, the common ancestor of the targets in `<thead>` and `<tbody>`.

## Behavior

Native form resets and renders of the Turbo Frame that owns the group automatically re-sync both the master's `checked` and `indeterminate` state.

The controller re-syncs the master's `checked` and `indeterminate` state on every `turbo:render` and when its owning Turbo Frame dispatches `turbo:frame-render`. Under morph (`<hw:meta refresh />` or `data-turbo-action="morph"`), idiomorph updates the children's checked attributes but does not fire `targetConnected`, so the master would otherwise stay stale. Events from unrelated frames are ignored.

## Values

| Value                   | Type      | Default | Description                                                                             |
|-------------------------|-----------|---------|-----------------------------------------------------------------------------------------|
| `disable-indeterminate` | `Boolean` | `false` | When `true`, skips indeterminate state; the master is only checked when all are checked |

## Targets

| Target        | Description                                    |
|---------------|------------------------------------------------|
| `checkboxAll` | The master checkbox that selects/deselects all |
| `checkbox`    | Each individual checkbox in the group          |
