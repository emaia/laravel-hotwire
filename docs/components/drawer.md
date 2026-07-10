# `<hw:drawer>`

Base drawer overlay with direction-aware slide transitions, backdrop, focus trap, Escape dismissal and click-outside dismissal.

## Usage

```blade
<hw:drawer direction="down">
    <hw:drawer.trigger>
        Open
    </hw:drawer.trigger>

    <hw:drawer.content>
        <hw:drawer.header>
            <hw:drawer.title>Notifications</hw:drawer.title>
            <hw:drawer.description>Recent activity for your account.</hw:drawer.description>
        </hw:drawer.header>

        <div class="flex-1 overflow-y-auto p-4">
            Content
        </div>

        <hw:drawer.footer>
            <hw:drawer.close>Close</hw:drawer.close>
        </hw:drawer.footer>
    </hw:drawer.content>
</hw:drawer>
```

## Props

| Prop | Default | Description |
|------|---------|-------------|
| `id` | auto | Root element id. |
| `direction` | `down` | `up`, `right`, `down`, or `left`. |
| `side` | `null` | Legacy alias for `direction`; `top` maps to `up`, `bottom` maps to `down`. |
| `size` | `75vw`/`24rem` for side drawers, `auto` for vertical drawers | CSS length assigned to the drawer width or height variable. |
| `backdrop` | `true` | Render the backdrop and click-outside target. |
| `openDuration` | `300` | Open transition duration in milliseconds. |
| `closeDuration` | `300` | Close transition duration in milliseconds. |
| `lockScroll` | `true` | Lock body scroll while open. |
| `closeOnEscape` | `true` | Close when Escape is pressed. |
| `closeOnClickOutside` | `true` | Close when the backdrop is clicked. |

## Components

| Component | Description |
|-----------|-------------|
| `drawer.trigger` | Button that toggles the drawer. |
| `drawer.content` | Overlay, backdrop and sliding popup wrapper. |
| `drawer.header` | Header region. |
| `drawer.title` | Drawer title. |
| `drawer.description` | Drawer description text. |
| `drawer.footer` | Footer actions region. |
| `drawer.close` | Button that closes the drawer. |

## Behavior

The drawer traps focus while open, restores focus to the trigger on close, locks body scroll by default and closes before Turbo caches the page.

Use `<hw:sheet>` instead when you want a side panel with an always-visible close button.

## Future Enhancements

Swipe gestures, nested drawers and snap points are planned as separate enhancements after the base drawer behavior is stable.
