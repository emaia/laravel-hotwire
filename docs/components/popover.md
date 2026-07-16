# Popover

Anchored popover for rich content that opens from a trigger button. Use it for panels with forms, filters, previews or
short contextual detail. Use [Dropdown](dropdown.md) instead for menus made of actions or navigation items.

The component wraps the [`popover`](../controllers/popover.md) Stimulus controller, renders the trigger/content wiring
and positions the content with Floating UI.

## Usage

```blade
<hw:popover>
    <hw:popover.trigger>Edit profile</hw:popover.trigger>

    <hw:popover.content>
        <hw:popover.header>
            <hw:popover.title>Profile</hw:popover.title>
            <hw:popover.description>Update public profile details.</hw:popover.description>
        </hw:popover.header>

        <hw:field.group class="mt-4 gap-3">
            <hw:field name="name">
                <hw:field.label>Name</hw:field.label>
                <hw:input name="name" value="Jane Doe" />
            </hw:field>

            <hw:button type="submit" size="sm">Save changes</hw:button>
        </hw:field.group>
    </hw:popover.content>
</hw:popover>
```

`popover.trigger` renders the button, links it to `popover.content` with `aria-controls`, and keeps `aria-expanded` in
sync.

## Positioning

Popover uses `strategy="fixed"` by default so the panel can cross Drawer, Modal, Turbo Frame and scroll-container
boundaries more reliably:

```blade
<hw:popover side="right" align="end" :side-offset="8">
    <hw:popover.trigger>Filters</hw:popover.trigger>
    <hw:popover.content><!-- content --></hw:popover.content>
</hw:popover>
```

Use `strategy="absolute"` only when you explicitly want the panel positioned within the nearest positioned ancestor.
This is not a portal; transformed or contained ancestors can still affect the positioning context.

## Sizing

The Nova preset gives the content a `w-72` default with a viewport max-width. Pass classes to `popover.content` for
one-off sizing or layout changes:

```blade
<hw:popover>
    <hw:popover.trigger>Event details</hw:popover.trigger>

    <hw:popover.content class="w-96 max-w-[calc(100vw-2rem)] p-0">
        <!-- custom layout -->
    </hw:popover.content>
</hw:popover>
```

## Keeping It Open

Popover is intended for arbitrary content, so it does not close when buttons or links inside the panel are clicked.
Close it explicitly where needed:

```blade
<hw:button type="button" data-action="popover#close">Done</hw:button>
```

Outside click, `Escape`, and Turbo's `before-cache` event close the popover automatically. `Escape` returns focus to the
trigger.

## Props

| Prop           | Default              | Description                                                                       |
|----------------|----------------------|-----------------------------------------------------------------------------------|
| `id`           | `uniqid('popover-')` | Content id and trigger `aria-controls`.                                           |
| `side`         | `bottom`             | Preferred side: `top`, `right`, `bottom` or `left`.                               |
| `align`        | `start`              | Content alignment: `start`, `center` or `end`.                                    |
| `side-offset`  | `4`                  | Main-axis gap between the trigger and content.                                    |
| `align-offset` | `0`                  | Cross-axis offset along the trigger edge.                                         |
| `strategy`     | `fixed`              | Floating UI strategy: `fixed` or `absolute`.                                      |
| `flip`         | `true`               | Flip to the opposite side when the preferred side lacks room.                     |
| `shift`        | `true`               | Shift within the viewport when the content would overflow.                        |
| `open`         | `false`              | Start open without an enter animation.                                            |
| `transition`   | `true`               | Include the default enter/leave transition attributes.                            |
| `stimulus`     | `null`               | Optional Stimulus binding from `stimulus()`, merged with the internal controller. |

## Components

| Component             | Element                    | Slot                  |
|-----------------------|----------------------------|-----------------------|
| `popover`             | `div`                      | `popover`             |
| `popover.trigger`     | `button`                   | `popover-trigger`     |
| `popover.content`     | `div` with `role="dialog"` | `popover-content`     |
| `popover.header`      | `div`                      | `popover-header`      |
| `popover.title`       | `h2`                       | `popover-title`       |
| `popover.description` | `p`                        | `popover-description` |

## Styling Hooks

- `data-slot="popover"`
- `data-popover-side-value="top|right|bottom|left"`
- `data-popover-align-value="start|center|end"`
- `data-popover-side-offset-value`
- `data-popover-align-offset-value`
- `data-popover-strategy-value="fixed|absolute"`
- `data-popover-flip-value="true|false"`
- `data-popover-shift-value="true|false"`
- `data-slot="popover-trigger"`
- `aria-expanded="true|false"`
- `data-slot="popover-content"`
- `data-open="true|false"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`
- `data-slot="popover-header"`
- `data-slot="popover-title"`
- `data-slot="popover-description"`

## Limitations

- No portal or top-layer support in this release.
- Popover is not a strict ARIA menu and does not implement roving tabindex or arrow-key menu navigation.
- Popover is not portalled or moved to the top layer; transformed or contained ancestors can still affect positioning.
