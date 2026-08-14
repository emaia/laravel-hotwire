# Conditional Fields

Show or hide form fields based on the value of other form fields, with zero round-trips to the server. The controller auto-detects triggers from `data-when-*` attributes on each dependent and flips visibility plus the `disabled` cascade as the user fills in the form.

**Identifier:** `conditional-fields`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers conditional-fields`.

## Requirements

- No external dependencies.

> **Recommended path:** use the `<hw:conditional-field>` Blade component instead of writing the raw data attributes by hand. The component encodes the rule once on the server and renders the right markup for both initial state and runtime behavior. See [`<hw:conditional-field>` docs](../components/conditional-field.md).

## Basic Usage

```html
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <select name="reason">
        <option value="">Pick one...</option>
        <option value="bug" {{ old('reason') === 'bug' ? 'selected' : '' }}>Bug</option>
        <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>Other</option>
    </select>

    <fieldset
        data-conditional-fields-target="dependent"
        data-when-reason="other"
        @if (old('reason') !== 'other') hidden disabled @endif
    >
        <input name="other_reason" value="{{ old('other_reason') }}" />
    </fieldset>
</form>
```

The fieldset's initial `hidden disabled` mirrors the rule so the page renders with the right state before JavaScript runs. The controller re-confirms on `connect()` and takes over for subsequent changes.

Writing this by hand encodes the rule twice: once in `data-when-reason="other"`, once in `@if (old('reason') !== 'other')`. A drift between the two causes a flash, and worse, can briefly reveal sensitive fields. Reach for [`<hw:conditional-field>`](../components/conditional-field.md) so the rule lives in exactly one place.

## Multiple Conditions

Each dependent declares one or more `data-when-{field-name}` attributes:

```html
<fieldset
    data-conditional-fields-target="dependent"
    data-when-reason="bug|feature"
    data-when-severity="high"
>
    ...
</fieldset>
```

Within a single attribute, values are pipe-separated (`|`) and OR-matched. `data-when-reason="bug|feature"` matches when `reason` equals either `bug` or `feature`. Pipe is the separator rather than whitespace so that real-world values containing spaces, such as full names like `"Kris Jhonson"`, country labels like `"United States"`, or statuses like `"In Progress"`, match literally without being split into pieces.

Across multiple `data-when-*`, conditions are AND-combined. The dependent above appears only when `reason` is `bug` or `feature` and `severity` is `high`.

## Checkbox State

For boolean checkboxes (typical `<input type="checkbox" name="x" value="1" />`), match the checked or unchecked state directly:

```html
<input type="checkbox" name="ship_different" value="1" />

<fieldset data-conditional-fields-target="dependent" data-when-ship-different=":checked">
    ...
</fieldset>
```

## Not Limited To Forms

The controller hosts on any container that wraps the named inputs: filter bars, dashboard toolbars, in-page configuration panels or rule builders. As long as the descendants have `name` attributes and the dependents declare their `data-when-*` rules, the controller behaves the same.

```html
<section data-controller="conditional-fields" class="filter-bar">
    <select name="status">
        <option value="">All</option>
        <option value="open">Open</option>
        <option value="closed">Closed</option>
    </select>

    <fieldset data-conditional-fields-target="dependent" data-when-status="closed">
        <input type="date" name="closed_after" />
        <input type="date" name="closed_before" />
    </fieldset>
</section>
```

## Behavior

The controller scans every dependent for `data-when-{name}` attributes and infers the trigger field names from them. No explicit `trigger` target is needed. Field lookup is forgiving: a `data-when-ship-different` attribute resolves to `[name="ship_different"]`, `[name="ship-different"]`, or `[name="ship_different[]"]` automatically.

When a dependent should be hidden, the controller sets both `hidden` and `disabled` on the dependent element. Both attributes are required: `hidden` alone does not exclude form fields from submission; `disabled` does.

The recommended dependent element is `<fieldset>`. Setting `fieldset.disabled = true` cascades to every descendant form control via the HTML5 spec, so the controller does no per-field bookkeeping.

For `<div>` or any non-`<fieldset>`, the controller walks descendant controls, saves each field's original `disabled` state in `data-conditional-fields-prev-disabled`, then forces `disabled = true`. On show, it restores the saved values, including controls that were disabled before the dependent was hidden. This is runtime behavior only: without JavaScript, a wrapper's `disabled` attribute cascades to descendants only when the wrapper is a `<fieldset>`, which is why the component recommends its default fieldset.

The evaluation runs on `connect()` and on every `change` / `input` event inside the controller's scope. The events are delegated; no per-field listener attachment is needed.

## Limitations

- No negation operator. `data-when-reason="not-other"` is treated as a literal value `not-other`, not as negation.
- No comparison operators (`>`, `<=`, regex). For complex predicates, write a small custom controller.
- Bracketed nested names like `user[email]` are not supported as trigger names because the data attribute name would be invalid. Top-level names, including `name[]` array names, work.
- Trigger values containing a literal `|` clash with the OR separator. Normalize such values (slug, code) before using them as triggers. `:when="['country' => $user->country_code]"` is safer than matching a free-text label with `|` in it.
- No animation. Visibility flips synchronously; `hidden` is binary.

## Targets

| Target      | Description                                                                                  |
|-------------|----------------------------------------------------------------------------------------------|
| `dependent` | A block of fields that should appear or disappear based on the value of one or more triggers. |

## Params

### Rule Grammar

Each dependent declares one or more `data-when-{field-name}` attributes. Values inside one attribute are OR-matched with `|`; multiple `data-when-*` attributes are AND-combined.

### Tokens For Checkbox State

| Token        | Matches                                      |
|--------------|----------------------------------------------|
| `:checked`   | The checkbox (or any in a group) is checked. |
| `:unchecked` | No checkbox in the group is checked.         |

### Supported Trigger Types

| Trigger                  | Effective value used for matching                                      |
|--------------------------|------------------------------------------------------------------------|
| `<select>`               | `.value`                                                               |
| `<input type="text" ...>` | `.value`                                                               |
| Radio group              | The value of the checked radio (empty if none).                        |
| Single checkbox          | Tokens above, or the `value` attribute when checked.                   |
| Checkbox group `name[]`  | Array of checked values; match any value listed in the `data-when-*`.  |
