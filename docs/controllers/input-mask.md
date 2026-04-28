# Input Mask

Applies input masks using [Maska](https://beholdr.github.io/maska/). Supports static, dynamic (array) and reverse masks.

**Identifier:** `input-mask`  
**Install:** `php artisan hotwire:controllers input-mask`

## Requirements

- `maska` (`bun add maska`)

## Stimulus Values

| Value            | Type      | Default | Description                                                       |
|------------------|-----------|---------|-------------------------------------------------------------------|
| `mask`           | `String`  | —       | Mask or JSON array of masks (required)                            |
| `reversed`       | `Boolean` | `false` | Applies the mask from right to left                               |
| `eager`          | `Boolean` | `false` | Renders static characters of the mask before the user types them  |
| `tokens`         | `String`  | —       | JSON object of custom tokens (see below)                          |
| `tokens-replace` | `Boolean` | `false` | When `true`, replaces Maska defaults instead of merging with them |

> Looking for currency formatting (locale-aware grouping, prefix/suffix, decimals)? Use the dedicated
> [`money-input`](./money-input.md) controller instead.

## Mask tokens

The controller uses [Maska's default tokens](https://beholdr.github.io/maska/v3/#/tokens):

| Token | Pattern       | Notes        |
|-------|---------------|--------------|
| `#`   | `[0-9]`       | Digit        |
| `@`   | `[a-zA-Z]`    | Letter       |
| `*`   | `[a-zA-Z0-9]` | Alphanumeric |

Escape any token by prefixing with `!` (e.g. `!#` renders a literal `#`).

### Custom tokens

Pass a JSON object via `data-input-mask-tokens-value` to register your own token definitions. By default they merge
with Maska's built-ins; set `data-input-mask-tokens-replace-value="true"` to replace them entirely.

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="LL-##"
    data-input-mask-tokens-value='{"L":{"pattern":"[A-Z]"}}'
    placeholder="AB-12"
/>
```

Token modifiers (`optional`, `multiple`, `repeated`) can be set in the JSON object — see the Maska
[tokens docs](https://beholdr.github.io/maska/v3/#/tokens) for details. The `transform` callback is not supported
via data attributes (functions cannot be serialized); if you need it, write a small Stimulus controller of your own
that wires Maska directly.

## Basic usage — phone

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="(##) #####-####"
    placeholder="(21) 99999-9999"
/>
```

## CPF

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="###.###.###-##"
    placeholder="000.000.000-00"
/>
```

## CNPJ

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="##.###.###/####-##"
    placeholder="00.000.000/0000-00"
/>
```

## ZIP code

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="#####-###"
    placeholder="00000-000"
/>
```

## Dynamic mask — CPF or CNPJ

Use a JSON array to switch between masks based on the number of characters typed:

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value='["###.###.###-##", "##.###.###/####-##"]'
    placeholder="CPF or CNPJ"
/>
```

## Reverse mask

```html
<input
    type="text"
    data-controller="input-mask"
    data-input-mask-mask-value="###.###.###"
    data-input-mask-reversed-value="true"
    placeholder="Value"
/>
```
