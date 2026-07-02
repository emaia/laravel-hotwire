# Description

Helper/auxiliary text for a form field. Renders a `<p>` with `data-slot="description"` so presets can style it consistently.

## Quick example

```blade
<x-hwc::description>We will never share your email.</x-hwc::description>
```

Renders:

```html
<p data-slot="description">We will never share your email.</p>
```

## Props

| Prop    | Type     | Default | Description                                       |
|---------|----------|---------|---------------------------------------------------|
| `class` | `string` | `""`    | Merged on the `<p>`.                              |

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
