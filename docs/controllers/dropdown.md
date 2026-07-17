# Dropdown

Accessible disclosure dropdown: a trigger button toggles a Floating UI-positioned menu. It dismisses on outside click,
on `Escape` (returning focus to the trigger) and — by default — when an actionable item inside the menu is clicked. The
show/hide animation is optional and driven by CSS classes and positioning data attributes.

**Identifier:** `dropdown`  
**Install:** `php artisan hotwire:controllers dropdown`

## Requirements

- `@floating-ui/dom` for viewport-aware anchored positioning.
- Ships with `_floating.js` and `_transition.js` helpers, which `hotwire:controllers` publishes alongside the controller
  automatically.

## Targets

| Target    | Required | Description                                                                                            |
| --------- | :------: | ------------------------------------------------------------------------------------------------------ |
| `trigger` |    ✅    | The element that toggles the menu; `aria-expanded` and `data-state` are synced and it receives focus back on `Escape` |
| `menu`    |    ✅    | The element shown/hidden                                                                               |

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

## Stimulus Classes

| Class    | Default    | Description                    |
| -------- | ---------- | ------------------------------ |
| `hidden` | `"hidden"` | Class toggled to hide the menu |

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
        data-state="closed"
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
        class="hidden max-h-(--available-height) w-(--anchor-width) min-w-32 rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10"
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
keeps `aria-expanded` and `data-state` in sync on the trigger, and the preset targets the open state. If you are styling
without the preset, use the same selector instead of relying on `group-*` classes.

## Positioning

The controller uses Floating UI's `computePosition`, `autoUpdate`, `offset`, `flip`, `shift`, and `size` middleware. The
menu is positioned only while open; `autoUpdate` is cleaned up on close, `disconnect()`, and `turbo:before-cache`.

The helper writes these hooks to the menu:

- `data-side`
- `data-align`
- `--anchor-width`
- `--anchor-height`
- `--available-width`
- `--available-height`
- `--transform-origin`

The Nova preset uses those hooks for trigger-width matching, viewport-constrained height and side-aware animations. The
controller promotes the menu to the browser top layer when supported; fallback rendering can still be clipped by ancestors
with `overflow: hidden`.

When `mobile-side` or `mobile-align` is present, the controller watches `mobile-media` and recalculates Floating UI while
open if the media query changes.

When `collapsed-side` or `collapsed-align` is present, that placement is used while the dropdown root, active trigger or
menu target matches the `collapsed-when` ancestor selector. The packaged default targets collapsed Sidebars.

## Transitions

Declare enter/leave transitions with `data-transition-*` on the menu (Vue/`stimulus-use` style). They are optional —
without them the menu just toggles the hidden class.

```html
<div
    data-slot="dropdown-menu"
    data-dropdown-target="menu"
    class="hidden max-h-(--available-height) w-(--anchor-width) min-w-32 origin-(--transform-origin) rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10"
    data-transition-enter="transition ease-out duration-100"
    data-transition-enter-from="opacity-0 scale-95"
    data-transition-enter-to="opacity-100 scale-100"
    data-transition-leave="transition ease-in duration-75"
    data-transition-leave-from="opacity-100 scale-100"
    data-transition-leave-to="opacity-0 scale-95"
>
    …
</div>
```

| Attribute                    | Applied                                  |
| ---------------------------- | ---------------------------------------- |
| `data-transition-enter`      | Throughout the enter transition (timing) |
| `data-transition-enter-from` | Enter start state                        |
| `data-transition-enter-to`   | Enter end state                          |
| `data-transition-leave`      | Throughout the leave transition (timing) |
| `data-transition-leave-from` | Leave start state                        |
| `data-transition-leave-to`   | Leave end state                          |

<details>
<summary><strong>CSS-only transitions (Tailwind v4)</strong></summary>

You can skip the `data-transition-*` attributes entirely and animate with CSS, keyed off the hidden class — the
controller then just toggles `hidden` instantly and CSS does the rest. This needs three pieces, because `hidden` is
`display: none`:

1. **`@starting-style`** — the state the menu animates _from_ when it leaves `display: none` (the enter start).
2. **`transition-behavior: allow-discrete`** (Tailwind `transition-discrete`) — lets `display` take part in the
   transition, deferring `display: none` until the leave animation finishes.
3. **`display` in the transitioned properties** (hence `transition-all`).

In plain CSS:

```css
.menu {
    transition:
        opacity 150ms ease,
        transform 150ms ease,
        display 150ms allow-discrete;
    opacity: 1;
    transform: scale(1);
}

/* hidden state — the class the controller toggles */
.menu.hidden {
    display: none;
    opacity: 0;
    transform: scale(0.95);
}

/* where the enter animation starts (coming out of display: none) */
@starting-style {
    .menu:not(.hidden) {
        opacity: 0;
        transform: scale(0.95);
    }
}
```

The same thing with Tailwind v4 utilities — no `data-transition-*` needed:

```html
<div
    data-slot="dropdown-menu"
    data-dropdown-target="menu"
    class="hidden max-h-(--available-height) w-(--anchor-width) min-w-32 origin-(--transform-origin) scale-100 rounded-lg bg-popover p-1 text-popover-foreground opacity-100 shadow-md ring-1 ring-foreground/10 transition-all transition-discrete duration-150 ease-out starting:scale-95 starting:opacity-0 [&.hidden]:scale-95 [&.hidden]:opacity-0"
>
    …
</div>
```

| Utility                                    | Role                                                          |
| ------------------------------------------ | ------------------------------------------------------------- |
| `transition-all transition-discrete`       | Transition everything (incl. `display`) with `allow-discrete` |
| `opacity-100 scale-100`                    | Resting (visible) state                                       |
| `starting:opacity-0 starting:scale-95`     | `@starting-style` — where the enter starts                    |
| `[&.hidden]:opacity-0 [&.hidden]:scale-95` | Leave state, matched to the hidden class                      |

> The two approaches are mutually exclusive: provide `data-transition-*` and the JS engine drives the animation (any
> browser/Tailwind); omit them and the CSS path above takes over (needs `@starting-style`/`allow-discrete`, i.e. recent
> browsers + Tailwind v4).

</details>

## Closing on select

By default, clicking an `<a>` or `<button>` inside the menu closes it. To opt out and close manually:

```html
<div data-controller="dropdown" data-dropdown-close-on-select-value="false">
    <button data-dropdown-target="trigger" data-action="dropdown#toggle" aria-expanded="false">Filters</button>
    <div data-dropdown-target="menu" class="hidden">
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

The dropdown closes on `turbo:before-cache`, so a cached page snapshot is never restored with the menu open.
