# Badge

Compact status label for counts, states and inline metadata.

## Usage

```blade
<x-hwc::badge>New</x-hwc::badge>
<x-hwc::badge variant="outline">Beta</x-hwc::badge>
<x-hwc::badge as="a" href="/issues" variant="secondary">12 issues</x-hwc::badge>
```

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `variant` | `default` | `default`, `secondary`, `destructive`, `outline`, `ghost` or `link`. |
| `as` | `span` | Render a different element, usually `a` for link badges. |

Use `as="a"` and pass link attributes directly to the badge when it should behave as a link.

## Styling Hooks

- `data-slot="badge"`
- `data-variant="default|secondary|destructive|outline|ghost|link"`
