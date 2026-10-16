# Overlay Return

Marks the region of a parent form that receives a record created in a Modal, Sheet or Drawer opened from that form,
such as a "New customer" child inside a sale. The parent keeps its draft, its other values and its errors.

## Usage

Wrap the field and name the region. The opening link selects it with `overlay-return`:

```blade
<hw:form action="/sales" method="post">
    <hw:overlay-return name="customer">
        <hw:select name="customer_id" :options="$customers" :selected="old('customer_id')" />
    </hw:overlay-return>

    <hw:button as="a" href="{{ route('customers.create') }}" frame="modal" overlay-return="customer">
        New customer
    </hw:button>
</hw:form>
```

After saving, the child's response returns the region's new contents and closes itself:

```php
$origin = $request->overlayOrigin();

if ($origin === null) {
    return to_route('customers.index');
}

return turbo_stream()
    ->returnFromOverlay($origin, view('sales._customer-field', [
        'customers' => Customer::pluck('name', 'id'),
        'selected' => $customer->id,
    ]))
    ->closeOverlay($origin)
    ->toast('success', 'Customer created');
```

The returned fragment replaces the region's **contents**, so render the complete field, not only its `<option>`
elements. The name is local to the form: two forms on the same page can both use `customer` and each opening updates
its own. The link must belong to a form with exactly one matching region, or the opening is blocked.

## Let the parent render its own field

A reusable customer form does not need to know whether its caller shows a native select or a searchable Multi Select.
A richer consumer passes `src`, a same-origin GET route that renders the field for the saved record, and the scalar
controls of its own form that route needs as `fields`:

```blade
<hw:form action="/sales" method="post">
    <hw:field name="company_id" label="Company">
        <hw:select :options="$companies" />
    </hw:field>

    <hw:overlay-return name="customer" :src="route('sales.fields.customer')" :fields="['company_id']">
        @include('sales._customer-field')
    </hw:overlay-return>

    <hw:button as="a" href="/customers/create" frame="modal" overlay-return="customer">New customer</hw:button>
</hw:form>
```

The child's response adds the saved record's identity alongside the native fallback fragment:

```php
return turbo_stream()
    ->returnFromOverlay($origin, view('sales._customer-field', [
        'customers' => Customer::pluck('name', 'id'),
        'selected' => $customer->id,
    ]), result: $customer->getKey())
    ->closeOverlay($origin)
    ->toast('success', 'Customer created');
```

The fallback must still contain the complete field for native consumers without `src`, not only `<option>` elements.
For a region with `src`, the package ignores the fallback and requests `src` with `result=<id>` and
`context[company_id]=<value>`. Only the declared, enabled scalar controls of the initiating form are sent; the draft is
not serialized. The route returns a **non-empty HTML fragment**, not a Frame or a page layout, and owns field names,
markup, filtering and the selected value. The result id and context are not authorization: authenticate, authorize and
revalidate the selection on the server.

The context is checked before and after the request and again on retry. A replaced form or region, a changed `src` or
`fields`, or changed context values expire the return instead of rediscovering another destination. A `409` response
means the record cannot be selected in that context; the old field remains and the child stays open. Redirects, empty
or page responses, non-HTML responses and failed requests are not applied.

Creating and returning are separate steps. A following `closeOverlay()` closes the child only after the field is
updated. A temporary failure offers **Retry return**, which repeats only the GET, never the creation POST. While a
return is pending or needs retry, forms in that child cannot submit; unrelated forms stay usable.

Modal, Sheet and Drawer hosts include the retry feedback. A raw HTML host using `src` must include a
`template[data-hotwire-overlay-return-feedback]` with a focusable `[data-hotwire-overlay-return-error]` alert containing
`[data-hotwire-overlay-return-message]` and a `button[data-hotwire-overlay-return-retry]`.

## Props

| Prop     | Type           | Default  | Description                                                                                |
|----------|----------------|----------|--------------------------------------------------------------------------------------------|
| `name`   | `string`       | required | Region name, local to the form, selected by `overlay-return` on the opening link.          |
| `src`    | `?string`      | `null`   | Same-origin GET route that renders the field for the saved record instead of the fallback. |
| `fields` | `list<string>` | `[]`     | Control names of the initiating form sent to `src` as `context[...]`. Requires `src`.      |

Other attributes are rendered on the wrapper `<div>`. Invalid names, cross-origin or fragment `src` URLs and `fields`
without `src` throw `InvalidArgumentException`.

## Styling hooks

- `data-slot="overlay-return"` (structural; presets do not style it)

See [overlay responses](../recipes/overlay-responses.md) for opening, closing and returning from the server.
