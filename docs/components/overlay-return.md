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
its own. The link must belong to a form with exactly one matching region, or the opening is blocked. A
[`<hw:dropdown.item>`](./dropdown.md#opening-an-overlay-from-an-item) inside the form accepts the same `overlay-return`.

## Let the parent render its own field

A reusable child can receive an application-owned presentation hint, such as
`/customers/create?field=rich&company=3`, and return the corresponding complete field after saving. Carry those hints
through the child's form action and validation source. If the company can change before opening, update the link from
the current control value with a small application Stimulus controller; a URL rendered with the page's initial value is
not a snapshot of the current draft. The package does not serialize parent fields or track their values.

Validate the hint against a fixed set of supported field views, authorize the company and saved record, and check that
the selection belongs to that company on the server. Never use a client-provided hint as an arbitrary view name or URL.
This direct-fragment approach needs no second request and works with Multi Select just like a native select.

For example, choose an allowlisted field view **before** creating the customer:

```php
$fieldView = match ($request->query('field', 'native')) {
    'native' => 'sales._customer-field',
    'rich' => 'sales._rich-customer-field',
    default => abort(422),
};
```

After validation and creation, pass that view to `returnFromOverlay($origin, view($fieldView, $fieldData))` and compose
`closeOverlay($origin)` as usual. The rich view can render a Multi Select limited to one selection:

```blade
<hw:field name="customer_id" :id="$fieldId" label="Customer">
    <hw:multi-select :options="$customers" :selected="[$selected]" :max="1" />
</hw:field>
```

Supply a unique `$fieldId` and the authorized customers for the captured company. The parent must accept the Multi
Select's array field (`customer_id[]`) and revalidate that selection against the submitted company.

When the parent must own a separate rendering route, return a native Turbo Frame as the region's new contents:

```blade
{{-- sales/_customer-field-loader.blade.php --}}
<turbo-frame id="{{ $fieldFrame }}"
    src="{{ route('sales.fields.customer', ['selected' => $customer->id, 'company' => $company->id]) }}">
    Loading customer field…
</turbo-frame>
```

Generate a unique Frame id for this returned field, including across simultaneous openings:

```php
return turbo_stream()
    ->returnFromOverlay($origin, view('sales._customer-field-loader', [
        'customer' => $customer,
        'company' => $company,
        'fieldFrame' => 'customer-field-'.Str::uuid(),
    ]))
    ->closeOverlay($origin);
```

The GET route reads the Frame id with `request()->turboFrameId()` from Turbo's header, validates it and returns a
**matching `<turbo-frame>`** containing the complete field. No extra `frame` query parameter is needed. Turbo owns
this loading; `closeOverlay()` does **not** wait for it. The record is already saved if the field fails to load.
Provide application-owned loading/error recovery that reloads only the GET, or let the user reopen a refreshed customer
selector; do not repeat the creation POST. The original region contents have already been replaced by the loader.

Both approaches use context captured by the application at opening. An inactive parent cannot be edited directly,
but Streams or application JavaScript can still change it; reconcile such changes in the application and revalidate
the final sale on submission. The package checks the identity of the captured form and region, not business values.

## Props

| Prop   | Type     | Default  | Description                                                                     |
|--------|----------|----------|---------------------------------------------------------------------------------|
| `name` | `string` | required | Region name, local to the form, selected by `overlay-return` on the opening link. |

Other attributes are rendered on the wrapper `<div>`. Invalid names throw `InvalidArgumentException`.

## Styling hooks

- `data-slot="overlay-return"` (structural; presets do not style it)

See [overlay responses](../recipes/overlay-responses.md) for opening, closing and returning from the server.
