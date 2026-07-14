# Marker

Lightweight visual primitive for timelines, activity feeds and inline list markers.

## Usage

```blade
<hw:marker>
    <hw:marker.icon>
        <hw:icon name="git-branch" />
    </hw:marker.icon>
    <hw:marker.content>Switched to a new branch</hw:marker.content>
</hw:marker>
```

## Variants

Use `separator` for section markers and `border` for row boundaries.

```blade
<hw:marker variant="separator">
    <hw:marker.content>Conversation compacted</hw:marker.content>
</hw:marker>

<hw:marker variant="border">
    <hw:marker.content>Explored 4 files</hw:marker.content>
</hw:marker>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `marker` | `variant` | `default` | `default`, `separator` or `border`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `marker` | `div` | `marker` |
| `marker.icon` | `span` | `marker-icon` |
| `marker.content` | `span` | `marker-content` |

## Styling Hooks

- `data-slot="marker"`
- `data-variant="default|separator|border"`
- `data-slot="marker-icon"`
- `data-slot="marker-content"`
