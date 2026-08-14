# Badge

Compact status label for counts, states and inline metadata.

## Usage

```blade
<hw:badge>New</hw:badge>
<hw:badge variant="outline">Beta</hw:badge>
<hw:badge as="a" href="/issues" variant="secondary">12 issues</hw:badge>
```

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `variant` | `default` | `default`, `secondary`, `destructive`, `outline`, `ghost` or `link`. |
| `as` | `span` | Render `span` or `a`. Values are normalized and validated. |

Use `as="a"` and pass link attributes directly to the badge when it should behave as a link. `as` is trimmed,
lowercased, and restricted to `span` or `a`; unsupported values are rejected.

## Styling hooks

- `data-slot="badge"`
- `data-variant="default|secondary|destructive|outline|ghost|link"`
