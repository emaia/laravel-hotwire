# Empty

Composable empty state for zero-results, first-run and unavailable-content screens.

## Usage

```blade
<x-hwc::empty>
    <x-hwc::empty.header>
        <x-hwc::empty.media variant="icon">
            <x-hwc::icon name="search" />
        </x-hwc::empty.media>
        <x-hwc::empty.title>No results</x-hwc::empty.title>
        <x-hwc::empty.description>
            Try changing your filters or creating a new record.
        </x-hwc::empty.description>
    </x-hwc::empty.header>
    <x-hwc::empty.content>
        <x-hwc::button>Create record</x-hwc::button>
    </x-hwc::empty.content>
</x-hwc::empty>
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
