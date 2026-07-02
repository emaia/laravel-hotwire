# Button Group

Groups related buttons and button-like controls so they read as one action cluster.

## Usage

```blade
<x-hwc::button-group aria-label="Pagination">
    <x-hwc::button variant="outline">Previous</x-hwc::button>
    <x-hwc::button variant="outline">Next</x-hwc::button>
</x-hwc::button-group>

<x-hwc::button-group orientation="vertical">
    <x-hwc::button-group.text>Sort</x-hwc::button-group.text>
    <x-hwc::button-group.separator />
    <x-hwc::button variant="outline">Newest</x-hwc::button>
</x-hwc::button-group>
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
