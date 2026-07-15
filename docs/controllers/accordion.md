# Accordion Controller

Coordinates native `<details>` / `<summary>` accordion items without replacing browser disclosure behavior.

**Identifier:** `accordion`
**Install:** `php artisan hotwire:controllers accordion`

## Markup

```html
<section data-controller="accordion" data-accordion-type-value="single">
    <details data-accordion-target="item" data-value="shipping" open>
        <summary>Shipping</summary>
        <section>Shipping answers.</section>
    </details>
    <details data-accordion-target="item" data-value="billing">
        <summary>Billing</summary>
        <section>Billing answers.</section>
    </details>
</section>
```

## Values

| Value | Type | Default | Description |
| --- | --- | --- | --- |
| `type` | `String` | `single` | Use `multiple` to allow more than one item open. |
| `value` | `String` | `""` | Initial open value. Pass a JSON array for multiple values. |

## Behavior

- `single` closes sibling items when a new item opens.
- `multiple` leaves sibling items open.
- Items with `aria-disabled="true"` or `data-disabled="true"` cannot be opened.
- The controller dispatches `accordion:change` after an item toggles.

The event detail is:

```js
{
    value: "shipping",
    open: true,
    item: detailsElement,
}
```

## Accessibility

The controller relies on native `<details>` / `<summary>` semantics. It does not implement roving tabindex or arrow-key
navigation. Native `Tab`, `Enter` and `Space` behavior stays intact.
