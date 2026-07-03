# Empty

Composable empty state for zero-results, first-run and unavailable-content screens.

## Usage

```blade
<hw:empty>
    <hw:empty.header>
        <hw:empty.media variant="icon">
            <hw:icon name="search" />
        </hw:empty.media>
        <hw:empty.title>No results</hw:empty.title>
        <hw:empty.description>
            Try changing your filters or creating a new record.
        </hw:empty.description>
    </hw:empty.header>
    <hw:empty.content>
        <hw:button>Create record</hw:button>
    </hw:empty.content>
</hw:empty>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `empty.media` | `variant` | `default` | `default` or `icon`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `empty` | `div` | `empty` |
| `empty.header` | `div` | `empty-header` |
| `empty.media` | `div` | `empty-icon` |
| `empty.title` | `div` | `empty-title` |
| `empty.description` | `div` | `empty-description` |
| `empty.content` | `div` | `empty-content` |

## Styling Hooks

- `data-slot="empty"`
- `data-slot="empty-header"`
- `data-slot="empty-icon"`
- `data-variant="default|icon"`
- `data-slot="empty-title"`
- `data-slot="empty-description"`
- `data-slot="empty-content"`
