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

## Styling Hooks

| Slot | Purpose |
|------|---------|
| `field-group` | Outer group wrapper. |
| `field` | Individual field wrapper. |

Override spacing after importing the preset:

```css
[data-slot="field-group"] {
    @apply gap-4;
}
```
