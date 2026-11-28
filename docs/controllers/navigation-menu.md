# Navigation Menu Controller

`navigation-menu` coordinates keyboard, mouse and touch interaction for site-navigation disclosures. Prefer
[`<hw:navigation-menu>`](../components/navigation-menu.md), which provides the semantic markup, matching IDs and
Stimulus actions.

## Values and targets

| Value | Default | Purpose |
| --- | --- | --- |
| `hover` | `true` | Open or switch submenus on mouse hover. |
| `openDelay` | `100` | Delay in milliseconds for the first hover opening. |
| `closeDelay` | `180` | Pointer-leave grace period in milliseconds. |

The `list` contains the top-level `trigger` buttons and direct `link` anchors. Each trigger's `aria-controls`
identifies a `content` panel in the shared `viewport`. The controller keeps `aria-expanded`, `data-state`,
`hidden` and `inert` in sync. The root's `data-orientation` determines arrow-key navigation and preferred
placement. With `data-anchor="menu"`, the horizontal viewport centers on the root and a vertical viewport anchors
to the list. Otherwise, the active trigger is the anchor.

Focus alone does not open a panel. Hover previews it, but users tab past the preview unless they activate the trigger
by clicking, tapping or pressing `Enter`, `Space` or a disclosure arrow key.

## Styling

The viewport gets `--navigation-menu-viewport-width` and `--navigation-menu-viewport-height` from the active
panel's measured size. `--navigation-menu-viewport-x` and `--navigation-menu-viewport-y` are the physical
coordinates from positioning. When the viewport opens above the anchor, `--navigation-menu-viewport-bottom`
holds its bottom edge's vertical coordinate so it can resize upward without moving the content vertically.
`data-positioned` indicates that the first position is available. The newly shown
panel receives `data-motion="from-start"` or `"from-end"` when switching from another trigger. These describe
list order, not physical left and right. In menu mode, the viewport takes its width from the layout rather than
the measured panel width. Use `--navigation-menu-max-width` on the viewport to set its maximum.

Inactive panels stay in the DOM but are hidden and inert. When the active panel's first child is a `turbo-frame`
with `data-navigation-menu-src` and no `src`, the controller sets `src`. It does not move the frame or
remove its loaded content when the panel closes.
