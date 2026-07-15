# Accordion

Composable Blade primitives for native `<details>` / `<summary>` accordions. The controller only coordinates single-item,
multiple-item and disabled behavior; native browser disclosure semantics remain the source of truth.

## Usage

```blade
<hw:accordion id="faq" type="single" value="shipping">
    <hw:accordion.item value="shipping">
        <hw:accordion.trigger>What are your shipping options?</hw:accordion.trigger>
        <hw:accordion.content>
            We offer standard, express and overnight shipping.
        </hw:accordion.content>
    </hw:accordion.item>

    <hw:accordion.item value="returns">
        <hw:accordion.trigger>What is your return policy?</hw:accordion.trigger>
        <hw:accordion.content>
            You can return items within 30 days of delivery.
        </hw:accordion.content>
    </hw:accordion.item>
</hw:accordion>
```

## Components

| Component | Element | Description |
| --- | --- | --- |
| `accordion` | `section` | Root element with `data-controller="accordion"`. |
| `accordion.item` | `details` | Native disclosure item. |
| `accordion.trigger` | `summary` | Native disclosure trigger with an optional chevron icon. |
| `accordion.content` | `section` | Panel content revealed by the parent `details`. |

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `accordion` | `id` | generated | Root id. |
| `accordion` | `type` | `single` | Use `multiple` to allow more than one item open. |
| `accordion` | `value` | `null` | Open value, or array of values when `type="multiple"`. |
| `accordion` | `controller` | `accordion` | Stimulus identifier, useful for subclasses. |
| `accordion` | `stimulus` | `null` | Optional extra Stimulus binding merged into the root. |
| `accordion.item` | `value` | required | Stable item value. |
| `accordion.item` | `disabled` | `false` | Prevents the item from being toggled open. |
| `accordion.item` | `open` | derived | Explicitly controls the native `open` attribute. |
| `accordion.trigger` | `icon` | `true` | Renders the trailing chevron icon. |

## Multiple Items

```blade
<hw:accordion type="multiple" :value="['shipping', 'billing']">
    ...
</hw:accordion>
```

## Disabled Items

```blade
<hw:accordion>
    <hw:accordion.item value="enterprise" disabled>
        <hw:accordion.trigger>Enterprise-only settings</hw:accordion.trigger>
        <hw:accordion.content>This item cannot be opened.</hw:accordion.content>
    </hw:accordion.item>
</hw:accordion>
```

Disabled items render `aria-disabled="true"` and the controller prevents native click/keyboard toggles.

## Accessibility

Accordion uses native disclosure elements instead of a custom ARIA recreation. `Tab`, `Enter` and `Space` behavior comes
from the browser. The MVP intentionally does not add roving tabindex or arrow-key navigation.

Listen for `accordion:change` when app code needs to react to state changes:

```blade
<hw:accordion
    :stimulus="stimulus()->action('analytics', 'track', 'accordion:change')"
>
    ...
</hw:accordion>
```

The event detail contains `value`, `open` and `item`.

## Required Controllers

`hotwire:check` looks for `accordion` when you use `<hw:accordion>`.
