# Card

Composable content container with header, action, content and footer slots.

## Usage

```blade
<x-hwc::card>
    <x-hwc::card.header>
        <x-hwc::card.title>Revenue</x-hwc::card.title>
        <x-hwc::card.description>Last 30 days</x-hwc::card.description>
        <x-hwc::card.action>
            <x-hwc::button size="sm" variant="outline">Export</x-hwc::button>
        </x-hwc::card.action>
    </x-hwc::card.header>
    <x-hwc::card.content>
        <p class="text-2xl font-semibold">$12,400</p>
    </x-hwc::card.content>
    <x-hwc::card.footer>Updated now</x-hwc::card.footer>
</x-hwc::card>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `card` | `size` | `default` | `default` or `sm`. |

## Spacing

Override `--card-spacing` on the root card to tune header, content and footer padding for one instance.

```blade
<x-hwc::card class="[--card-spacing:--spacing(8)]">
    <x-hwc::card.header>
        <x-hwc::card.title>Large spacing</x-hwc::card.title>
    </x-hwc::card.header>
    <x-hwc::card.content>
        Content uses the same spacing token.
    </x-hwc::card.content>
</x-hwc::card>
```

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `card` | `div` | `card` |
| `card.header` | `div` | `card-header` |
| `card.title` | `div` | `card-title` |
| `card.description` | `div` | `card-description` |
| `card.action` | `div` | `card-action` |
| `card.content` | `div` | `card-content` |
| `card.footer` | `div` | `card-footer` |

## Styling Hooks

- `data-slot="card"`
- `data-size="default|sm"`
- `data-slot="card-header"`
- `data-slot="card-title"`
- `data-slot="card-description"`
- `data-slot="card-action"`
- `data-slot="card-content"`
- `data-slot="card-footer"`
