# Money Input

Formats inputs as locale-aware currency or numeric values using [Maska](https://beholdr.github.io/maska/).
Handles digit grouping, decimals and prefix/suffix derived from a currency code.

**Identifier:** `money-input`
**Install:** `php artisan hotwire:controllers money-input`

## Requirements

- `maska` (`bun add maska`)

## Stimulus Values

| Value      | Type      | Default | Description                                                      |
|------------|-----------|---------|------------------------------------------------------------------|
| `locale`   | `String`  | `en-US` | BCP 47 locale used for digit grouping and decimal separator      |
| `currency` | `String`  | —       | ISO 4217 code (e.g. `BRL`, `USD`, `EUR`). Resolves prefix/suffix |
| `prefix`   | `String`  | —       | Manual prefix (overrides `currency`)                             |
| `suffix`   | `String`  | —       | Manual suffix (overrides `currency`)                             |
| `fraction` | `Number`  | `2`     | Number of decimal digits                                         |
| `unsigned` | `Boolean` | `false` | Disallows negative values                                        |
| `eager`    | `Boolean` | `false` | Renders static characters of the mask before the user types them |

When `currency` is set, prefix and suffix are derived automatically via `Intl.NumberFormat`, respecting where the
locale places the symbol. Pass `prefix` or `suffix` explicitly to override.

> Maska's number mode only renders fractional digits the user actually types. To force `100.00` in the field at
> render time, prefill the input value (e.g. `value="{{ number_format($price, 2) }}"`); otherwise typing `100` shows
> `100`, and `100.00` shows `100.00`.

## Plain number (no currency)

```html
<input
    type="text"
    data-controller="money-input"
    placeholder="1,234.56"
/>
```

## Brazilian Real

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="pt-BR"
    data-money-input-currency-value="BRL"
    placeholder="R$ 0,00"
/>
```

## US Dollar

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-currency-value="USD"
    placeholder="$1,234.56"
/>
```

## Euro (Germany)

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="de-DE"
    data-money-input-currency-value="EUR"
    placeholder="1.234,56 €"
/>
```

## Custom prefix/suffix

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="pt-BR"
    data-money-input-prefix-value="R$ "
    data-money-input-fraction-value="2"
/>
```

## No decimals (integer)

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-fraction-value="0"
    placeholder="1,000"
/>
```

## Unsigned (positive only)

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-currency-value="USD"
    data-money-input-unsigned-value="true"
/>
```

## Reading the unmasked value

Maska dispatches a `maska` event after each keystroke with `event.detail = { masked, unmasked, completed }`.
Listen to it whenever you need the raw numeric string (e.g. `"1234.56"`) instead of the formatted display value.

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-currency-value="USD"
    data-action="maska->cart#updateTotal"
/>
```

```js
// app/javascript/controllers/cart_controller.js
updateTotal(event)
{
    const raw = event.detail.unmasked; // e.g. "1234.56"
}
```

## Submitting raw values to the server

The visible input contains a formatted string like `R$ 1.234,56`, which Laravel's `numeric` validator rejects.
Pair the visible field with a hidden companion and sync them via a tiny Stimulus controller:

```html

<form method="POST" action="/products">
    <div data-controller="money-form-field">
        <input
            type="text"
            data-controller="money-input"
            data-money-input-locale-value="pt-BR"
            data-money-input-currency-value="BRL"
            data-action="maska->money-form-field#sync"
            value="{{ number_format($product->price, 2, ',', '.') }}"
        />
        <input
            type="hidden"
            name="price"
            data-money-form-field-target="raw"
            value="{{ $product->price }}"
        />
    </div>
</form>
```

```js
// app/javascript/controllers/money_form_field_controller.js
import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["raw"];

    sync(event) {
        this.rawTarget.value = event.detail.unmasked;
    }
}
```

The hidden field receives `1234.56` regardless of the locale-formatted display, so server-side `numeric` validation
just works.
