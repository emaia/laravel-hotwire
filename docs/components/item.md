# Item

Composable list item primitive for menus, notifications, search results and settings rows.

## Usage

```blade
<hw:item.group>
    <hw:item variant="outline">
        <hw:item.media variant="icon">
            <hw:icon name="bell" />
        </hw:item.media>
        <hw:item.content>
            <hw:item.title>Notifications</hw:item.title>
            <hw:item.description>Manage delivery preferences.</hw:item.description>
        </hw:item.content>
        <hw:item.actions>
            <hw:badge variant="secondary">New</hw:badge>
        </hw:item.actions>
    </hw:item>

    <hw:item.separator />

    <hw:item as="a" href="/settings/profile" size="sm">
        Profile settings
    </hw:item>
</hw:item.group>
```

Use `as="a"`, `as="button"` or another tag and pass attributes directly to the item when it needs a different root element.

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `item` | `variant` | `default` | `default`, `outline` or `muted`. |
| `item` | `size` | `default` | `default`, `sm` or `xs`. |
| `item` | `as` | `div` | Render a different root element, usually `a` or `button`. |
| `item.media` | `variant` | `default` | `default`, `icon` or `image`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `item.group` | `div` with `role="list"` | `item-group` |
| `item` | configurable, defaults to `div` | `item` |
| `item.media` | `div` | `item-media` |
| `item.content` | `div` | `item-content` |
| `item.title` | `div` | `item-title` |
| `item.description` | `p` | `item-description` |
| `item.actions` | `div` | `item-actions` |
| `item.header` | `div` | `item-header` |
| `item.footer` | `div` | `item-footer` |
| `item.separator` | `div` | `item-separator` |

## Styling Hooks

- `data-slot="item-group"`
- `data-slot="item"`
- `data-variant="default|outline|muted"`
- `data-size="default|sm|xs"`
- `data-slot="item-media"`
- `data-variant="default|icon|image"`
- `data-slot="item-content"`
- `data-slot="item-title"`
- `data-slot="item-description"`
- `data-slot="item-actions"`
- `data-slot="item-header"`
- `data-slot="item-footer"`
- `data-slot="item-separator"`
