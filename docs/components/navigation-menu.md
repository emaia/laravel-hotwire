# Navigation Menu

Use `<hw:navigation-menu>` for site navigation with direct links and rich submenus that share a floating viewport.
Use [Navbar](navbar.md) for navigation that only needs links or buttons.

## Inline submenus

Put top-level entries in `.list`, each with one `.trigger` or `.link` inside an `.item`. Put all submenu panels in
one `.viewport` after the list. Match each trigger to its panel with a unique `value` within the menu.

```blade
<hw:navigation-menu id="main-nav" aria-label="Main">
    <hw:navigation-menu.list>
        <hw:navigation-menu.item>
            <hw:navigation-menu.trigger value="products">Products</hw:navigation-menu.trigger>
        </hw:navigation-menu.item>
        <hw:navigation-menu.item>
            <hw:navigation-menu.trigger value="resources">Resources</hw:navigation-menu.trigger>
        </hw:navigation-menu.item>
        <hw:navigation-menu.item>
            <hw:navigation-menu.link href="/about" :current="request()->is('about')">About</hw:navigation-menu.link>
        </hw:navigation-menu.item>
    </hw:navigation-menu.list>

    <hw:navigation-menu.viewport>
        <hw:navigation-menu.content value="products" class="w-72">
            <p>Find the right tools for your team.</p>
            <hw:navigation-menu.link href="/products">All products</hw:navigation-menu.link>
        </hw:navigation-menu.content>
        <hw:navigation-menu.content value="resources" class="w-96">
            <div class="grid grid-cols-2 gap-3">
                <hw:navigation-menu.link href="/docs">Documentation</hw:navigation-menu.link>
                <hw:navigation-menu.link href="/examples">Examples</hw:navigation-menu.link>
            </div>
        </hw:navigation-menu.content>
    </hw:navigation-menu.viewport>
</hw:navigation-menu>
```

- Hover previews a submenu after a short delay. Clicking or tapping activates it, including when it is already
  visible from hover; activating it again closes it. Set `:hover="false"` to disable previews.
- Focus alone does not open a submenu. Press `Enter` or `Space` to toggle it. In a horizontal menu, `ArrowUp` and
  `ArrowDown` also toggle it. In a vertical menu, use `ArrowRight` in LTR or `ArrowLeft` in RTL to open it. These
  keys keep focus on the trigger; `Tab` enters the active submenu and skips closed submenus and hover previews.
  `Shift+Tab` can return from the first link to the trigger. Arrows along the menu's orientation move between items
  without opening panels; `Home` and `End` jump to the first and last items.
- `Escape` closes the submenu without closing an enclosing overlay. Focus returns to the trigger only if it was
  inside the submenu. Clicking outside, leaving the menu after the pointer delay, and Turbo snapshot caching also
  dismiss it.
- Nova and Bloom fade in the viewport on open, then slide in the incoming content when switching panels.
  When the viewport opens above its anchor, it grows upward without shifting the sliding content vertically.
  With reduced motion enabled, the viewport updates without animations. Panels that exceed the available space
  scroll independently; the shared viewport clips content while it resizes.

When embedding this component in an interactive [Dropdown](dropdown.md), set `:close-on-select="false"` on the
Dropdown so it does not dismiss itself when a submenu trigger is clicked.

## Viewport anchoring

By default, `anchor="trigger"` positions the viewport at the active trigger and sizes it to the active panel. This
fits compact menus and panels of different widths.

Use `anchor="menu"` for a mega menu. A horizontal menu centers a shared viewport on the root, up to the width of the
root. The width and horizontal position don't follow the triggers; the content slides while the viewport adapts to
the panel's height. A vertical menu anchors to the list instead and opens towards the inline end when space allows.

```blade
<hw:navigation-menu anchor="menu" aria-label="Main">
    {{-- Navigation list and triggers --}}
    <hw:navigation-menu.viewport class="[--navigation-menu-max-width:60rem]">
        {{-- Submenus fill the viewport. Use a grid or columns inside each panel. --}}
    </hw:navigation-menu.viewport>
</hw:navigation-menu>
```

`--navigation-menu-max-width` caps the viewport in menu mode. It defaults to `68rem` horizontally and `30rem`
vertically. The viewport still fits the available space and may flip to stay visible.

## Lazy Turbo Frame submenu

Set `src` on a panel to load its Turbo Frame on first opening. Use the panel slot for the loading placeholder. Closing
and reopening the panel retains the loaded content while that frame remains on the page.

```blade
{{-- Inside the viewport; the matching trigger has value="guides". --}}
<hw:navigation-menu.content value="guides" frame="navigation-guides" src="/navigation/guides" class="w-80">
    <p>Loading guides…</p>
</hw:navigation-menu.content>
```

`/navigation/guides` returns HTML containing a matching frame:

```blade
<turbo-frame id="navigation-guides">
    <hw:navigation-menu.link href="/guides/first-steps">First steps</hw:navigation-menu.link>
    <hw:navigation-menu.link href="/guides/deploying">Deploying</hw:navigation-menu.link>
</turbo-frame>
```

`frame` sets the frame **ID**, not a navigation target, on `.content`. If omitted, the ID is
`{menu-id}-content-{encoded-value}-frame`. You can use a URL with a stable explicit frame ID if the response is shared
across layouts. Links in the returned frame default to full-page navigation. Set `frame` on a link (or use
`data-turbo-frame`) to target a specific frame instead.

## Props

| Part | Prop | Default | Description |
| --- | --- | --- | --- |
| Root | `id` | Generated | Stable ID for the navigation landmark and its disclosures. |
| Root | `orientation` | `horizontal` | `horizontal` places the viewport below the active trigger; `vertical` places it to the inline end when space allows. |
| Root | `anchor` | `trigger` | Follow the active trigger, or use `menu` for the shared mega-menu placement. |
| Root | `hover` | `true` | Open submenus on mouse hover. |
| Root | `open-delay` | `100` | Milliseconds before opening on hover when no submenu is open. |
| Root | `close-delay` | `180` | Milliseconds to keep a submenu open after the pointer leaves, so it can cross into the viewport. |
| Trigger, content | `value` | Required | Matching non-empty value, unique within the menu. |
| Trigger | `chevron` | `true` | Show the disclosure indicator. |
| Trigger | `disabled` | `false` | Disable the disclosure button. |
| Link | `href` | Required | Native link destination. |
| Link | `current` | `false` | Set `aria-current="page"`. |
| Link | `frame` | `null` | Optional Turbo Frame destination. |
| Content | `src` | `null` | Optional URL requested on first opening. |
| Content | `frame` | Generated | Frame ID to match in the `src` response. |

## Styling hooks

| Slot | Element |
| --- | --- |
| `data-slot="navigation-menu"` | Navigation landmark; `data-orientation` and `data-anchor` identify the layout and anchor. |
| `data-slot="navigation-menu-list"` | Top-level list. |
| `data-slot="navigation-menu-item"` | A top-level entry. |
| `data-slot="navigation-menu-trigger"` | Disclosure button; `data-state` and `aria-expanded` track the open panel. |
| `data-slot="navigation-menu-trigger-icon"` | Optional chevron. |
| `data-slot="navigation-menu-link"` | Navigation link; `aria-current="page"` marks the current page. |
| `data-slot="navigation-menu-content"` | Submenu panel; `data-motion` tracks the entering direction. |
| `data-slot="navigation-menu-viewport"` | Shared surface sized and positioned by the controller. |

The [controller reference](../controllers/navigation-menu.md) lists the viewport CSS properties.
