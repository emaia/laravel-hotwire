# Field

Thin context wrapper that propagates `name`, `errorKey`, and `required` to nested `<x-hwc::label>`, `<x-hwc::input>`, and `<x-hwc::error>` via `@aware`. Auto-renders `<x-hwc::error>` at the end of the field when `name` is set (opt-out with `:error="false"`).

## Quick example

```blade
<x-hwc::field name="email" required>
    <x-hwc::label>E-mail</x-hwc::label>
    <x-hwc::input type="email" />
</x-hwc::field>
```

The label gets `for="email"` + required marker, the input gets `id="email"` + `name="email"` + `aria-required` + `aria-describedby="email-error"`, and an `<x-hwc::error>` with `id="email-error"` is rendered automatically after the slot.

## Props

| Prop       | Type           | Default   | Description                                                                |
|------------|----------------|-----------|----------------------------------------------------------------------------|
| `name`     | `string\|null` | `null`    | Field name. Propagated to children — derives `id`, `for`, and `errorKey`. |
| `errorKey` | `string\|null` | `null`    | Override the validation key when HTML `name` ≠ Laravel error key.          |
| `required` | `bool\|null`   | `null`    | Marks the field required. Propagated to label (marker) and input (ARIA).   |
| `error`    | `bool`         | `true`    | Auto-render `<x-hwc::error>` after the slot. Set `:error="false"` to opt out and render it manually elsewhere. |
| `class`    | `string`       | `""`      | Merged on the wrapper `<div>`.                                             |

## ARIA contract

`<x-hwc::input>` always emits `aria-describedby="{id}-error"`. The matching error container must exist in the DOM — the field auto-renders one for you when `name` is set, so the contract holds by default. If you opt out via `:error="false"`, you must render `<x-hwc::error>` somewhere in the slot yourself.

## Custom error placement or styling

The auto-rendered error sits at the bottom of the field with default styling. Opt out with `:error="false"` and place a custom `<x-hwc::error>` anywhere in the slot:

```blade
<x-hwc::field name="documento" required :error="false" class="mb-4">
    <x-hwc::label class="label">Documento</x-hwc::label>
    <p class="text-xs text-gray-500">CPF ou CNPJ</p>
    <x-hwc::input class="input w-full" clearable mask="cpf-cnpj" />
    <x-hwc::error class="text-red-500 text-sm" />
</x-hwc::field>
```

Each child owns its own `class`. No `*-class` props on the field.

## Override `id`

The field doesn't accept an `id` prop — passing `id` via `@aware` would break the error component's id derivation (it appends `-error`, but `@aware`-fed values are used raw). If you need a custom `id`, set it on each child:

```blade
<x-hwc::field name="variables[0][name]">
    <x-hwc::label for="var-0">Variables</x-hwc::label>
    <x-hwc::input id="var-0" />
    <x-hwc::error id="var-0-error" />
</x-hwc::field>
```
