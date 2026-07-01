# Checkbox Group

Renders a group of checkboxes from an `options` array, with optional "select-all" master checkbox via the
`checkbox-select-all` Stimulus controller.

Each checkbox gets a unique `id` derived from the group name and option value. All checkboxes share the same
`aria-describedby` pointing to the group's error element.

Flat (non-associative) options arrays are automatically normalized: `['main', 'dev']` becomes
`['main' => 'main', 'dev' => 'dev']`.

## Quick example

```blade
<x-hwc::checkbox-group
    name="user_ids[]"
    :options="$users->pluck('name', 'id')->toArray()"
    select-all
/>
```

## Props

| Prop               | Type           | Default        | Description                                                          |
|--------------------|----------------|----------------|----------------------------------------------------------------------|
| `name`             | `string\|null` | —              | Input name. Auto-normalized to `foo[]` if you pass `foo` (see below) |
| `options`          | `array`        | `[]`           | `[value => label]` pairs                                             |
| `selected`         | `array`        | `[]`           | Values that should be checked                                        |
| `select-all`       | `bool`         | `false`        | Renders a master checkbox that toggles all items                     |
| `select-all-label` | `string\|null` | `"Select all"` | Custom label for the master checkbox                                 |
| `class`            | `string`       | `""`           | Merged on each checkbox `<input>`                                    |
| `wrapper-class`    | `string`       | `""`           | Merged on the wrapper `<div>`                                        |
| `label-class`      | `string`       | `""`           | Merged on each item `<label>`                                        |
| `old`              | `bool`         | `true`         | When `true`, merges `old()` input over `selected`                    |
| `id`               | `string\|null` | derived        | Base id for per-checkbox ids and error reference                     |
| `errorKey`         | `string\|null` | derived        | Override when HTML `name` ≠ Laravel validation key                   |

## Name auto-normalization

Checkbox groups submit one HTTP field per checked item. PHP only collects them into an array when the `name` ends in
`[]` — otherwise it silently keeps only the last submitted value. To prevent this footgun, the component appends `[]`
automatically when missing:

```blade
{{-- These render identically --}}
<x-hwc::checkbox-group name="ids" :options="$opts" />
<x-hwc::checkbox-group name="ids[]" :options="$opts" />
```

Both produce `<input ... name="ids[]" ...>`. In debug mode (`APP_DEBUG=true`, non-testing env), passing `name="ids"`
triggers an `E_USER_NOTICE` so you can tighten the call site. Validation keys and per-checkbox ids are unaffected —
they're always derived from the unbracketed name (`ids`, `ids-1`, `ids-error`).

## ARIA

Each checkbox (including the select-all master) emits:

- `id="{baseId}-{valueSlug}"` — unique per checkbox (e.g. `roles-admin`, `roles-editor`)
- `aria-describedby="{baseId}-error"` — points to the group's error element
- `aria-invalid="true"` and `data-invalid` when the field has validation errors

The select-all checkbox gets `id="{baseId}-all"`.

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

When `select-all` is enabled, the wrapper gets `data-controller="checkbox-select-all"`, the master checkbox gets
`data-checkbox-select-all-target="checkboxAll"`, and each item gets `data-checkbox-select-all-target="checkbox"`. The
controller handles indeterminate state automatically.

```blade
<x-hwc::checkbox-group
    name="tags[]"
    :options="[1 => 'Laravel', 2 => 'Hotwire', 3 => 'Stimulus']"
    select-all
    select-all-label="All tags"
/>
```

## Flat options arrays

Non-associative arrays are normalized, so values serve as both keys and labels:

```blade
<x-hwc::checkbox-group
    name="branchs[]"
    :options="['main', 'dev', 'next']"
    :selected="['main', 'dev']"
/>
```

This renders `value="main"`, `value="dev"`, `value="next"` — not `value="0"`, `value="1"`, `value="2"`.

## Inheriting from `<x-hwc::field>`

When inside `<x-hwc::field>`, `name`, `id`, and `errorKey` are inherited via `@aware`:

```blade
<x-hwc::field name="roles[]" label="Roles">
    <x-hwc::checkbox-group :options="[1 => 'Admin', 2 => 'Editor']" />
</x-hwc::field>
```

## Disable indeterminate state

The select-all checkbox shows an indeterminate (dash) state when some — but not all — items are checked. To disable this
behavior and only toggle between fully checked and unchecked:

```blade
<x-hwc::checkbox-group
    name="tags[]"
    :options="[1 => 'Laravel', 2 => 'Hotwire']"
    select-all
    data-checkbox-select-all-disable-indeterminate-value="true"
/>
```

## Required controllers

`hotwire:check` looks for `checkbox-select-all` when you use the `select-all` prop.
