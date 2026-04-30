# Field

Thin context wrapper that propagates `name`, `errorKey`, and `required` to nested `<x-hwc::label>`, `<x-hwc::input>`, and `<x-hwc::error>` via `@aware`. Does not auto-render any markup — you compose the children explicitly.

## Quick example

```blade
<x-hwc::field name="email" required>
    <x-hwc::label>E-mail</x-hwc::label>
    <x-hwc::input type="email" />
    <x-hwc::error />
</x-hwc::field>
```

The label gets `for="email"` + required marker, the input gets `id="email"` + `name="email"` + `aria-required` + `aria-describedby="email-error"`, and the error renders with `id="email-error"`.

## Props

| Prop       | Type           | Default   | Description                                                                |
|------------|----------------|-----------|----------------------------------------------------------------------------|
| `name`     | `string\|null` | `null`    | Field name. Propagated to children — derives `id`, `for`, and `errorKey`. |
| `errorKey` | `string\|null` | `null`    | Override the validation key when HTML `name` ≠ Laravel error key.          |
| `required` | `bool\|null`   | `null`    | Marks the field required. Propagated to label (marker) and input (ARIA).   |
| `class`    | `string`       | `""`      | Merged on the wrapper `<div>`.                                             |

## ARIA contract

`<x-hwc::input>` always emits `aria-describedby="{id}-error"`. The matching error container must exist in the DOM, so **you must render `<x-hwc::error>` inside the field**. Forgetting it makes the ARIA reference dangle silently — screen readers won't announce the validation message even though the input still gets `aria-invalid`.

A future `hotwire:check` lint pass will catch this at build time. For now, treat it as a hand-enforced convention.

## Custom layout

The field is just a `<div class="hwc-field">` plus context. You can compose any layout inside:

```blade
<x-hwc::field name="documento" required class="mb-4">
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
