# Field

Thin context wrapper that propagates `name`, `errorKey`, and `required` to nested `<x-hwc::label>`, `<x-hwc::input>`,
`<x-hwc::checkbox-group>`, `<x-hwc::select>`, `<x-hwc::textarea>`, and `<x-hwc::error>` via `@aware`.

Auto-renders `<x-hwc::label>` when `label` is provided, `<x-hwc::description>` when `description` is provided, and
`<x-hwc::error>` at the end when `name` is set (opt-out with `:error="false"`).

## Quick example

```blade
<x-hwc::field name="email" label="E-mail" required>
    <x-hwc::input type="email" />
</x-hwc::field>
```

The label gets `for="email"` + the `required` marker (default `*`), the input gets `id="email"` + `name="email"` +
`aria-required` + `aria-describedby="email-error"`, and an `<x-hwc::error>` with `id="email-error"` is rendered
automatically after the slot.

## Quick example with description

```blade
<x-hwc::field name="email" label="E-mail" description="We will never share your email.">
    <x-hwc::input type="email" />
</x-hwc::field>
```

The description renders as `<p data-slot="description">` between the slot and the auto-rendered error.

## Props

| Prop            | Type           | Default | Description                                                                                      |
|-----------------|----------------|---------|--------------------------------------------------------------------------------------------------|
| `name`          | `string\|null` | `null`  | Field name. Propagated to children — derives `id`, `for`, and `errorKey`.                        |
| `label`         | `string\|null` | `null`  | Auto-renders `<x-hwc::label>` before the slot. String empty or `null` skips it.                  |
| `description`   | `string\|null` | `null`  | Auto-renders `<x-hwc::description>` between the slot and error. String empty or `null` skips it. |
| `requiredLabel` | `string`       | `"*"`   | Marker text passed to the auto-rendered `<x-hwc::label>`.                                        |
| `errorKey`      | `string\|null` | `null`  | Override the validation key when HTML `name` ≠ Laravel error key.                                |
| `required`      | `bool\|null`   | `null`  | Marks the field required. Propagated to label (marker) and input (ARIA).                         |
| `error`         | `bool`         | `true`  | Auto-render `<x-hwc::error>` after the slot. Set `:error="false"` to opt out.                    |
| `class`         | `string`       | `""`    | Merged on the wrapper `<div>`.                                                                   |

## ARIA contract

`<x-hwc::input>`, `<x-hwc::select>`, `<x-hwc::textarea>`, and `<x-hwc::checkbox-group>` always emit
`aria-describedby="{id}-error"`. The matching error container must exist in the DOM — the field auto-renders one for you
when `name` is set, so the contract holds by default.

If you opt out via `:error="false"`, you must render `<x-hwc::error>` somewhere in the slot yourself.

## Label ordering

The auto-rendered label sits **before** the slot, so your controls come after the label:

```
<label>E-mail *</label>
<-- slot (input, select, etc.) -->
<-- description (if provided) -->
<-- error -->
```

## Custom layout

When you need more control — custom label content, label wrapping inputs, different ordering — skip the `label` and
`description` props and compose children manually in the slot:

```blade
<x-hwc::field name="document" required :error="false" class="mb-4">
    <x-hwc::label class="text-sm font-bold">Document</x-hwc::label>
    <p class="text-xs text-gray-500">CPF ou CNPJ</p>
    <x-hwc::input class="w-full" clearable mask="cpf-cnpj" />
    <x-hwc::error class="text-red-500 text-sm" />
</x-hwc::field>
```

Each child owns its own `class`. No `*-class` props on the field.

## When NOT to use a field

If you need fine-grained control over order, labels wrapping controls, or multiple errors per field, skip
`<x-hwc::field>` entirely and compose the individual components directly:

```blade
<div class="flex flex-col gap-2">
    <x-hwc::label for="email">E-mail</x-hwc::label>
    <x-hwc::input name="email" type="email" />
    <x-hwc::error name="email" />
</div>
```

## Override `id`

The field doesn't accept an `id` prop — passing `id` via `@aware` would break the error component's id derivation (it
appends `-error`, but `@aware`-fed values are used raw). If you need a custom `id`, set it on each child:

```blade
<x-hwc::field name="variables[0][name]">
    <x-hwc::label for="var-0">Variables</x-hwc::label>
    <x-hwc::input id="var-0" />
    <x-hwc::error id="var-0-error" />
</x-hwc::field>
```
