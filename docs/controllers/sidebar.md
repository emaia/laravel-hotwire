# Sidebar Controller

Controls expanded/collapsed state for sidebar markup. The Blade component wires the attributes for
you, but the controller also works with hand-written HTML.

**Identifier:** `sidebar`

## Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `open` | `boolean` | `true` | Current open state. |
| `openDuration` | `number` | `300` | Mobile drawer open transition duration in milliseconds. |
| `closeDuration` | `number` | `300` | Mobile drawer close transition duration in milliseconds. |
| `persist` | `boolean` | `true` | Whether state changes write a cookie. |
| `cookieName` | `string` | `sidebar_state` | Cookie name used when persistence is enabled. |

## Actions

| Action | Description |
|--------|-------------|
| `toggle` | Toggle expanded/collapsed state. |
| `open` | Expand the sidebar. |
| `close` | Collapse the sidebar. |
| `shortcut` | Toggle on Cmd/Ctrl+B when bound to `keydown@window`. |

## Events

State changes dispatch `sidebar:change` with:

```js
{ open: boolean, state: "expanded" | "collapsed" }
```

## Markup Contract

The controller updates:

- root `data-state`
- child sidebars with `data-slot="sidebar"` and `data-sidebar-collapsible`
- trigger `aria-expanded`

The child sidebar's `data-sidebar-collapsible` value is the mode restored when the sidebar closes.
Use `icon` to keep an icon rail visible, `offcanvas` to move the sidebar fully out of view, or
`none` for a static sidebar.

Mobile viewports use a separate drawer state exposed as `data-mobile-state="open|closed"` on the sidebar element. Desktop `data-state` remains unchanged while the mobile drawer opens or closes.

## Standalone Usage

```html
<div
    data-controller="sidebar"
    data-sidebar-open-value="true"
    data-action="keydown@window->sidebar#shortcut"
    data-state="expanded"
    style="--sidebar-width: 16rem; --sidebar-width-icon: 3rem"
>
    <button
        type="button"
        data-slot="sidebar-trigger"
        data-action="click->sidebar#toggle"
        aria-label="Toggle Sidebar"
    >
        Toggle
    </button>

    <aside
        data-slot="sidebar"
        data-state="expanded"
        data-collapsible=""
        data-sidebar-collapsible="icon"
    >
        <nav>
            <a data-slot="sidebar-menu-button" href="/dashboard">
                <svg aria-hidden="true"></svg>
                <span>Dashboard</span>
            </a>
        </nav>
    </aside>
</div>
```

For custom markup, keep `data-slot="sidebar"` and `data-sidebar-collapsible` on every sidebar panel
that should be controlled. Keep `data-slot="sidebar-trigger"` on triggers if you want the controller
to maintain `aria-expanded`.
