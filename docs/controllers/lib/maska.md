# Maska

Applies input masks using [Maska](https://beholdr.github.io/maska/). Supports static, dynamic (array) and reverse masks.

**Identifier:** `lib--maska`

## Requirements

- `maska` (`bun add maska`)

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `mask` | `String` | — | Mask or JSON array of masks (required) |
| `reversed` | `Boolean` | `false` | Applies the mask from right to left |
| `is-money` | `Boolean` | `false` | Reserved for monetary formatting |

## Mask tokens

| Token | Pattern |
|-------|---------|
| `#` | `[0-9]` (Maska default) |
| `9` | `9` (literal) |
| `S` | `[a-zA-ZÀ-ÿ\s]` (letters with accents, repeated) |

See the [Maska documentation](https://beholdr.github.io/maska/) for all default tokens.

## Basic usage — phone

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value="(##) #####-####"
    placeholder="(21) 99999-9999"
/>
```

## CPF

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value="###.###.###-##"
    placeholder="000.000.000-00"
/>
```

## CNPJ

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value="##.###.###/####-##"
    placeholder="00.000.000/0000-00"
/>
```

## ZIP code

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value="#####-###"
    placeholder="00000-000"
/>
```

## Dynamic mask — CPF or CNPJ

Use a JSON array to switch between masks based on the number of characters typed:

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value='["###.###.###-##", "##.###.###/####-##"]'
    placeholder="CPF or CNPJ"
/>
```

## Reverse mask

```html
<input
    type="text"
    data-controller="lib--maska"
    data-lib--maska-mask-value="###.###.###"
    data-lib--maska-reversed-value="true"
    placeholder="Value"
/>
```
