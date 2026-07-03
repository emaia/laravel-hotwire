# Icon

Inline SVG icon from the embedded Lucide subset.

## Usage

```blade
<hw:icon name="check" />
<hw:icon name="x" class="w-6 h-6" />
<hw:icon name="search" aria-label="Search" />
```

## Available Icons

| Icon | Name |
|------|------|
| X / Close | `x` |
| Check | `check` |
| Chevron Down | `chevron-down` |
| Chevron Up | `chevron-up` |
| Chevron Left | `chevron-left` |
| Chevron Right | `chevron-right` |
| Search | `search` |
| Circle X | `circle-x` |
| Info | `info` |
| Alert Triangle | `alert-triangle` |
| Alert Circle | `alert-circle` |
| Check Circle | `check-circle` |
| Arrow Up | `arrow-up` |
| Arrow Down | `arrow-down` |
| Arrow Left | `arrow-left` |
| Arrow Right | `arrow-right` |
| Ellipsis | `ellipsis` |
| Copy | `copy` |
| Eye | `eye` |
| Eye Off | `eye-off` |
| Loader Circle | `loader-circle` |

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `name` | `string` | — | Icon identifier (required) |

All standard HTML attributes (`class`, `aria-label`, etc.) are forwarded to the `<svg>` element.
