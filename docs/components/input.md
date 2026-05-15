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
| `checked`       | `bool`         | `false`                        | Initial checked state for `type="checkbox"` / `type="radio"`      |
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

## Checkbox and radio

For `type="checkbox"` and `type="radio"`, `value` is the HTML value attribute (what is posted when the input is marked), and `checked` controls the initial state.

```blade
{{-- Single checkbox / switch --}}
<x-hwc::input type="checkbox" name="notify" :checked="$user->notify" />

{{-- Checkbox group --}}
<x-hwc::input type="checkbox" name="roles[]" value="admin"
    :checked="in_array('admin', $user->roles ?? [])" />

{{-- Radio --}}
<x-hwc::input type="radio" name="plan" value="pro"
    :checked="$user->plan === 'pro'" />
```

When the page is re-rendered after a validation redirect (i.e. Laravel has flashed `_old_input`), the component derives `checked` from the submitted state instead of `:checked` — so a checkbox the user just toggled survives the round-trip. Specifically:

- **Single checkbox** (no explicit `value`): checked iff the key is present in old input.
- **Checkbox group** (`name="roles[]"`): checked iff the input's `value` appears in the flashed array.
- **Exclusive checkboxes** (several inputs sharing a `name`, each with a distinct `value`): checked iff the flashed scalar equals the input's `value` — same comparison as radio.
- **Radio**: checked iff the flashed value equals the input's `value`.

`old()`-driven derivation kicks in only while flash data exists; on a fresh load it falls back to `:checked`. Pass `:old="false"` to opt out entirely.

The `clearable`, `mask`, `auto-select`, and `:counter` props are no-ops for checkable types.

### Automatic unique ids for groups

Multiple `<x-hwc::input type="radio">` sharing a `name` — or `type="checkbox"` with `name="…[]"` — would otherwise collide on `id`. The component avoids that by appending `-{slug(value)}` to the derived id:

```blade
<x-hwc::input type="checkbox" name="size[]" value="default" />     {{-- id="size-default" --}}
<x-hwc::input type="checkbox" name="size[]" value="comfortable" /> {{-- id="size-comfortable" --}}
<x-hwc::input type="radio"    name="plan"   value="pro" />          {{-- id="plan-pro" --}}
```

The slug uses `Illuminate\Support\Str::slug`, so any string value is safe. `aria-describedby` still points to the **base** error id (`plan-error`, `size-error`), so all inputs in the group bind to the same `<x-hwc::error>` node — which is what Laravel's per-name validation produces.

Passing an explicit `id` opts out of the auto-derivation: the component uses your id verbatim and derives `aria-describedby` from it.

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
