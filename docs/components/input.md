# Input

Form input with auto-derived `id`/`errorKey` from `name`, automatic `old()` merge, ARIA wiring, and optional Stimulus behaviors (`mask`, `clearable`, `auto-select`).

## Quick example

```blade
<x-hwc::input name="email" type="email" required />
```

Renders an `<input>` with:

- `id="email"`, `name="email"`
- `value` from `old('email', $value)`
- `aria-describedby="email-error"` (always set, for stable screen-reader binding)
- `aria-invalid="true"` and `data-invalid` when `$errors->has('email')`
- `aria-required="true"` when `required` is present

## Props

| Prop            | Type           | Default                        | Description                                                       |
|-----------------|----------------|--------------------------------|-------------------------------------------------------------------|
| `name`          | `string\|null` | —                              | Pass-through. Drives `id` and `errorKey` if those aren't set       |
| `id`            | `string\|null` | derived from `name`            | Override the auto-derived id                                      |
| `type`          | `string`       | `"text"`                       | Pass-through                                                      |
| `value`         | `mixed`        | `null`                         | Merged with `old($errorKey, $value)` unless `:old="false"`        |
| `errorKey`      | `string\|null` | derived from `name`            | Override for arrays where HTML `name` ≠ validation key            |
| `old`           | `bool`         | `true`                         | Disable `old()` auto-merge                                        |
| `clearable`     | `bool`         | `false`                        | Wrapper + clear button (controller `clear-input`)                 |
| `auto-select`   | `bool`         | `false`                        | Selects content on focus (controller `auto-select`)               |
| `mask`          | `string\|null` | `null`                         | Preset (`cpf`, `phone-br`, ...) or raw Maska string               |
| `class`         | `string`       | `""`                           | Merged on `<input>`                                               |
| `wrapper-class` | `string`       | `""`                           | Merged on the wrapper when one is present                         |

Any other HTML attribute (`placeholder`, `pattern`, `disabled`, `data-*`, `aria-*`) passes through.

## Auto-derivation

Laravel validates with dot notation (`variables.0.name`); HTML uses brackets (`variables[0][name]`). The component does the conversion for you:

```blade
<x-hwc::input name="variables[0][name]" />
{{-- id="variables-0-name", aria-describedby="variables-0-name-error", errorKey="variables.0.name" --}}
```

Use `error-key` when the HTML name and the validation key diverge:

```blade
<x-hwc::input name="payload[email]" error-key="user.email" />
```

## Mask presets

| Preset      | Mask                                          |
|-------------|-----------------------------------------------|
| `cpf`       | `###.###.###-##`                              |
| `cnpj`      | `##.###.###/####-##`                          |
| `phone-br`  | `["(##) ####-####", "(##) #####-####"]`       |
| `cep`       | `#####-###`                                   |
| `date`      | `##/##/####`                                  |
| `time`      | `##:##`                                       |

Unknown presets pass through as raw Maska strings.

## Inheriting from `<x-hwc::field>`

`<x-hwc::field>` is a thin context wrapper: it propagates `name`, `errorKey`, and `required` to nested `<x-hwc::label>`, `<x-hwc::input>`, and `<x-hwc::error>` via `@aware`. It does not auto-render any markup — you compose the children yourself:

```blade
<x-hwc::field name="email" required>
    <x-hwc::label>E-mail</x-hwc::label>
    <x-hwc::input type="email" auto-select />
    <x-hwc::error />
</x-hwc::field>
```

> **ARIA contract:** the input always emits `aria-describedby="{id}-error"`. For screen readers to find the description, you must render `<x-hwc::error>` inside the field. Forgetting it makes the reference dangle silently.

## Required controllers

`hotwire:check` looks for `auto-select`, `clear-input`, and `input-mask`. Only the ones you actually use need to be published.
