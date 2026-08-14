# Popover

Anchored popover for rich content that opens from a trigger button. Use it for panels with forms, filters, previews or
short contextual detail. Use [Dropdown](dropdown.md) instead for menus made of actions or navigation items.

The component wraps the [`popover`](../controllers/popover.md) Stimulus controller, renders the trigger/content wiring
and positions the content with Floating UI.

## Basic Usage

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

`popover.trigger` renders the button, links it to `popover.content` with `aria-controls`, and keeps `aria-expanded` and
`data-popover-state="open|closed"` in sync. The content uses `data-state="open|closed"` for Presence styling.

## Positioning

Popover uses `strategy="fixed"` by default and promotes the panel to the browser's native top layer when supported, so it
can cross Drawer, Modal, Turbo Frame and scroll-container boundaries more reliably:

```blade
<hw:popover side="right" align="end" :side-offset="8">
    <hw:popover.trigger>Filters</hw:popover.trigger>

    <hw:popover.content>
        <hw:field.group class="gap-3">
            <hw:field name="status">
                <hw:field.label>Status</hw:field.label>
                <hw:select name="status">
                    <option>Open</option>
                    <option>Closed</option>
                </hw:select>
            </hw:field>
        </hw:field.group>
    </hw:popover.content>
</hw:popover>
```

Both strategies retain top-layer promotion. `fixed` uses viewport-relative coordinates; `absolute` uses page/document
coordinates while in the top layer, not the nearest positioned ancestor. Top-layer promotion does not move the node
elsewhere in the DOM. Browsers without the native Popover API use the normal DOM fallback, where `absolute` uses its
offset parent and transformed, contained, or clipped ancestors can still affect rendering.

The enter motion starts only after Floating UI resolves the first placement. If `flip` changes the preferred placement,
`data-side` and `data-align` expose the resolved placement used on screen. Superseded asynchronous positioning results
are ignored.

## Sizing

The default preset CSS gives the content a `w-72` default with a viewport max-width. Pass classes to `popover.content` for
one-off sizing or layout changes:

```blade
<hw:popover>
    <hw:popover.trigger>Event details</hw:popover.trigger>

    <hw:popover.content class="w-96 max-w-[calc(100vw-2rem)] p-0">
        <!-- custom layout -->
    </hw:popover.content>
</hw:popover>
```

## Automatic Behavior

Popover is intended for arbitrary content, so it does not close when buttons or links inside the panel are clicked.
Close it explicitly where needed:

```blade
<hw:popover>
    <hw:popover.trigger>Preferences</hw:popover.trigger>

    <hw:popover.content>
        <hw:popover.header>
            <hw:popover.title>Preferences</hw:popover.title>
        </hw:popover.header>

        <hw:button type="button" data-action="popover#close">Done</hw:button>
    </hw:popover.content>
</hw:popover>
```

Outside click, `Escape`, and Turbo's `before-cache` event close the popover automatically. `Escape` returns focus to the
trigger.

## Motion And Presence

Closed content is server-rendered with `data-state="closed" hidden inert`, including when `open="true"`; the controller
positions an initially open panel before showing it without enter motion. During exit, `data-state="closed"` and `inert`
apply immediately, while `hidden` is deferred until CSS motion finishes. This keeps the panel non-interactive without
cutting off its exit.

The default preset CSS transitions only `opacity`, `scale`, and `translate`. Set motion on the content component:

```blade
<hw:popover>
    <hw:popover.trigger>Instant popover</hw:popover.trigger>

    <hw:popover.content motion="none">
        <!-- content -->
    </hw:popover.content>
</hw:popover>
```

Custom CSS may use transitions or finite animations keyed by `data-state`. A closed-state selector must never apply
`display: none` or `hidden`; Presence owns the `hidden` attribute. Rapid reopen cancels stale exit cleanup, and
`prefers-reduced-motion: reduce` skips motion automatically.

## Props

| Component | Prop | Default | Description |
|---|---|---|---|
| `popover` | `id` | `uniqid('popover-')` | Content id and trigger `aria-controls`. |
| `popover` | `side` | `bottom` | Preferred side: `top`, `right`, `bottom` or `left`. |
| `popover` | `align` | `start` | Content alignment: `start`, `center` or `end`. |
| `popover` | `side-offset` | `4` | Main-axis gap between the trigger and content. |
| `popover` | `align-offset` | `0` | Cross-axis offset along the trigger edge. |
| `popover` | `strategy` | `fixed` | Floating UI strategy: `fixed` or `absolute`. |
| `popover` | `flip` | `true` | Flip to the opposite side when the preferred side lacks room. |
| `popover` | `shift` | `true` | Shift within the viewport when the content would overflow. |
| `popover` | `open` | `false` | Start open without enter motion. |
| `popover` | `stimulus` | `null` | Optional Stimulus binding from `stimulus()`, merged with the internal controller. |
| `popover.content` | `motion` | `default` | Presence motion: `default` or `none`. |

## Components

| Component             | Element                    | Slot                  |
|-----------------------|----------------------------|-----------------------|
| `popover`             | `div`                      | `popover`             |
| `popover.trigger`     | `button`                   | `popover-trigger`     |
| `popover.content`     | `div` with `role="dialog"` | `popover-content`     |
| `popover.header`      | `div`                      | `popover-header`      |
| `popover.title`       | `h2`                       | `popover-title`       |
| `popover.description` | `p`                        | `popover-description` |

## Styling hooks

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
- `data-popover-state="open|closed"` on the trigger
- `data-state="open|closed"` on the content
- `data-slot="popover-content"`
- `data-motion="default|none"`
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

- Popover is not a strict ARIA menu and does not implement roving tabindex or arrow-key menu navigation.
- Native top-layer promotion depends on browser Popover API support; the fallback can still be clipped by ancestors.
