# Money Input

Formats inputs as locale-aware monetary values with classic right-aligned digit entry. Use it for prices, amounts and currency fields where typing `1`, `2`, `3` should behave like `0,01`, `0,12`, `1,23`.

**Identifier:** `money-input`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers money-input`.

## Requirements

- No external dependencies.
- Ships with `_composition.js`; publishing the controller publishes this helper too.

## Basic Usage

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
    placeholder="$0.00"
/>
```

## Euro (Germany)

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="de-DE"
    data-money-input-currency-value="EUR"
    placeholder="0,00 €"
/>
```

## Custom Prefix Or Suffix

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="pt-BR"
    data-money-input-prefix-value="R$ "
    data-money-input-fraction-value="2"
/>
```

When `currency` is set, prefix and suffix are derived automatically via `Intl.NumberFormat`, respecting where the locale places the symbol. Pass `prefix` or `suffix` explicitly to override.

## No Decimals

With `fraction="0"`, the controller formats whole numbers only:

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-fraction-value="0"
    placeholder="1,000"
/>
```

## Unsigned Values

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-currency-value="USD"
    data-money-input-unsigned-value="true"
/>
```

## Four Fractional Digits

When `fraction > 0`, the controller behaves like a classic money input. Digits are appended from the right and the caret stays at the end of the field.

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="pt-BR"
    data-money-input-currency-value="BRL"
    data-money-input-fraction-value="4"
    placeholder="R$ 0,0000"
/>
```

Typing `99999` yields `R$ 9,9999`, and `1999` yields `R$ 0,1999`.

## Initial Value

The `value` attribute is interpreted as minor units: an integer digit string with an optional leading `-`. This matches the format the controller writes back to the hidden field on submit, so the round-trip server to form to server uses a single canonical representation.

```html
{{-- $product->price stored as cents (integer): 156795 --}}
<input
    type="text"
    data-controller="money-input"
    data-money-input-locale-value="de-DE"
    data-money-input-currency-value="EUR"
    value="{{ $product->price }}"
/>
{{-- Renders: 1.567,95 € --}}
```

If your model stores a decimal (`1567.95`), convert when rendering:

```blade
value="{{ (int) round($product->price * 100) }}"
```

## Submitting Raw Values To The Server

The visible input contains a formatted string like `R$ 1.234,56`, which Laravel's `numeric` validator rejects. Point `hiddenId` at a sibling hidden input. The controller mirrors the minor-unit value into it on every change:

```html
<form method="POST" action="/products">
    <input type="hidden" name="price" id="product-price-raw" value="{{ $product->price }}">

    <input
        type="text"
        data-controller="money-input"
        data-money-input-locale-value="pt-BR"
        data-money-input-currency-value="BRL"
        data-money-input-hidden-id-value="product-price-raw"
        value="{{ $product->price }}"
    />
</form>
```

The hidden field always carries the canonical minor-unit string, such as `123456` for `R$ 1.234,56`, regardless of locale. On the server side, cast it to integer and convert to your storage format:

```php
// price column stored in cents (integer)
$product->price = (int) $request->input('price');

// price column stored as DECIMAL(10, 2)
$product->price = $request->integer('price') / 100;

// using moneyphp/money or cknow/laravel-money
$product->price = Money::BRL($request->input('price'));
```

## Reading The Normalized Value

The controller dispatches a `money-input:change` event after each change with:

```js
event.detail = {
    masked,    // visible value, e.g. "R$ 1.234,56"
    unmasked,  // canonical minor-unit value, e.g. "123456"
    completed, // true when the field is not empty
}
```

Use it whenever you need the canonical minor-unit string instead of the formatted display value:

```html
<input
    type="text"
    data-controller="money-input"
    data-money-input-currency-value="USD"
    data-action="money-input:change->cart#updateTotal"
/>
```

```js
updateTotal(event) {
    const raw = event.detail.unmasked; // e.g. "123456"
}
```

## Behavior

Money Input leaves intermediate IME input untouched and formats only the final committed value.

When `fraction > 0`, digits are appended from the right and the caret stays at the end of the field:

- `1` -> `0,01`
- `12` -> `0,12`
- `123` -> `1,23`

If the entire input value is selected, the next typed digit replaces it.

Initial rendering, `turbo:render` reconciliation, and rebuilds caused by runtime Stimulus Value changes are silent. For user edits, Money Input consumes the original native `InputEvent` during capture, updates the visible and hidden values, then emits one bubbling `CustomEvent("input")` with the formatted value in `event.detail`. This guarantees downstream controllers such as Auto Submit observe the normalized value exactly once.

Because the replacement event is synthetic, it does not preserve native `InputEvent` metadata such as `inputType`, `data`, or `isTrusted`. Downstream integrations that need monetary state should read `event.target.value`, the hidden minor-unit input, or the richer `money-input:change` event instead.

On every `turbo:render`, the controller silently re-seeds its internal digit buffer from the current element value and re-renders. Under morph, idiomorph rewrites the input value to the newly flashed minor-unit amount without firing an `input` event, so subsequent typing would otherwise operate on a stale buffer.

## Values

| Value      | Type      | Default | Description                                                          |
|------------|-----------|---------|----------------------------------------------------------------------|
| `locale`   | `String`  | `en-US` | BCP 47 locale used for grouping and decimal separator                |
| `currency` | `String`  | —       | ISO 4217 code (e.g. `BRL`, `USD`, `EUR`). Resolves prefix/suffix     |
| `prefix`   | `String`  | —       | Manual prefix (overrides `currency`)                                 |
| `suffix`   | `String`  | —       | Manual suffix (overrides `currency`)                                 |
| `fraction` | `Number`  | `2`     | Number of fractional digits                                          |
| `unsigned` | `Boolean` | `false` | Disallows negative values                                            |
| `hiddenId` | `String`  | —       | `id` of a hidden `<input>` to keep in sync with the minor-unit value |

## Events

| Event                | Description                                                                 |
|----------------------|-----------------------------------------------------------------------------|
| `money-input:change` | Fired after each change with `masked`, `unmasked` and `completed` details. |
| `input`              | Synthetic bubbling event emitted once for each normalized user edit.        |
