# Conditional Field

Renders a dependent block for the [`conditional-fields`](../controllers/conditional-fields.md)
controller. The show/hide rule lives in the `when` prop, and the component renders both the
server-side initial visibility and the runtime `data-when-*` attributes the controller reads.

The component eliminates the classic rule duplication pitfall: dependent markup should not encode
the same rule once for JavaScript and once for first paint. Drift between those two copies flashes
the wrong fields before the controller connects.

## Form Integration

Add `conditional-fields` to `<hw:form>` so it mounts the controller. Use `state` when the first
render needs model-backed trigger values, such as edit forms:

```blade
<hw:form conditional-fields :state="['reason' => $selectedReason]">
    <hw:field name="reason" label="Reason">
        <hw:select
            :selected="$selectedReason"
            :options="[
                'bug'     => 'Bug',
                'feature' => 'Feature',
                'other'   => 'Other',
            ]"
        />
    </hw:field>

    <hw:conditional-field when="reason=bug|feature">
        <hw:field name="details" label="What happened?">
            <hw:textarea />
        </hw:field>
    </hw:conditional-field>

    <hw:conditional-field when="reason=other">
        <hw:field name="other_reason" label="Tell us">
            <hw:input />
        </hw:field>
    </hw:conditional-field>

    <hw:button type="submit">Send</hw:button>
</hw:form>
```

`state` can be an Eloquent model, array, or object readable by `data_get()`. Validation retries still
win over state via `old()`, so the initial visibility matches the values the user just submitted.

## String Rules

For common rules, pass a compact `field=value` string:

```blade
<hw:conditional-field when="type=link" />
<hw:conditional-field when="type=link|text" />
<hw:conditional-field when="active=:checked" />
<hw:conditional-field when="type=link location=sidebar" />
```

- `=` separates the trigger field from the expected value.
- `|` means OR for a single field.
- A space means AND across fields.
- `:checked` and `:unchecked` match checkbox state.

Use the array form when it is clearer for complex rules:

```blade
<hw:conditional-field :when="[
    'type' => ['link', 'text'],
    'active' => ':checked',
]">
    ...
</hw:conditional-field>
```

## Server-Rendered State

`<hw:input>`, `<hw:select>`, and `<hw:textarea>` each render one field with the value you hand them
via `:value` / `:selected`, merging `old()` on top automatically.

`<hw:conditional-field>` decides visibility for the whole block by resolving the `when` rule
server-side, often across multiple fields. It cannot peek at sibling form fields because they are
separate components, so it resolves trigger values with:

```text
old($field, data_get($state, $field))
```

Put `state` on the form to share the same source the trigger fields already read from:

```blade
<hw:form conditional-fields :state="$message" :action="route('messages.update', $message)" method="patch">
    <hw:field name="reason" label="Reason">
        <hw:select :options="$reasons" :selected="$message->reason" />
    </hw:field>

    <hw:conditional-field when="reason=other">
        <hw:field name="other_reason" label="Tell us">
            <hw:input :value="$message->other_reason" />
        </hw:field>
    </hw:conditional-field>
</hw:form>
```

A failed validation retry still wins over state. `old()` returns the user's last value, the component
matches against it, and the dependent's initial visibility lines up with what the user just submitted.

### State Is About The Triggers

The fields inside the block are irrelevant to whether you pass `state`. What matters is whether the
trigger fields named in `when` carry an initial value.

```blade
<hw:form conditional-fields :state="$message">
    {{-- 1 trigger, 1 input inside — state is what makes it visible on edit --}}
    <hw:conditional-field when="reason=other">
        <hw:input name="other_reason" :value="$message->other_reason" />
    </hw:conditional-field>

    {{-- 2 triggers AND, 1 input inside — state resolves both trigger keys --}}
    <hw:conditional-field when="authorized=no needs_visa=yes">
        <hw:select name="sponsorship_country" :options="$countries" :selected="$message->sponsorship_country" />
    </hw:conditional-field>
</hw:form>
```

Create forms can skip `state` entirely. Fresh GET requests start hidden, and validation retries still
use `old()` to restore the submitted trigger values.

### When The Trigger Name Does Not Match The State Key

The `when` key plays two roles: at runtime the controller looks for `[name="<key>"]` in the form; at
SSR the component calls `data_get($state, '<key>')`. In standard Laravel forms those align naturally.
They diverge when data lives on a related model, uses a different case convention, or sits behind a
display-only field.

Two ways out:

- Define an accessor on the model so `data_get($model, 'country')` resolves transparently.
- Pass an associative array as form `state` so each `when` key maps to the right source.

```blade
@php
$state = [
    'country'       => $user->address->country,
    'customer_type' => $invoice->customer->type,
];
@endphp

<hw:form conditional-fields :state="$state">
    <hw:conditional-field when="country=US">
        ...
    </hw:conditional-field>
</hw:form>
```

## Props

| Prop    | Type            | Default      | Description                                                                                                |
|---------|-----------------|--------------|------------------------------------------------------------------------------------------------------------|
| `when`  | `string\|array` | (required)   | Show/hide rule. Use `field=value` strings for common cases or an array for complex rules.                  |
| `state` | `mixed`         | inherited    | Optional local state override. Usually set `state` on `<hw:form>` instead. Anything `data_get()` can read. |
| `tag`   | `string`        | `'fieldset'` | Wrapper element. `<fieldset>` is recommended because the `disabled` cascade reaches descendant controls.   |

## Token Shortcuts

| Rule                           | Meaning                                          |
|--------------------------------|--------------------------------------------------|
| `ship_different=:checked`      | Match when the field's resolved value is truthy. |
| `agree=:unchecked`             | Match when the field is empty / unchecked.       |
| `plan=enterprise`              | Equality.                                        |
| `plan=pro\|enterprise`         | OR.                                              |
| `authorized=no needs_visa=yes` | AND across two fields.                           |

## When To Use `<div>` Instead Of `<fieldset>`

For a single-field dependent that does not need a `<legend>`, `<div>` is acceptable:

```blade
<hw:conditional-field tag="div" when="mode=advanced" class="mt-4">
    <hw:input name="threshold" />
</hw:conditional-field>
```

The controller walks descendant inputs and toggles their `disabled` state instead of using the
`<fieldset>` cascade. Slightly more work at runtime, but the markup stays flatter.

## Multiple Dependents Reuse The Same Controller

A single `conditional-fields` prop on the form is enough. Every `<hw:conditional-field>` under it
registers as a dependent target automatically. No per-dependent wiring.

## See Also

- [`<hw:form>`](./form.md) — mounts the controller and shares `state` with conditional fields.
- [Conditional fields controller](../controllers/conditional-fields.md) — the underlying Stimulus controller.
- [Conditional fields recipe](../recipes/conditional-fields.md) — real-world form patterns.
