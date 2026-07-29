# Dropdown

Accessible disclosure dropdown: a trigger button toggles a Floating UI-positioned menu. It dismisses on outside click,
on `Escape` (returning focus to the trigger) and — by default — when an actionable item inside the menu is clicked. Its
state-driven Presence lifecycle supports CSS motion without keeping closed content interactive.

**Identifier:** `dropdown`  
**Install:** `php artisan hotwire:controllers dropdown`

## Requirements

- `@floating-ui/dom` for viewport-aware anchored positioning.
- Ships with `_floating.js`, `_presence.js`, and `_top_layer.js`, which `hotwire:controllers` publishes alongside the
  controller automatically.
- Without the Nova preset, reset native Popover positioning with
  `[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define the floating element's border and padding.

## Targets

| Target    | Required | Description                                                                                            |
| --------- | :------: | ------------------------------------------------------------------------------------------------------ |
| `trigger` |    ✅    | The element that toggles the menu; `aria-expanded` and `data-dropdown-state` are synced and it receives focus back on `Escape` |
| `menu`    |    ✅    | The floating element whose `data-state`, `hidden`, and `inert` presence state is managed             |

## Stimulus Values

| Value             | Type      | Default | Description                                                              |
| ----------------- | --------- | ------- | ------------------------------------------------------------------------ |
| `open`            | `Boolean` | `false` | Initial/reflected open state. Set to `true` to start open (no animation) |
| `close-on-select` | `Boolean` | `true`  | Close when an `<a>` or `<button>` inside the menu is clicked             |
| `side`            | `String`  | `bottom` | Preferred side: `top`, `right`, `bottom`, or `left`                     |
| `align`           | `String`  | `start` | Alignment on the chosen side: `start`, `center`, or `end`                |
| `side-offset`     | `Number`  | `4`     | Main-axis gap between trigger and menu                                   |
| `align-offset`    | `Number`  | `0`     | Cross-axis offset along the trigger edge                                 |
| `strategy`        | `String`  | `absolute` | Floating UI strategy: `absolute` or `fixed`                          |
| `flip`            | `Boolean` | `true`  | Flip to the opposite side when the preferred side lacks room             |
| `shift`           | `Boolean` | `true`  | Shift within the viewport when the menu would overflow                   |
| `mobile-side`     | `String`  | `''`    | Optional side override while the mobile media query matches              |
| `mobile-align`    | `String`  | `''`    | Optional align override while the mobile media query matches             |
| `mobile-media`    | `String`  | `(max-width: 767px)` | Media query used by mobile side/align overrides              |
| `collapsed-side`  | `String`  | `''`    | Optional side override while inside a collapsed container                |
| `collapsed-align` | `String`  | `''`    | Optional align override while inside a collapsed container               |
| `collapsed-when`  | `String`  | Sidebar icon/collapsed selector | Selector used to detect collapsed context              |

Positioning values may live on the controller root or on the `menu` target. The packaged Blade component writes them to
`dropdown.content`, so each content element carries the placement configuration it needs.

Motion is configured on the menu itself with `data-motion="default|none"`; it is not a controller value. The
`dropdown.content` Blade component renders this attribute from its `motion` prop.

## Actions

| Action   | Description                              |
| -------- | ---------------------------------------- |
| `toggle` | Toggle open/closed (bind to the trigger) |
| `open`   | Open the menu                            |
| `close`  | Close the menu                           |

## Basic usage

The controller positions the menu for you:

```html
<div data-controller="dropdown">
    <button
        data-slot="dropdown-trigger"
        data-dropdown-target="trigger"
        data-action="dropdown#toggle"
        aria-haspopup="true"
        aria-expanded="false"
        data-dropdown-state="closed"
        class="inline-flex items-center gap-1"
    >
        Options
        <svg data-slot="dropdown-trigger-icon" class="size-5"><!-- chevron --></svg>
    </button>

    <div
        data-slot="dropdown-menu"
        data-dropdown-target="menu"
        data-dropdown-side-value="bottom"
        data-dropdown-align-value="end"
        data-state="closed"
        data-motion="default"
        hidden
        inert
        class="max-h-(--available-height) w-(--anchor-width) min-w-32 rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10"
    >
        <a href="/account" class="block px-4 py-2 text-sm">Account</a>
        <a href="/support" class="block px-4 py-2 text-sm">Support</a>
        <form action="/logout" method="post">
            <button type="submit" class="block w-full px-4 py-2 text-left text-sm">Sign out</button>
        </form>
    </div>
</div>
```

The chevron rotates for free in the package preset when it carries `data-slot="dropdown-trigger-icon"`: the controller
keeps `aria-expanded` and `data-dropdown-state` in sync on the trigger, and the preset targets the open state. The
namespaced state avoids clobbering `data-state` owned by a Toggle, Sidebar button, or another controller composed on the
same trigger.

## Positioning

The controller uses Floating UI's `computePosition`, `autoUpdate`, `offset`, `flip`, `shift`, and `size` middleware. The
menu is positioned only while present. Presence waits for the first successful placement before changing the menu to
`data-state="open"`, and stale results from stopped or superseded positioning runs cannot mutate its coordinates or CSS
variables. `autoUpdate` is cleaned up after exit, on target replacement, on `disconnect()`, and on
`turbo:before-cache`.

The helper writes these hooks to the menu:

- `data-side`
- `data-align`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`

The Nova preset uses those hooks for trigger-width matching, viewport-constrained height and side-aware motion.
`data-side` and `data-align` describe the resolved placement returned by Floating UI after `flip`, not just the requested
values. The controller promotes the menu to the browser top layer when supported; fallback rendering can still be clipped
by ancestors with `overflow: hidden`.

While native top layer is active, `fixed` uses viewport-relative coordinates and `absolute` uses page/document
coordinates; `absolute` does not use the trigger's nearest positioned ancestor in that mode. Without native Popover
support, `absolute` falls back to normal offset-parent behavior.

When `mobile-side` or `mobile-align` is present, the controller watches `mobile-media` and recalculates Floating UI while
open if the media query changes. While that query matches, mobile placement wins as a complete `(side, align)` profile:
an absent mobile value falls back to the corresponding normal `side` or `align`, never to a collapsed value.

When `collapsed-side` or `collapsed-align` is present, that placement is used while the dropdown root, active trigger or
menu target matches the `collapsed-when` ancestor selector. The packaged default targets collapsed Sidebars. Collapsed
placement is considered only when the mobile query does not match.

## Presence And Motion

Server-render menus with `data-state="closed" hidden inert`, even when `open` starts as `true`; this avoids an
unpositioned flash before Stimulus connects. Opening removes `hidden`, obtains the first Floating UI placement while the
menu remains closed and inert, then sets `data-state="open"` and removes `inert`. Closing sets `data-state="closed"` and
`inert` immediately, but keeps the menu present until its CSS motion finishes; only then does Presence add `hidden`.

The Nova preset transitions only `opacity`, `scale`, and `translate`. Custom CSS can define transitions or finite
animations using `data-state`:

```css
[data-dropdown-target~="menu"] {
    opacity: 1;
    scale: 1;
    translate: 0 0;
    transition: opacity 150ms ease, scale 150ms ease, translate 150ms ease;
}

[data-dropdown-target~="menu"][data-state="closed"] {
    opacity: 0;
    scale: .95;
    translate: 0 -.25rem;
    pointer-events: none;
}
```

Never set `display: none` or otherwise hide the menu in the closed-state CSS rule. Presence owns the `hidden` attribute
so exit motion can complete. Set `data-motion="none"` for immediate open/close. Reduced-motion preference also skips
motion, and a rapid reopen cancels stale hiding and top-layer teardown so CSS transitions can reverse naturally.

## Closing on select

By default, clicking an `<a>` or `<button>` inside the menu closes it. To opt out and close manually:

```html
<div data-controller="dropdown" data-dropdown-close-on-select-value="false">
    <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false" data-dropdown-state="closed">
        Filters
    </button>
    <div data-dropdown-target="menu" data-state="closed" data-motion="default" hidden inert>
        <!-- interactive content that should not dismiss the menu -->
        <button type="button" data-action="dropdown#close">Apply</button>
    </div>
</div>
```

## Keyboard navigation

Dropdown stays a disclosure-style popup. It does not capture arrow keys, `Home` or `End`, so action lists and custom form
panels keep native browser focus behavior. Users move through focusable content with `Tab`/`Shift+Tab`.

`Escape` closes the dropdown and returns focus to the trigger that opened it. Inside a Drawer, Modal or Sidebar,
`Escape` closes the open dropdown before the parent overlay handles a later `Escape`.

## Accessibility

- `aria-expanded` is kept in sync on the trigger(s).
- `Escape` closes the menu and returns focus to the trigger.
- Focus order is the DOM's native `Tab` order; the controller does not implement roving tabindex.
- Set `aria-haspopup` and, if you give the menu an `id`, `aria-controls` on the trigger.
- This is the **disclosure** pattern (a button revealing a panel), which is correct for menus of links/actions. It does
  not impose `role="menu"`/`menuitem` semantics or roving tabindex — that strict ARIA menu pattern is out of scope.

## Turbo

The dropdown closes synchronously on `turbo:before-cache`, applies `hidden inert`, cancels pending placement, and leaves
the top layer, so a cached page snapshot is never restored with the menu open. Replacing the menu target during a Turbo
morph tears down Presence, Floating UI, top-layer state, and target listeners for the old node immediately before the new
target is initialized.
