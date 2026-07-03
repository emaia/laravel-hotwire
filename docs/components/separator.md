# Separator

Horizontal or vertical rule for separating content sections.

## Usage

```blade
<hw:separator />

<div class="flex h-6 gap-4">
    <span>Left</span>
    <hw:separator orientation="vertical" />
    <span>Right</span>
</div>
```

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `orientation` | `horizontal` | `horizontal` or `vertical`. Vertical separators also render `aria-orientation="vertical"`. |

## Styling Hooks

- `data-slot="separator"`
- `data-orientation="horizontal|vertical"`
