# Card

Composable content container with header, action, content and footer slots.

## Usage

```blade
<hw:card>
    <hw:card.header>
        <hw:card.title>Revenue</hw:card.title>
        <hw:card.description>Last 30 days</hw:card.description>
        <hw:card.action>
            <hw:button size="sm" variant="outline">Export</hw:button>
        </hw:card.action>
    </hw:card.header>
    <hw:card.content>
        <p class="text-2xl font-semibold">$12,400</p>
    </hw:card.content>
    <hw:card.footer>Updated now</hw:card.footer>
</hw:card>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `card` | `size` | `default` | `default` or `sm`. |

## Spacing

Override `--card-spacing` on the root card to tune header, content and footer padding for one instance.

```blade
<hw:card class="[--card-spacing:--spacing(8)]">
    <hw:card.header>
        <hw:card.title>Large spacing</hw:card.title>
    </hw:card.header>
    <hw:card.content>
        Content uses the same spacing token.
    </hw:card.content>
</hw:card>
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
