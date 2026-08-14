# Accordion

Use `<hw:accordion>` with its item, trigger, and content components. The components provide native disclosure semantics,
single- or multiple-item coordination, disabled items, and the package's shipped accordion styling and motion.

## Build a single-open FAQ

`single` is the default type. Pass a stable value to each item and, optionally, the value that should start open.

```blade
<hw:accordion id="faq" value="shipping">
    @foreach ($faqs as $faq)
        <hw:accordion.item :value="$faq->slug">
            <hw:accordion.trigger>{{ $faq->question }}</hw:accordion.trigger>
            <hw:accordion.content>
                {{ $faq->answer }}
            </hw:accordion.content>
        </hw:accordion.item>
    @endforeach
</hw:accordion>
```

Opening an item closes its open sibling. The trigger includes the package chevron by default; use `:icon="false"` when
your design supplies a different indicator.

## Allow several items to stay open

Use `type="multiple"` and pass an array when more than one item should start open:

```blade
<hw:accordion type="multiple" :value="['shipping', 'returns']">
    <hw:accordion.item value="shipping">
        <hw:accordion.trigger>What are the shipping options?</hw:accordion.trigger>
        <hw:accordion.content>Standard, express, and overnight shipping are available.</hw:accordion.content>
    </hw:accordion.item>

    <hw:accordion.item value="returns">
        <hw:accordion.trigger>What is the return policy?</hw:accordion.trigger>
        <hw:accordion.content>Items can be returned within 30 days of delivery.</hw:accordion.content>
    </hw:accordion.item>
</hw:accordion>
```

This is useful for settings and reference content where readers compare several sections.

## Open a section from the URL

Make a section linkable by reading its value from the request and passing it to the accordion:

```php
public function show(Request $request)
{
    return view('settings.show', [
        'openSection' => $request->query('section', 'general'),
    ]);
}
```

```blade
<hw:accordion :value="$openSection">
    <hw:accordion.item value="general">
        <hw:accordion.trigger>General</hw:accordion.trigger>
        <hw:accordion.content>...</hw:accordion.content>
    </hw:accordion.item>

    <hw:accordion.item value="billing">
        <hw:accordion.trigger>Billing</hw:accordion.trigger>
        <hw:accordion.content>...</hw:accordion.content>
    </hw:accordion.item>
</hw:accordion>
```

Links such as `/settings?section=billing` now survive refreshes and Turbo Drive visits.

## Disable an item

```blade
<hw:accordion>
    <hw:accordion.item value="enterprise" disabled>
        <hw:accordion.trigger>Enterprise settings</hw:accordion.trigger>
        <hw:accordion.content>Contact your administrator to enable this section.</hw:accordion.content>
    </hw:accordion.item>
</hw:accordion>
```

Disabled items render `aria-disabled="true"` and cannot be opened with pointer or keyboard input.

## React to changes

When application code needs analytics or another side effect, listen for `accordion:change`:

```blade
<hw:accordion :stimulus="stimulus()->action('analytics', 'track', 'accordion:change')">...</hw:accordion>
```

The event detail contains `value`, `open`, and the native `details` element as `item`.

## How it works

The components render native `<details>` and `<summary>` elements, so the browser provides disclosure semantics and
`Tab`, `Enter`, and `Space` behavior. The `accordion` controller only coordinates single, multiple, and disabled state.
The package stylesheet already provides the structural collapse transition and reduced-motion handling; application
styles should use the component's `data-slot` hooks rather than recreating those mechanics.

## See also

- [`<hw:accordion>` component](../components/accordion.md) - complete props and styling hooks.
- [Accordion controller](../controllers/accordion.md) - lower-level event and value details.
