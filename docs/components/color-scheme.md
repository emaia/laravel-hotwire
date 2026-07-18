# Color Scheme

Applies and toggles the package colour scheme using `html[data-theme="light|dark"]`.

Use `<hw:color-scheme.script>` in the document head to avoid a flash of the wrong theme, then render one or more
`<hw:color-scheme.toggle>` buttons anywhere in the UI.

## Basic usage

```blade
<html>
<head>
    <hw:color-scheme.script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <hw:color-scheme.toggle aria-label="Toggle color scheme" tooltip="Toggle color scheme" />
</body>
</html>
```

The script reads `localStorage`, resolves `system` through `prefers-color-scheme`, sets `data-theme` and
`data-color-scheme-mode` on `<html>`, then updates `style.colorScheme` before CSS paints. The mode attribute lets the
toggle show the correct icon before Stimulus connects.

## Script Props

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `default` | `light\|dark\|system` | `system` | Mode used when no valid value exists in storage. |
| `storage-key` | `string` | `hotwire.colorScheme` | Key used for the persisted mode. |
| `attribute` | `string` | `data-theme` | Attribute written to `<html>`. Keep the default unless you also customise the CSS preset. |

## Toggle Props

| Prop | Type | Default | Description |
| --- | --- | --- | --- |
| `variant` | `string` | `outline` | Button visual variant from the active preset. |
| `size` | `string` | `icon` | Button size from the active preset. |
| `modes` | `string` | `light dark system` | Space-separated cycle order. |
| `storage-key` | `string` | `hotwire.colorScheme` | Must match the script storage key. |
| `default` | `light\|dark\|system` | `system` | Fallback mode. |
| `tooltip` | `string\|null` | `null` | Mounts the `tooltip` controller with this content. |
| `tooltip-side` | `string\|null` | `null` | Tooltip side when `tooltip` is set. |
| `tooltip-align` | `string\|null` | `null` | Tooltip alignment when `tooltip` is set. |
| `tooltip-enabled-when` | `string\|null` | `null` | Selector that controls tooltip visibility. |

Any other HTML attribute passes through to the button. Internal `data-color-scheme-*` attributes are protected; configure
the controller with props instead.

## Custom Cycle Order

```blade
<hw:color-scheme.toggle modes="light dark" aria-label="Toggle dark mode" />
```

This keeps the stored mode to explicit `light` or `dark` and skips `system` while cycling.

## Styling Hooks

- `data-slot="color-scheme-toggle"`
- `data-slot="color-scheme-icon"`
- `html[data-color-scheme-mode="light|dark|system"]`
- `data-mode="light|dark|system"`
- `data-scheme="light|dark"`
- `data-scheme-icon="light|dark"`
- `data-mode-icon="system"`

The Nova preset reads `html[data-color-scheme-mode]` when the script has run, so the initial icon matches the stored
mode before Stimulus connects. Without the script, it falls back to the toggle's local `data-mode` and `data-scheme`
attributes.

## Required Controllers

`<hw:color-scheme.toggle>` mounts the `color-scheme` controller. The `tooltip` controller is also mounted when the
`tooltip` prop is set.
