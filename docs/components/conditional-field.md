# Conditional Field

Renders a dependent block for the [`conditional-fields`](../controllers/conditional-fields.md)
controller — the show/hide rule lives in **exactly one place**, the `when` prop, and the
component takes care of both the server-side initial visibility and the runtime data attributes
the controller reads.

The component eliminates the classic "rule duplication" pitfall: without it, the dependent
markup has to encode the rule twice (once in `data-when-*` attributes for the client, once in
`@if (...) hidden disabled @endif` for the server), and any drift between the two flashes the
wrong fields on first paint.

## Props

| Prop    | Type     | Default      | Description                                                                                                |
|---------|----------|--------------|------------------------------------------------------------------------------------------------------------|
| `when`  | `array`  | (required)   | The rule: `['field' => 'value']`, `['field' => ['v1', 'v2']]` (OR), or `['field' => ':checked']`. Multiple entries AND-match. |
| `model` | `mixed`  | `null`       | Source of attribute fallbacks for edit forms. Anything `data_get()` can read — Eloquent model, array, stdClass. The component evaluates `old($field, data_get($model, $field))` per trigger. |
| `tag`   | `string` | `'fieldset'` | Wrapper element. `<fieldset>` is recommended — the `disabled` cascade reaches every descendant control for free. |

## How the initial value is resolved

For each `field` in `when`, the component reads the current value via:

```
old($field, data_get($model, $field))
```

- After validation retry — `old()` returns the failed-submission value from session. The model fallback is skipped.
- Fresh GET on an edit form (model passed) — `old()` is empty, falls back to `$model->$field`.
- Fresh GET with no model — `null`. Dependent renders `hidden disabled` by default.

This is the same lookup `<x-hwc::input>`, `<x-hwc::select>`, and `<x-hwc::textarea>` already perform when you set `:value="$message->field"` or `:selected="$message->field"` on them — evaluated once on the server, no duplicate state map to maintain.

## Wrapping with the controller

Put `data-controller="conditional-fields"` on the form (or any parent of the dependents and their
triggers). The component handles the rest:

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <x-hwc::select
        name="reason"
        placeholder="Pick one…"
        :options="[
            'bug'     => 'Bug',
            'feature' => 'Feature',
            'other'   => 'Other',
        ]"
    />

    <x-hwc::conditional-field :when="['reason' => ['bug', 'feature']]">
        <x-hwc::field name="details" label="What happened?">
            <x-hwc::textarea name="details" />
        </x-hwc::field>
    </x-hwc::conditional-field>

    <x-hwc::conditional-field :when="['reason' => 'other']">
        <x-hwc::field name="other_reason" label="Tell us">
            <x-hwc::input name="other_reason" />
        </x-hwc::field>
    </x-hwc::conditional-field>

    <button type="submit">Send</button>
</form>
```

## Edit forms — the `model` prop

`<x-hwc::input>`, `<x-hwc::select>`, and `<x-hwc::textarea>` each render **one field** with the
value you hand them via `:value` / `:selected`, merging `old()` on top automatically.

`<x-hwc::conditional-field>` decides visibility for the **whole block** by resolving the `when`
rule server-side, often across multiple fields. It can't peek at the sibling form fields — they
are separate components — so it does its own `old($field, data_get($model, $field))` lookup.
`:model` hands it the same source the fields already read from. No parallel state map.

```blade
<form data-controller="conditional-fields" action="/messages/{{ $message->id }}" method="POST">
    @csrf @method('PATCH')

    <x-hwc::select
        name="reason"
        :options="$reasons"
        :selected="$message->reason"
    />

    <x-hwc::conditional-field :model="$message" :when="['reason' => 'other']">
        <x-hwc::input name="other_reason" :value="$message->other_reason" />
    </x-hwc::conditional-field>
</form>
```

A failed validation retry still wins over the model — `old()` returns the user's last value, the
component matches against it, and the dependent's initial visibility lines up with what the
user just submitted.

## Token shortcuts

| Rule                                          | Meaning                                                                |
|-----------------------------------------------|------------------------------------------------------------------------|
| `['ship_different' => ':checked']`            | Match when the named field's resolved value (`old()` / model fallback) is truthy. |
| `['agree' => ':unchecked']`                   | Match when the named field is empty / unchecked.                       |
| `['plan' => 'enterprise']`                    | Equality.                                                              |
| `['plan' => ['pro', 'enterprise']]`           | OR.                                                                    |
| `['authorized' => 'no', 'needs_visa' => 'yes']` | AND across two fields.                                                |

## When to use `<div>` instead of `<fieldset>`

For a single-field dependent that does not need a `<legend>`, `<div>` is acceptable:

```blade
<x-hwc::conditional-field tag="div" :when="['mode' => 'advanced']" class="mt-4">
    <x-hwc::input name="threshold" />
</x-hwc::conditional-field>
```

The controller walks descendant inputs and toggles their `disabled` state instead of using the
`<fieldset>` cascade. Slightly more work at runtime but the markup stays flatter.

## Multiple dependents reuse the same controller

A single `data-controller="conditional-fields"` on the form is enough — every `<x-hwc::conditional-field>`
under it registers as a dependent target automatically. No per-dependent wiring.

## See also

- [Conditional fields controller](../controllers/conditional-fields.md) — the underlying
  Stimulus controller, with the full rule grammar reference.
- [Conditional fields recipe](../recipes/conditional-fields.md) — real-world form patterns
  (checkout shipping, subscription tiers, NPS survey, newsletter preferences).
