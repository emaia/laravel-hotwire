# Description

Helper/auxiliary text for a form field. Renders a `<p>` with the `hwc-description` hook so the user can style it freely.

## Quick example

```blade
<x-hwc::description>We will never share your email.</x-hwc::description>
```

Renders:

```html
<p class="hwc-description">We will never share your email.</p>
```

## Props

| Prop    | Type     | Default | Description                                       |
|---------|----------|---------|---------------------------------------------------|
| `class` | `string` | `""`    | Merged on the `<p>` alongside `hwc-description`.  |

Any other HTML attribute (`id`, `data-*`, `aria-*`) passes through.

## Use inside `<x-hwc::field>`

`<x-hwc::field>` auto-renders this component when its `description` prop is set:

```blade
<x-hwc::field name="email" label="E-mail" description="We will never share your email.">
    <x-hwc::input type="email" />
</x-hwc::field>
```

The description sits between the slot and the auto-rendered error.

For custom layouts, compose it manually:

```blade
<x-hwc::field name="email" label="E-mail">
    <x-hwc::input type="email" />
    <x-hwc::description class="text-xs text-gray-500">
        We will never share your email.
    </x-hwc::description>
</x-hwc::field>
```
