# Hover Card

Anchored preview card that opens from hover or focus. Use it for lightweight contextual detail such as profile previews,
record summaries or metadata hints. Use [Popover](popover.md) instead when the panel needs click-triggered interaction or
long-lived form content.

The component wraps the [`hover-card`](../controllers/hover-card.md) Stimulus controller, renders the trigger/content
wiring and positions the content with Floating UI.

## Usage

```blade
<hw:hover-card>
    <hw:hover-card.trigger>Hover Here</hw:hover-card.trigger>

    <hw:hover-card.content class="flex w-64 flex-col gap-0.5">
        <div class="font-semibold">@nextjs</div>
        <div>
            The React Framework - created and maintained by @vercel.
        </div>
        <div class="mt-1 text-xs text-muted-foreground">
            Joined December 2021
        </div>
    </hw:hover-card.content>
</hw:hover-card>
```

`hover-card.trigger` renders a `button` with `variant="link"` by default, links it to `hover-card.content` with
`aria-describedby`, and keeps `aria-expanded` in sync. Pass `as="a"`, `href`, `variant` or `size` when the trigger
should use another semantic element or button style:

```blade
<hw:hover-card.trigger as="a" href="/users/1" variant="link">
    Jane Doe
</hw:hover-card.trigger>
```

## Delays

Hover Card uses a short open delay and close delay by default to avoid flicker while users move across dense UI:

```blade
<hw:hover-card :open-delay="10" :close-delay="100">
    <hw:hover-card.trigger>Order #1042</hw:hover-card.trigger>
    <hw:hover-card.content>Ships tomorrow.</hw:hover-card.content>
</hw:hover-card>
```

Set either delay to `0` when the card should respond immediately.

## Positioning

Hover Card uses `strategy="fixed"` by default so the preview can cross Drawer, Modal, Turbo Frame and scroll-container
boundaries more reliably:

```blade
<hw:hover-card side="right" align="center" :side-offset="8">
    <hw:hover-card.trigger>Plan</hw:hover-card.trigger>
    <hw:hover-card.content>Enterprise, renewed yearly.</hw:hover-card.content>
</hw:hover-card>
```

Use `strategy="absolute"` only when you explicitly want the panel positioned within the nearest positioned ancestor. This
is not a portal; transformed or contained ancestors can still affect the positioning context.

## Content Guidance

Hover Card opens on hover and focus, and closes on mouse leave, blur, `Escape`, and Turbo's `before-cache` event. Keep the
content short and mostly non-interactive. Links or buttons can work while the pointer or focus remains inside the card,
but a Popover is usually the better fit for deliberate interaction.

## Props

| Prop           | Default                  | Description                                                                       |
|----------------|--------------------------|-----------------------------------------------------------------------------------|
| `id`           | `uniqid('hover-card-')`  | Content id and trigger `aria-describedby`.                                        |
| `side`         | `bottom`                 | Preferred side: `top`, `right`, `bottom` or `left`.                               |
| `align`        | `start`                  | Content alignment: `start`, `center` or `end`.                                    |
| `side-offset`  | `4`                      | Main-axis gap between the trigger and content.                                    |
| `align-offset` | `0`                      | Cross-axis offset along the trigger edge.                                         |
| `strategy`     | `fixed`                  | Floating UI strategy: `fixed` or `absolute`.                                      |
| `flip`         | `true`                   | Flip to the opposite side when the preferred side lacks room.                     |
| `shift`        | `true`                   | Shift within the viewport when the content would overflow.                        |
| `open-delay`   | `10`                     | Delay in milliseconds before opening after hover or focus.                        |
| `close-delay`  | `100`                    | Delay in milliseconds before closing after mouse leave or blur.                   |
| `open`         | `false`                  | Start open without waiting for hover or focus.                                    |
| `transition`   | `true`                   | Include the default enter/leave transition attributes.                            |
| `stimulus`     | `null`                   | Optional Stimulus binding from `stimulus()`, merged with the internal controller. |

## Trigger Props

| Prop      | Default   | Description                                                        |
|-----------|-----------|--------------------------------------------------------------------|
| `as`      | `button`  | Element rendered by the trigger.                                   |
| `variant` | `link`    | Button preset variant: `default`, `outline`, `ghost`, `link`, etc. |
| `size`    | `default` | Button preset size.                                                |
| `type`    | `button`  | Button type when `as="button"`.                                    |

## Components

| Component            | Element                     | Slot                 |
|----------------------|-----------------------------|----------------------|
| `hover-card`         | `div`                       | `hover-card`         |
| `hover-card.trigger` | `button` by default         | `hover-card-trigger` |
| `hover-card.content` | `div` with `role="tooltip"` | `hover-card-content` |

## Styling Hooks

- `data-slot="hover-card"`
- `data-hover-card-open-delay-value`
- `data-hover-card-close-delay-value`
- `data-hover-card-side-value="top|right|bottom|left"`
- `data-hover-card-align-value="start|center|end"`
- `data-hover-card-side-offset-value`
- `data-hover-card-align-offset-value`
- `data-hover-card-strategy-value="fixed|absolute"`
- `data-hover-card-flip-value="true|false"`
- `data-hover-card-shift-value="true|false"`
- `data-slot="hover-card-trigger"`
- `data-variant`
- `data-size`
- `aria-expanded="true|false"`
- `data-slot="hover-card-content"`
- `data-open="true|false"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`

## Limitations

- No portal or top-layer support in this release.
- Hover Card is not a strict ARIA tooltip implementation because its content can contain richer preview markup.
- Hover Card is not intended for complex interactive panels; use Popover when the user needs to intentionally open and
  interact with controls.
