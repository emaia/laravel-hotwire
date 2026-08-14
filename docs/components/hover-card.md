# Hover Card

Anchored preview card that opens from hover or focus. Use it for lightweight contextual detail such as profile previews,
record summaries or metadata hints. Use [Popover](popover.md) instead when the panel needs click-triggered interaction or
long-lived form content.

The component wraps the [`hover-card`](../controllers/hover-card.md) Stimulus controller, renders the trigger/content
wiring and positions the content with Floating UI.

## Basic Usage

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
`aria-describedby`, and keeps `aria-expanded` and `data-hover-card-state="open|closed"` in sync. The content uses
`data-state="open|closed"` for Presence styling. Pass `as="a"`, `href`, `variant` or `size` when the trigger should use
another semantic element or button style:

```blade
<hw:hover-card>
    <hw:hover-card.trigger as="a" href="/users/1" variant="link">
        Jane Doe
    </hw:hover-card.trigger>

    <hw:hover-card.content>
        Product designer on the Growth team.
    </hw:hover-card.content>
</hw:hover-card>
```

Trigger `as` values are trimmed, lowercased, and restricted to `button` or `a`. Native button `type` accepts `button`,
`submit`, or `reset`. An anchor without `href` receives `tabindex="0"` so focus can still open the card. Disabled anchors
omit `href` and receive `aria-disabled="true"` and `tabindex="-1"`.

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

Hover Card uses `strategy="fixed"` by default and promotes the preview to the browser's native top layer when supported,
so it can cross Drawer, Modal, Turbo Frame and scroll-container boundaries more reliably:

```blade
<hw:hover-card side="right" align="center" :side-offset="8">
    <hw:hover-card.trigger>Plan</hw:hover-card.trigger>

    <hw:hover-card.content>Enterprise, renewed yearly.</hw:hover-card.content>
</hw:hover-card>
```

Both strategies retain top-layer promotion. `fixed` uses viewport-relative coordinates; `absolute` uses page/document
coordinates while in the top layer, not the nearest positioned ancestor. Top-layer promotion does not move the node
elsewhere in the DOM. Browsers without the native Popover API use the normal DOM fallback, where `absolute` uses its
offset parent and transformed, contained, or clipped ancestors can still affect rendering.

The enter motion starts only after Floating UI resolves the first placement. If `flip` changes the preferred placement,
`data-side` and `data-align` expose the resolved placement used on screen. Superseded asynchronous positioning results
are ignored.

## Automatic Behavior

Hover Card opens on hover and focus, and closes on mouse leave, blur, `Escape`, and Turbo's `before-cache` event. Keep the
content short and mostly non-interactive. Links or buttons can work while the pointer or focus remains inside the card,
but a Popover is usually the better fit for deliberate interaction.

## Motion And Presence

Closed content is server-rendered with `data-state="closed" hidden inert`, including when `open="true"`; the controller
positions an initially open card before showing it without enter motion. During exit, `data-state="closed"` and `inert`
apply immediately, while `hidden` is deferred until CSS motion finishes. This keeps the preview non-interactive without
cutting off its exit.

The default preset CSS transitions only `opacity`, `scale`, and `translate`. Set motion on the content component:

```blade
<hw:hover-card>
    <hw:hover-card.trigger>Instant preview</hw:hover-card.trigger>

    <hw:hover-card.content motion="none">
        <!-- preview -->
    </hw:hover-card.content>
</hw:hover-card>
```

Custom CSS may use transitions or finite animations keyed by `data-state`. A closed-state selector must never apply
`display: none` or `hidden`; Presence owns the `hidden` attribute. Rapid reopen cancels stale exit cleanup, and
`prefers-reduced-motion: reduce` skips motion automatically.

## Props

| Component | Prop | Default | Description |
|---|---|---|---|
| `hover-card` | `id` | `uniqid('hover-card-')` | Content id and trigger `aria-describedby`. |
| `hover-card` | `side` | `bottom` | Preferred side: `top`, `right`, `bottom` or `left`. |
| `hover-card` | `align` | `start` | Content alignment: `start`, `center` or `end`. |
| `hover-card` | `side-offset` | `4` | Main-axis gap between the trigger and content. |
| `hover-card` | `align-offset` | `0` | Cross-axis offset along the trigger edge. |
| `hover-card` | `strategy` | `fixed` | Floating UI strategy: `fixed` or `absolute`. |
| `hover-card` | `flip` | `true` | Flip to the opposite side when the preferred side lacks room. |
| `hover-card` | `shift` | `true` | Shift within the viewport when the content would overflow. |
| `hover-card` | `open-delay` | `10` | Delay in milliseconds before opening after hover or focus. |
| `hover-card` | `close-delay` | `100` | Delay in milliseconds before closing after mouse leave or blur. |
| `hover-card` | `open` | `false` | Start open without waiting for hover or focus. |
| `hover-card` | `stimulus` | `null` | Optional Stimulus binding from `stimulus()`, merged with the internal controller. |
| `hover-card.content` | `motion` | `default` | Presence motion: `default` or `none`. |

## Trigger Props

| Prop      | Default   | Description                                                        |
|-----------|-----------|--------------------------------------------------------------------|
| `as`      | `button`  | Render `button` or `a`. Values are normalized and validated.       |
| `variant` | `link`    | Button preset variant: `default`, `outline`, `ghost`, `link`, etc. |
| `size`    | `default` | Button preset size.                                                |
| `type`    | `button`  | Native button type: `button`, `submit`, or `reset`.                 |

## Components

| Component            | Element                     | Slot                 |
|----------------------|-----------------------------|----------------------|
| `hover-card`         | `div`                       | `hover-card`         |
| `hover-card.trigger` | `button` by default         | `hover-card-trigger` |
| `hover-card.content` | `div` with `role="tooltip"` | `hover-card-content` |

## Styling hooks

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
- `data-hover-card-state="open|closed"` on the trigger
- `data-state="open|closed"` on the content
- `data-slot="hover-card-content"`
- `data-motion="default|none"`
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`

## Limitations

- Hover Card is not a strict ARIA tooltip implementation because its content can contain richer preview markup.
- Hover Card is not intended for complex interactive panels; use Popover when the user needs to intentionally open and
  interact with controls.
- Native top-layer promotion depends on browser Popover API support; the fallback can still be clipped by ancestors.
