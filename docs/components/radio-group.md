# Radio Group

Renders a group of native radio inputs from an `options` array or rich `<hw:radio-group.item>` children.

Use `<hw:radio-group>` for mutually exclusive form choices that submit a single value. Use `<hw:checkbox-group>` for
multi-select checkbox semantics, and `<hw:toggle-group>` for pressed-button UI that can be cleared.

## Quick example

```blade
<hw:radio-group
    name="plan"
    :options="['free' => 'Free', 'pro' => 'Pro', 'team' => 'Team']"
    selected="pro"
/>
```

## Props

| Component          | Prop                | Type                   | Default    | Description                                                                |
|--------------------|---------------------|------------------------|------------|----------------------------------------------------------------------------|
| `radio-group`      | `name`              | `string\|null`         | —          | Input name shared by every radio.                                          |
| `radio-group`      | `options`           | `array`                | `[]`       | `[value => label]` pairs. Flat arrays use the value as both key and label. |
| `radio-group`      | `selected`          | `mixed`                | `null`     | Value that should be checked.                                              |
| `radio-group`      | `disabled`          | `bool`                 | `false`    | Disables every option and rich item radio.                                 |
| `radio-group`      | `orientation`       | `horizontal\|vertical` | `vertical` | Layout hook for presets. Invalid values fall back to `vertical`.           |
| `radio-group`      | `class`             | `string`               | `""`       | Merged on each generated option `<input>`.                                 |
| `radio-group`      | `wrapper-class`     | `string`               | `""`       | Merged on the wrapper `<div>`.                                             |
| `radio-group`      | `label-class`       | `string`               | `""`       | Merged on each generated option `<label>`.                                 |
| `radio-group`      | `old`               | `bool`                 | `true`     | Restores selected value from `old()` input.                                |
| `radio-group`      | `id`                | `string\|null`         | derived    | Base id for per-radio ids and error reference.                             |
| `radio-group`      | `errorKey`          | `string\|null`         | derived    | Override when HTML `name` differs from the Laravel validation key.         |
| `radio-group`      | `auto-submit`       | `bool\|string`         | `false`    | Add auto-submit wiring to each radio change event.                         |
| `radio-group`      | `auto-submit-delay` | `int\|string\|null`    | `null`     | Per-field debounce override when `auto-submit="debounced"` is used.        |
| `radio-group.item` | `value`             | `mixed`                | required   | Submitted radio value.                                                     |
| `radio-group.item` | `checked`           | `bool\|string\|null`   | `false`    | Force this rich item checked in addition to the group `selected` value.    |
| `radio-group.item` | `disabled`          | `bool\|null`           | inherited  | Disable only this rich item.                                               |
| `radio-group.item` | `class`             | `string`               | `""`       | Merged on the rich item `<input>`.                                         |
| `radio-group.item` | `label-class`       | `string`               | `""`       | Merged on the rich item `<label>`.                                         |

Any other HTML attribute on `<hw:radio-group>` passes through to the wrapper. Attributes on `<hw:radio-group.item>` pass
through to the item label.

## Options

```blade
<hw:radio-group
    name="visibility"
    :options="['public' => 'Public', 'private' => 'Private']"
    :selected="$post->visibility"
/>
```

Flat arrays are normalized so values serve as both keys and labels:

```blade
<hw:radio-group name="plan" :options="['free', 'pro', 'team']" />
```

This renders `value="free"`, `value="pro"`, and `value="team"`.

## Rich items

Use `<hw:radio-group.item>` when each option needs custom markup. The item inherits `name`, `selected`, `old`,
`errorKey`, `disabled`, and `auto-submit` from the parent group.

```blade
<hw:radio-group name="plan" selected="pro">
    <hw:radio-group.item value="free">
        <span class="font-medium">Free</span>
        <span class="text-muted-foreground">For personal projects.</span>
    </hw:radio-group.item>

    <hw:radio-group.item value="pro">
        <span class="font-medium">Pro</span>
        <span class="text-muted-foreground">For growing teams.</span>
    </hw:radio-group.item>
</hw:radio-group>
```

You can combine `options` and rich items in the same group; options render first, then the slot content.

## Inheriting from `<hw:field>`

When inside `<hw:field>`, `name`, `id`, and `errorKey` are inherited via `@aware`:

```blade
<hw:field name="plan" label="Plan">
    <hw:radio-group :options="['free' => 'Free', 'pro' => 'Pro']" />
</hw:field>
```

## ARIA and validation

Each radio emits:

- `id="{baseId}-{valueSlug}"` — unique per radio, e.g. `plan-free`
- `aria-describedby="{baseId}-error"` — points to the group's error element
- `aria-invalid="true"` and `data-invalid` when the field has validation errors

Use `error-key` when the HTML name differs from the validation key:

```blade
<hw:field name="settings[plan]" error-key="plan">
    <hw:radio-group :options="$plans" />
</hw:field>
```

## Auto-submit

`auto-submit` adds auto-submit wiring to every radio. The `auto-submit` controller should live on an ancestor form:

```blade
<hw:form auto-submit>
    <hw:radio-group name="plan" :options="$plans" auto-submit />
</hw:form>
```

Groups submit immediately by default. Use `auto-submit="debounced" auto-submit-delay="..."` to debounce changes.

## Styling Hooks

- `data-slot="radio-group"`
- `data-slot="radio-group-item"`
- `data-slot="radio-group-input"`
- `data-slot="radio-group-item-content"`
- `data-orientation="horizontal|vertical"`
- `data-checkable="true"`

## Required controllers

`auto-submit` is only required when the `auto-submit` prop is used.
