# `<hw:side-panel>`

Composable inline panel for secondary navigation, filters, and workspace tools. Unlike [`<hw:sidebar>`](./sidebar.md),
Side Panel stays in normal document flow, never becomes a mobile overlay, and can be nested safely.

## Basic Usage

```blade
<hw:side-panel name="project-navigation" width="18rem">
    <hw:side-panel.panel>
        <nav aria-label="Project">
            <!-- Project navigation -->
        </nav>
    </hw:side-panel.panel>

    <hw:side-panel.inset>
        <hw:side-panel.trigger />
        {{ $slot }}
    </hw:side-panel.inset>
</hw:side-panel>
```

The panel animates to a narrow control rail while the inset consumes the released space. The rail defaults to `1.75rem`,
keeping the trigger inside the root even when an ancestor clips overflow. Panel content becomes `inert` as soon as
collapse starts, so links and controls inside it are skipped by keyboard navigation.

## Automatic Behavior

`name="project-navigation"` uses the cookie `side_panel_project-navigation_state`. The server reads that cookie before
rendering, so the first frame already matches the persisted state. Passing `defaultOpen` explicitly or setting
`persist="false"` ignores it. Laravel may discard this unencrypted package cookie from `request()->cookie()`, so the
component falls back to the raw Cookie header when `EncryptCookies` is active.

The root exposes `data-state="expanded|collapsed"` and `data-side="left|right"`. The trigger mirrors state through
`aria-expanded` and references the generated panel id through `aria-controls`.

Before Turbo Drive renders a new body, the controller copies the live state into matching incoming markup so a response
prepared before the latest toggle cannot restore stale state.

## Nesting

Each controller works only with its own Stimulus targets. Nested Side Panels therefore retain independent state and
cookies:

```blade
<hw:side-panel name="workspace">
    <hw:side-panel.panel>
        <hw:side-panel name="filters" width="12rem">
            <hw:side-panel.panel>Filters</hw:side-panel.panel>
            <hw:side-panel.inset>Results</hw:side-panel.inset>
        </hw:side-panel>
    </hw:side-panel.panel>

    <hw:side-panel.inset>Workspace</hw:side-panel.inset>
</hw:side-panel>
```

Side Panel can also be placed inside an app `<hw:sidebar>` without sharing state or overlay behavior.

## Hotkeys

Side Panel deliberately has no default hotkey. A page can contain several panels, so a package-wide shortcut would be
ambiguous. Compose the standalone [`hotkey` controller](../controllers/hotkey.md) onto a trigger when a specific panel
needs one.

## Props

### `<hw:side-panel>`

| Prop          | Default               | Description                                                                         |
| ------------- | --------------------- | ----------------------------------------------------------------------------------- |
| `name`        | required              | Stable key used to isolate the persisted cookie and generated panel id.             |
| `panelId`     | generated from `name` | Shared id for the panel and trigger's `aria-controls`.                              |
| `defaultOpen` | `null`                | Initial state. When omitted, reads the generated cookie and falls back to expanded. |
| `width`       | `16rem`               | Expanded panel width.                                                               |
| `side`        | `left`                | Physical `left` or `right`, preserved in RTL layouts.                               |
| `persist`     | `true`                | Whether controller changes write the state cookie.                                  |
| `controller`  | `side-panel`          | Stimulus identifier.                                                                |
| `stimulus`    | `null`                | Inline Stimulus attributes merged with the root.                                    |

### `<hw:side-panel.trigger>`

| Prop    | Default             | Description                               |
| ------- | ------------------- | ----------------------------------------- |
| `label` | `Toggle Side Panel` | Accessible label rendered on the trigger. |

## Components

| Component            | Description                          |
| -------------------- | ------------------------------------ |
| `side-panel`         | State and layout root.               |
| `side-panel.panel`   | Collapsible `aside` surface.         |
| `side-panel.trigger` | Edge control that toggles the panel. |
| `side-panel.inset`   | Main content beside the panel.       |

## Styling hooks

Collapse geometry lives in `resources/css/structural.css` and is included by every preset. Visual styling uses:

- `data-slot="side-panel"`
- `data-slot="side-panel-panel"`
- `data-slot="side-panel-panel-content"`
- `data-slot="side-panel-trigger"`
- `data-slot="side-panel-trigger-icon"`
- `data-slot="side-panel-inset"`
- `data-state="expanded|collapsed"`
- `data-side="left|right"`

Override `--side-panel-collapsed-width` to change the collapsed rail width and `--side-panel-trigger-size` to change the
edge control size. Both default to `1.75rem`; keeping the rail at least as wide as the trigger prevents clipping under an
ancestor with `overflow: hidden`.

`--side-panel-trigger-rotation` communicates the current physical side/state to the structural trigger icon rule, so
nested panels remain independent. Presets can override the resulting `transform` when they use a different icon motion.
