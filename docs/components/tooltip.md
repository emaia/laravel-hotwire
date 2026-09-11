# Tooltip

Blade-authored source template for accessible, non-interactive help positioned by the `tooltip` controller.

## Usage

Mount the controller on the actual trigger and render exactly one Tooltip component inside it:

```blade
<button type="button" data-controller="tooltip">
    Save
    <hw:tooltip>Save changes</hw:tooltip>
</button>
```

`<hw:tooltip>` renders an inert `<template>`. On hover or focus, the controller clones its surface into
`document.body`, assigns a unique id, positions it with Floating UI and adds that id to the trigger's
`aria-describedby`. The source remains in place and is reused on the next opening.

The controller does not depend on this component. Standalone usage can provide an application-owned template and CSS;
see [Tooltip controller](../controllers/tooltip.md#standalone-template). The component is the package-styled producer
of the same root-template contract.

Use Blade composition for rich text. Content is descriptive only; use Popover for links, buttons or form controls.

```blade
<hw:button data-controller="tooltip">
    Save
    <hw:tooltip>Save changes <hw:kbd>⌘S</hw:kbd></hw:tooltip>
</hw:button>
```

Button and Color Scheme Toggle expose a `tooltip` string prop that mounts this same template internally. Sidebar Menu
Button also exposes `tooltip`. These convenience strings are escaped and rendered as text; use an explicit
`<hw:tooltip>` child for rich Blade content rather than putting HTML in an attribute. By default, Sidebar enables the
clone only while an icon rail is collapsed on desktop and the mobile Sidebar is closed. Its `tooltip-enabled-when` prop
can replace or remove that condition.

Attributes on `<hw:tooltip>` are copied to the portaled surface. This is useful for an application class, `dir`, or an
explicit `data-theme`. A local theme or direction inherited only from the trigger's ancestors does not follow a clone
ported to `document.body`; put that context on the Tooltip component when it must differ from the document root.

## Component

| Component | Element | Slots |
| --- | --- | --- |
| `tooltip` | Inert `template` containing a `div` with `role="tooltip"` | `tooltip`, `tooltip-arrow` |

The source surface starts as `data-state="closed" hidden inert`. The controller owns runtime role, id, motion, state,
placement and top-layer attributes, so those attributes and internal `data-tooltip-*` markers cannot be overridden.

## Styling hooks

- `data-slot="tooltip"`
- `data-slot="tooltip-arrow"`
- `data-state="open|closed"` on the clone
- `data-motion="default|none"` on the clone
- `data-side="top|right|bottom|left"`
- `data-align="start|center|end"`
- `data-anchor-hidden`
- `--transform-origin`

See [Tooltip controller](../controllers/tooltip.md) for positioning, delays, conditional display and lifecycle values.
