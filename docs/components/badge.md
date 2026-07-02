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

Blade does not have a React Slot equivalent for shadcn's `asChild`; use `as="a"` and pass attributes directly to the badge instead.

## Styling Hooks

- `data-slot="badge"`
- `data-variant="default|secondary|destructive|outline|ghost|link"`
