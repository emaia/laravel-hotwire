# Field Group

Groups related fields with the Nova preset's form spacing and enables responsive `<x-hwc::field>` orientation rules.

## Quick Example

```blade
<x-hwc::field-group>
    <x-hwc::field name="name" label="Name">
        <x-hwc::input />
    </x-hwc::field>

    <x-hwc::field name="email" label="Email">
        <x-hwc::input type="email" />
    </x-hwc::field>
</x-hwc::field-group>
```

## Responsive Fields

Use `orientation="responsive"` on a field to stack on small screens and align horizontally from the `md` breakpoint.

```blade
<x-hwc::field-group>
    <x-hwc::field name="email" label="Email" orientation="responsive">
        <x-hwc::input type="email" />
    </x-hwc::field>
</x-hwc::field-group>
```

The shadcn React component uses container queries for this behavior. Laravel Hotwire intentionally uses viewport
breakpoints by default so `FieldGroup` does not break intrinsic-width surfaces such as `<x-hwc::modal size="auto">`.

## Field Content

Use `<x-hwc::field.content>` when a checkbox, radio, switch, or custom control needs a label/title and description next
to the control.

```blade
<x-hwc::field-group>
    <x-hwc::field name="notifications" orientation="horizontal">
        <x-hwc::input type="checkbox" value="1" />

        <x-hwc::field.content>
            <x-hwc::field.title>Marketing emails</x-hwc::field.title>
            <x-hwc::description>Receive occasional product updates.</x-hwc::description>
        </x-hwc::field.content>
    </x-hwc::field>
</x-hwc::field-group>
```

Use `<x-hwc::field.title>` for non-label headings. For form controls that need a real label association, keep using
`<x-hwc::label>`.

## Fieldset And Legend

Use `<x-hwc::field.set>` and `<x-hwc::field.legend>` for semantic form groups.

```blade
<x-hwc::field.set>
    <x-hwc::field.legend>Address Information</x-hwc::field.legend>
    <x-hwc::description>We need your address to deliver your order.</x-hwc::description>

    <x-hwc::field-group>
        <x-hwc::field name="street" label="Street Address">
            <x-hwc::input />
        </x-hwc::field>
    </x-hwc::field-group>
</x-hwc::field.set>
```

`field.legend` accepts `variant="legend"` (default) or `variant="label"`.

## Separator

Use `<x-hwc::field.separator>` between logical groups. It can render a bare line or centered text.

```blade
<x-hwc::field-group>
    <x-hwc::field>...</x-hwc::field>
    <x-hwc::field.separator>Or</x-hwc::field.separator>
    <x-hwc::field>...</x-hwc::field>
</x-hwc::field-group>
```

## Naming

The package keeps `<x-hwc::label>`, `<x-hwc::description>`, and `<x-hwc::error>` as the canonical Laravel-aware
components. They derive `for`, `id`, validation keys, required markers, and error messages through the existing field
context. The `field.*` additions cover layout primitives that did not already exist.

## Styling Hooks

| Slot | Purpose |
|------|---------|
| `field-group` | Outer group wrapper. |
| `field-set` | Semantic `<fieldset>` wrapper. |
| `field-legend` | Semantic `<legend>` for a field set. |
| `field` | Individual field wrapper. |
| `field-content` | Text/content column next to a control. |
| `field-title` | Non-label field heading. |
| `field-separator` | Separator wrapper. |
| `field-separator-line` | Decorative separator line. |
| `field-separator-content` | Optional centered separator text. |

Override spacing after importing the preset:

```css
[data-slot="field-group"] {
    @apply gap-4;
}
```
