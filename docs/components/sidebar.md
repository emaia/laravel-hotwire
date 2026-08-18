# `<hw:sidebar>`

Composable app sidebar with collapsible navigation primitives.

## Usage

```blade
<hw:sidebar.provider>
    <hw:sidebar collapsible="icon">
        <hw:sidebar.header>
            <hw:sidebar.brand href="/" label="Acme Cloud">
                <span>Acme Cloud</span>

                <x-slot:icon>
                    <hw:icon name="panel-left" />
                </x-slot:icon>
            </hw:sidebar.brand>

            <hw:sidebar.input placeholder="Search..." />
        </hw:sidebar.header>

        <hw:sidebar.separator />

        <hw:sidebar.content>
            <hw:sidebar.group>
                <hw:sidebar.group-label>Platform</hw:sidebar.group-label>
                <hw:sidebar.group-action aria-label="Add project">
                    <x-lucide-plus class="size-4" />
                </hw:sidebar.group-action>

                <hw:sidebar.group-content>
                    <hw:sidebar.menu>
                        <hw:sidebar.menu-item>
                            <hw:sidebar.menu-button
                                href="/dashboard"
                                active
                                data-controller="tooltip"
                                data-tooltip-content-value="Dashboard"
                                data-tooltip-side-value="right"
                                data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
                            >
                                <hw:icon name="panel-left" />
                                <span>Dashboard</span>
                            </hw:sidebar.menu-button>
                            <hw:sidebar.menu-badge>12</hw:sidebar.menu-badge>
                        </hw:sidebar.menu-item>

                        <hw:sidebar.menu-item>
                            <hw:sidebar.menu-button href="/projects">
                                <x-lucide-folder class="size-4" />
                                <span>Projects</span>
                            </hw:sidebar.menu-button>

                            <hw:sidebar.menu-action show-on-hover aria-label="Create project">
                                <x-lucide-plus class="size-4" />
                            </hw:sidebar.menu-action>

                            <hw:sidebar.menu-sub>
                                <hw:sidebar.menu-sub-item>
                                    <hw:sidebar.menu-sub-button href="/projects/acme" active>
                                        Acme
                                    </hw:sidebar.menu-sub-button>
                                </hw:sidebar.menu-sub-item>
                                <hw:sidebar.menu-sub-item>
                                    <hw:sidebar.menu-sub-button href="/projects/roadmap">
                                        Roadmap
                                    </hw:sidebar.menu-sub-button>
                                </hw:sidebar.menu-sub-item>
                            </hw:sidebar.menu-sub>
                        </hw:sidebar.menu-item>

                        <hw:sidebar.menu-item>
                            <hw:sidebar.menu-skeleton show-icon width="60%" />
                        </hw:sidebar.menu-item>
                    </hw:sidebar.menu>
                </hw:sidebar.group-content>
            </hw:sidebar.group>
        </hw:sidebar.content>

        <hw:sidebar.footer>
            <hw:sidebar.menu>
                <hw:sidebar.menu-item>
                    <hw:sidebar.menu-button href="/account" size="lg">
                        <x-lucide-user class="size-4" />
                        <span>Account</span>
                    </hw:sidebar.menu-button>
                </hw:sidebar.menu-item>
            </hw:sidebar.menu>
        </hw:sidebar.footer>

        <hw:sidebar.rail />
    </hw:sidebar>

    <hw:sidebar.inset>
        <header>
            <hw:sidebar.trigger />
        </header>

        {{ $slot }}
    </hw:sidebar.inset>
</hw:sidebar.provider>
```

The tooltip controller uses the package's Floating UI dependency and only appears while the sidebar is collapsed when
`data-tooltip-enabled-when-value` targets the icon rail state.

## Components

| Component                                                                                  | Description                                                                     |
|--------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------|
| `sidebar.provider`                                                                         | State wrapper. Mounts the `sidebar` controller and exposes width CSS variables. |
| `sidebar`                                                                                  | Main sidebar panel.                                                             |
| `sidebar.inset`                                                                            | Main content area beside the sidebar.                                           |
| `sidebar.trigger`                                                                          | Button that toggles the sidebar.                                                |
| `sidebar.rail`                                                                             | Edge control inside the sidebar that toggles it.                                |
| `sidebar.brand`                                                                            | Header brand link with separate expanded and icon-collapsed content.            |
| `sidebar.header` / `sidebar.content` / `sidebar.footer`                                    | Sidebar layout regions.                                                         |
| `sidebar.group` / `sidebar.group-label` / `sidebar.group-action` / `sidebar.group-content` | Grouped navigation sections.                                                    |
| `sidebar.menu` / `sidebar.menu-item` / `sidebar.menu-button`                               | Primary menu structure.                                                         |
| `sidebar.menu-action` / `sidebar.menu-badge` / `sidebar.menu-skeleton`                     | Menu affordances.                                                               |
| `sidebar.menu-sub` / `sidebar.menu-sub-item` / `sidebar.menu-sub-button`                   | Nested menu structure.                                                          |
| `sidebar.input`                                                                            | Search/filter input styled for the sidebar.                                     |
| `sidebar.separator`                                                                        | Sidebar separator.                                                              |

## Props

### `<hw:sidebar.provider>`

| Prop          | Default         | Description                                                                                   |
|---------------|-----------------|-----------------------------------------------------------------------------------------------|
| `defaultOpen` | `null`          | Initial expanded state. When omitted, the provider reads `cookieName` and falls back to open. |
| `width`       | `16rem`         | Value for `--sidebar-width`.                                                                  |
| `mobileWidth` | `18rem`         | Value for `--sidebar-width-mobile`.                                                           |
| `iconWidth`   | `3rem`          | Value for `--sidebar-width-icon`.                                                             |
| `cookieName`  | `sidebar_state` | Cookie used to persist the desktop expanded/collapsed state.                                  |
| `controller`  | `sidebar`       | Stimulus identifier.                                                                          |
| `stimulus`    | `null`          | Inline Stimulus attributes merged with the provider.                                          |

### `<hw:sidebar>`

| Prop          | Default     | Description                                              |
|---------------|-------------|----------------------------------------------------------|
| `side`        | `left`      | `left` or `right`.                                       |
| `variant`     | `sidebar`   | `sidebar`, `floating`, or `inset`.                       |
| `collapsible` | `offcanvas` | `offcanvas`, `icon`, or `none`.                          |
| `motion`      | `default`   | `default` follows mobile CSS motion; `none` disables it. |
| `reveal`      | `false`     | Mounts Reveal directly on the existing sidebar surface with document scope. |
| `revealMotion` | `rise`     | Reveal motion: `rise`, `flat`, or `fade`.                |
| `revealStagger` / `revealDuration` / `revealDelay` / `revealMaxSteps` | preset | Optional Reveal timing overrides. |

### Reveal integration

Use `reveal` when the sidebar chrome should cascade once per document without adding a wrapper around its layout
surface:

```blade
<hw:sidebar reveal reveal-stagger="35ms" reveal-duration="380ms">
    <hw:sidebar.brand data-reveal-item style="--reveal-index: 0" ... />
    <hw:sidebar.separator data-reveal-item style="--reveal-index: 1" />
    <hw:sidebar.group-label data-reveal-item style="--reveal-index: 2">Projects</hw:sidebar.group-label>
    ...
</hw:sidebar>
```

The component mounts `data-controller="reveal"` and `data-reveal-scope="document"` directly on `sidebar-container`.
For `collapsible="none"`, it mounts them on the native `<aside>`. There is no extra wrapper, so the provider flex row,
sidebar column, fixed positioning, and view-transition selectors keep their existing structure. Mark the exact sidebar
units with `data-reveal-item`; automatic direct-child mode is deliberately not enabled because the sidebar's internal
wrappers are layout mechanics rather than animation units.

`--reveal-index` is optional after the controller connects: missing indexes are assigned in document order. That lets an
eagerly loaded Reveal produce the expected cascade with only `data-reveal-item` in most browsers. For a deterministic
first-paint cascade, keep the indexes server-rendered as shown above. Without them, every nested explicit item initially
uses index `0`; a lazy controller may connect only after CSS has already started those items together. Configuring
`reveal` under `hotwire.controllers.eager` narrows that window but is not the same guarantee as rendering the index.

On a desktop sidebar initially collapsed with `collapsible="icon"`, Nova suppresses Reveal on group labels because that
state already hides the label with `opacity: 0`. The label still consumes its declared cascade index, leaving one timing
step with no visible animation; reindexing by runtime visibility would add disproportionate complexity. Expanded and
mobile labels keep their normal Reveal entrance.

### Menu buttons

`sidebar.menu-button` accepts `href`, `active`, `variant`, `size`, `type`, and `frame`. When `href` is present it renders
an anchor; otherwise it renders a button. `type` defaults to `button` and accepts `button`, `submit`, or `reset`.

`sidebar.menu-sub-button` accepts `href`, `active`, `size`, `type`, and `frame`, with the same native button type allowlist.

### Brand

`sidebar.brand` accepts `href`, `label`, and `frame`. `label` provides the accessible name when `href` makes the brand a
link; without `href`, the default slot provides the non-link brand's accessible content. The default slot renders while
the sidebar is expanded. The `icon` slot renders when `collapsible="icon"` is collapsed:

```blade
<hw:sidebar.header>
    <hw:sidebar.brand href="/" label="Acme Cloud">
        <x-logo-horizontal class="h-8 w-auto" />

        <x-slot:icon>
            <x-logo-icon class="size-8" />
        </x-slot:icon>
    </hw:sidebar.brand>
</hw:sidebar.header>
```

If the `icon` slot is omitted, the brand keeps rendering the default slot in every state.

On brand, menu button, and menu sub-button links, `frame` emits `data-turbo-frame`. It accepts strings or objects resolved
with `dom_id()`; null, false, empty, and whitespace-only values are omitted. An explicit `data-turbo-frame` wins and can
be bound to `false` to suppress the prop. Components without `href` render non-link controls and omit frame metadata.

## Collapse Modes

Use `collapsible="icon"` when the collapsed sidebar should keep an icon rail visible. Put the label inside a `<span>`
after the icon so the NOVA preset can visually hide that text in collapsed mode:

```blade
<hw:sidebar.provider>
    <hw:sidebar collapsible="icon">
        <hw:sidebar.menu>
            <hw:sidebar.menu-item>
                <hw:sidebar.menu-button href="/dashboard">
                    <hw:icon name="panel-left" />
                    <span>Dashboard</span>
                </hw:sidebar.menu-button>
            </hw:sidebar.menu-item>
        </hw:sidebar.menu>
    </hw:sidebar>
</hw:sidebar.provider>
```

Pair icon-only rails with the `tooltip` controller when labels are hidden. This example keeps the tooltip disabled while
the sidebar is expanded and the label is already visible:

```blade
<hw:sidebar.provider>
    <hw:sidebar collapsible="icon">
        <hw:sidebar.header>
            Components
        </hw:sidebar.header>

        <hw:sidebar.content>
            <hw:sidebar.menu>
                <hw:sidebar.menu-item>
                    <hw:sidebar.menu-button
                        href="/components/map"
                        data-controller="tooltip"
                        data-tooltip-content-value="Map"
                        data-tooltip-side-value="right"
                        data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
                    >
                        <x-lucide-map class="size-5" />
                        <span>Map</span>
                    </hw:sidebar.menu-button>
                </hw:sidebar.menu-item>
            </hw:sidebar.menu>
        </hw:sidebar.content>

        <hw:sidebar.rail />
    </hw:sidebar>

    <hw:sidebar.inset>
        <header>
            <hw:sidebar.trigger />
        </header>

        {{ $slot }}
    </hw:sidebar.inset>
</hw:sidebar.provider>
```

Use `collapsible="offcanvas"` when the collapsed sidebar should slide fully out of view. Use
`collapsible="none"` for a static sidebar.

## Behavior

The provider stores the current state as `data-state="expanded|collapsed"`. Triggers and rails use
`click->sidebar#toggle`, and the controller also listens for Cmd/Ctrl+B on the window.

The controller writes the cookie named by `cookieName` by default, and the provider reads it automatically when
`defaultOpen` is omitted. Pass `defaultOpen` explicitly when a page should ignore the persisted state.

On mobile viewports, the trigger opens a temporary drawer using `--sidebar-width-mobile`. This mobile Presence state is
separate from the desktop expanded/collapsed state, so opening the mobile sidebar does not change the persisted desktop
state. `data-mobile-state="open|closed"`, `hidden`, and `inert` coordinate the mobile lifecycle; reduced motion and
`motion="none"` complete without waiting.

Clicking a normal link inside the open mobile drawer waits for the actual exit motion before navigation continues.
Modified clicks, non-`_self` `target` links, downloads and `mailto:`/`tel:` links are not intercepted.

### Nested providers

A provider only drives the sidebars and triggers up to the next `data-slot="sidebar-wrapper"` below it, so a collapsible
panel nested inside the shell sidebar keeps its own state. The boundary is the wrapper slot, not the controller
identifier, so it holds whether or not the inner provider runs a custom `controller` — `<hw:sidebar>` names its overlay
targets after the provider it belongs to, so a nested drawer stays wired to its own provider.

Give each nested provider a distinct `cookieName`. Besides keeping the persisted states apart, it is how a provider
recognizes itself in the next page during a Turbo render: providers that share a cookie name are told apart by position
alone, and a page that drops the outer provider then shifts that position. When no match is found the provider leaves
the incoming markup as the server rendered it.

Both providers still answer Cmd/Ctrl+B, since that shortcut is bound to the window.

## Styling hooks

The preset styles all parts through semantic hooks:

- `data-slot="sidebar-wrapper"`
- `data-slot="sidebar"`
- `data-slot="sidebar-backdrop"`
- `data-slot="sidebar-trigger"`
- `data-slot="sidebar-rail"`
- `data-slot="sidebar-inset"`
- `data-slot="sidebar-header"`
- `data-slot="sidebar-brand"`
- `data-slot="sidebar-brand-logo"`
- `data-slot="sidebar-brand-icon"`
- `data-slot="sidebar-footer"`
- `data-slot="sidebar-content"`
- `data-slot="sidebar-input"`
- `data-slot="sidebar-separator"`
- `data-slot="sidebar-group"`
- `data-slot="sidebar-group-label"`
- `data-slot="sidebar-group-action"`
- `data-slot="sidebar-group-content"`
- `data-slot="sidebar-menu"`
- `data-slot="sidebar-menu-item"`
- `data-slot="sidebar-menu-button"`
- `data-slot="sidebar-menu-action"`
- `data-slot="sidebar-menu-badge"`
- `data-slot="sidebar-menu-skeleton"`
- `data-slot="sidebar-menu-skeleton-icon"`
- `data-slot="sidebar-menu-skeleton-text"`
- `data-slot="sidebar-menu-sub"`
- `data-slot="sidebar-menu-sub-item"`
- `data-slot="sidebar-menu-sub-button"`
- `data-slot="sidebar-gap"`
- `data-slot="sidebar-container"`
- `data-slot="sidebar-inner"`
- `data-state="expanded|collapsed"`
- `data-collapsible="offcanvas|icon|none"`
- `data-side="left|right"`
- `data-variant="sidebar|floating|inset"`
