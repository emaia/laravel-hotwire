# Item

Composable list item primitive for menus, notifications, search results and settings rows.

## Usage

```blade
<hw:item.group>
    <hw:item variant="outline">
        <hw:item.media variant="icon">
            <x-lucide-bell class="size-4" />
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

Use `as="a"` or `as="button"` and pass attributes directly to the item when it needs an interactive root. `as` is
trimmed, lowercased, and restricted to `div`, `a`, or `button`; unsupported values are rejected. Button items default to
`type="button"`, with `submit` and `reset` also accepted. Disabled anchors omit `href` and receive
`aria-disabled="true"` and `tabindex="-1"`.

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `item` | `variant` | `default` | `default`, `outline` or `muted`. |
| `item` | `size` | `default` | `default`, `sm` or `xs`. |
| `item` | `as` | `div` | Render `div`, `a`, or `button`. Values are normalized and validated. |
| `item` | `type` | `button` | Native button type: `button`, `submit`, or `reset`. |
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

## Styling hooks

- `data-slot="item-group"`
- `data-slot="item"`
- `data-variant="default|outline|muted"`
- `data-size="default|sm|xs"`
- `data-slot="item-media"`
- `data-variant="default|icon|image"`
- `data-slot="icon"` for a direct child of icon-variant media, including application-provided icons
- `data-slot="item-content"`
- `data-slot="item-title"`
- `data-slot="item-description"`
- `data-slot="item-actions"`
- `data-slot="item-header"`
- `data-slot="item-footer"`
- `data-slot="item-separator"`
