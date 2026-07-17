# Empty State

Composable empty state for zero-results, first-run and unavailable-content screens.

## Usage

```blade
<hw:empty-state>
    <hw:empty-state.header>
        <hw:empty-state.media variant="icon">
            <hw:icon name="info" />
        </hw:empty-state.media>
        <hw:empty-state.title>No results</hw:empty-state.title>
        <hw:empty-state.description>
            Try changing your filters or creating a new record.
        </hw:empty-state.description>
    </hw:empty-state.header>
    <hw:empty-state.content>
        <hw:button>Create record</hw:button>
    </hw:empty-state.content>
</hw:empty-state>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `empty-state.media` | `variant` | `default` | `default` or `icon`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `empty-state` | `div` | `empty-state` |
| `empty-state.header` | `div` | `empty-state-header` |
| `empty-state.media` | `div` | `empty-state-media` |
| `empty-state.title` | `div` | `empty-state-title` |
| `empty-state.description` | `div` | `empty-state-description` |
| `empty-state.content` | `div` | `empty-state-content` |

## Styling Hooks

- `data-slot="empty-state"`
- `data-slot="empty-state-header"`
- `data-slot="empty-state-media"`
- `data-variant="default|icon"`
- `data-slot="empty-state-title"`
- `data-slot="empty-state-description"`
- `data-slot="empty-state-content"`
