# Button Group

Groups related buttons and button-like controls so they read as one action cluster.

## Usage

```blade
<hw:button-group aria-label="Pagination">
    <hw:button variant="outline">Previous</hw:button>
    <hw:button variant="outline">Next</hw:button>
</hw:button-group>

<hw:button-group orientation="vertical">
    <hw:button-group.text>Sort</hw:button-group.text>
    <hw:button-group.separator />
    <hw:button variant="outline">Newest</hw:button>
</hw:button-group>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `button-group` | `orientation` | `horizontal` | `horizontal` or `vertical`. |
| `button-group.text` | `as` | `div` | Render a different text wrapper element. |
| `button-group.separator` | `orientation` | `vertical` | `horizontal` or `vertical`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `button-group` | `div` with `role="group"` | `button-group` |
| `button-group.text` | configurable, defaults to `div` | `button-group-text` |
| `button-group.separator` | `div` with `role="separator"` | `button-group-separator` |

## Styling Hooks

- `data-slot="button-group"`
- `data-orientation="horizontal|vertical"`
- `data-slot="button-group-text"`
- `data-slot="button-group-separator"`
