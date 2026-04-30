# Error

Always-rendered error container bound to a form field via `name` (or explicit `errorKey`). The element is kept in the DOM (with `hidden`) when there are no errors, so the input's `aria-describedby` stays stable.

## Quick example

```blade
<x-hwc::error name="email" />
```

Renders:

```html
<div id="email-error" role="alert" aria-live="polite" class="hwc-error hidden" hidden></div>
```

When validation fails:

```html
<div id="email-error" role="alert" aria-live="polite" class="hwc-error">
    The email field is required.
</div>
```

If multiple messages are present, they are wrapped in `<ul><li>` automatically.

## Props

| Prop        | Type                                  | Default                   | Description                                                  |
|-------------|---------------------------------------|---------------------------|--------------------------------------------------------------|
| `name`      | `string\|null`                        | —                         | Drives `errorKey` and `id` derivation                        |
| `errorKey`  | `string\|null`                        | derived from `name`       | Override when HTML name ≠ validation key                     |
| `messages`  | `string\|array\|null`                 | `$errors->get($errorKey)` | Override the messages source                                 |
| `id`        | `string\|null`                        | `{derivedId}-error`       | Override the element id (must match input's `aria-describedby`) |
| `class`     | `string`                              | `""`                      | Merged                                                       |

## Auto-derivation

```blade
<x-hwc::error name="variables[0][name]" />
{{-- id="variables-0-name-error", errorKey="variables.0.name" --}}
```

## Inheriting from `<x-hwc::field>`

When inside `<x-hwc::field>`, `name`, `errorKey` and `id` are inherited via `@aware`:

```blade
<x-hwc::field name="email" label="E-mail">
    <x-hwc::input type="email" />
    {{-- Field renders <x-hwc::error /> automatically at the bottom --}}
</x-hwc::field>
```
