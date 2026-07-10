# `<hw:sidebar>`

Composable app sidebar with collapsible navigation primitives.

## Usage

```blade
<hw:sidebar.provider>
    <hw:sidebar collapsible="icon">
        <hw:sidebar.header>
            App
        </hw:sidebar.header>

        <hw:sidebar.content>
            <hw:sidebar.group>
                <hw:sidebar.group-label>Platform</hw:sidebar.group-label>
                <hw:sidebar.group-content>
                    <hw:sidebar.menu>
                        <hw:sidebar.menu-item>
                            <hw:sidebar.menu-button href="/dashboard" active>
                                <hw:icon name="panel-left" />
                                <span>Dashboard</span>
                            </hw:sidebar.menu-button>
                        </hw:sidebar.menu-item>
                    </hw:sidebar.menu>
                </hw:sidebar.group-content>
            </hw:sidebar.group>
        </hw:sidebar.content>

        <hw:sidebar.footer>
            Account
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

## Components

| Component | Description |
|-----------|-------------|
| `sidebar.provider` | State wrapper. Mounts the `sidebar` controller and exposes width CSS variables. |
| `sidebar` | Main sidebar panel. |
| `sidebar.inset` | Main content area beside the sidebar. |
| `sidebar.trigger` | Button that toggles the sidebar. |
| `sidebar.rail` | Edge control inside the sidebar that toggles it. |
| `sidebar.header` / `sidebar.content` / `sidebar.footer` | Sidebar layout regions. |
| `sidebar.group` / `sidebar.group-label` / `sidebar.group-action` / `sidebar.group-content` | Grouped navigation sections. |
| `sidebar.menu` / `sidebar.menu-item` / `sidebar.menu-button` | Primary menu structure. |
| `sidebar.menu-action` / `sidebar.menu-badge` / `sidebar.menu-skeleton` | Menu affordances. |
| `sidebar.menu-sub` / `sidebar.menu-sub-item` / `sidebar.menu-sub-button` | Nested menu structure. |
| `sidebar.input` | Search/filter input styled for the sidebar. |
| `sidebar.separator` | Sidebar separator. |

## Props

### `<hw:sidebar.provider>`

| Prop | Default | Description |
|------|---------|-------------|
| `defaultOpen` | `true` | Initial expanded state. |
| `width` | `16rem` | Value for `--sidebar-width`. |
| `mobileWidth` | `18rem` | Value for `--sidebar-width-mobile`. |
| `iconWidth` | `3rem` | Value for `--sidebar-width-icon`. |
| `controller` | `sidebar` | Stimulus identifier. |
| `stimulus` | `null` | Inline Stimulus attributes merged with the provider. |

### `<hw:sidebar>`

| Prop | Default | Description |
|------|---------|-------------|
| `side` | `left` | `left` or `right`. |
| `variant` | `sidebar` | `sidebar`, `floating`, or `inset`. |
| `collapsible` | `offcanvas` | `offcanvas`, `icon`, or `none`. |

### Menu buttons

`sidebar.menu-button` accepts `href`, `active`, `variant`, and `size`. When `href` is present it
renders an anchor; otherwise it renders a `button type="button"`.

`sidebar.menu-sub-button` accepts `href`, `active`, and `size`.

## Collapse Modes

Use `collapsible="icon"` when the collapsed sidebar should keep an icon rail visible. Put the label
inside a `<span>` after the icon so the NOVA preset can visually hide that text in collapsed mode:

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

Use `collapsible="offcanvas"` when the collapsed sidebar should slide fully out of view. Use
`collapsible="none"` for a static sidebar.

## Behavior

The provider stores the current state as `data-state="expanded|collapsed"`. Triggers and rails use
`click->sidebar#toggle`, and the controller also listens for Cmd/Ctrl+B on the window.

The controller writes the `sidebar_state` cookie by default so the app can choose to feed that value
back into `defaultOpen` on the next request.

On mobile viewports, the trigger opens a temporary drawer using `--sidebar-width-mobile`. This mobile open state is separate from the desktop expanded/collapsed state, so opening the mobile sidebar does not change the persisted desktop state.

## Styling

The NOVA preset styles all parts through semantic hooks:

- `data-slot="sidebar-wrapper"`
- `data-slot="sidebar"`
- `data-slot="sidebar-inset"`
- `data-slot="sidebar-menu-button"`
- `data-slot="sidebar-menu-sub-button"`
- `data-state="expanded|collapsed"`
- `data-collapsible="offcanvas|icon|none"`
- `data-side="left|right"`
- `data-variant="sidebar|floating|inset"`
