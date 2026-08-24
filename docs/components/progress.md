# Progress

Server-rendered progress primitive with label, value, track and indicator subcomponents.

## Usage

```blade
<hw:progress value="56" />
```

Use `max` when the total is not `100`.

```blade
<hw:progress value="3" max="4" />
```

## With Label

```blade
<hw:progress value="56">
    <hw:progress.label>Upload progress</hw:progress.label>
    <hw:progress.value />
</hw:progress>
```

An empty `<hw:progress.value />` reads the percentage from its nearest Progress root and throws without one. Non-empty
explicit content can render standalone. If dynamic standalone content may render empty, opt in explicitly so an empty
value is not mistaken for a missing root:

```blade
<hw:progress.value>3 of 5</hw:progress.value>
<hw:progress.value standalone>{{ $dynamicLabel }}</hw:progress.value>
```

`standalone` always renders the supplied slot instead of an inherited percentage, including when the value sits inside a
Progress root and the slot currently renders empty.

Define a percentage-reading empty value inside the root's Blade body. Slot content passed to a wrapper renders before the
wrapper view, so a Progress root created by that wrapper cannot provide its percentage. Move the value inside that root,
or use `standalone` when it supplies its own content.

## Composition

The root renders a track and indicator automatically. Use the track and indicator subcomponents when you need to attach
attributes directly to those elements. Raw `data-slot="progress-track"` markup is also recognized; tracks inside a nested
Progress belong only to that nested root. The documented `data-slot` values on the root and all Progress subcomponents are
reserved and cannot be overridden; use `class`, `id` or another `data-*` attribute for application hooks.

```blade
<hw:progress value="25">
    <hw:progress.track aria-label="Upload track">
        <hw:progress.indicator />
    </hw:progress.track>
</hw:progress>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `progress` | `value` | `0` | Current value. Clamped between `0` and `max`. |
| `progress` | `max` | `100` | Maximum value used to calculate the filled width. |
| `progress.value` | `standalone` | `false` | Allow explicit content that may render empty without a Progress root. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `progress` | `div` | `progress` |
| `progress.track` | `div` | `progress-track` |
| `progress.indicator` | `div` | `progress-indicator` |
| `progress.label` | `span` | `progress-label` |
| `progress.value` | `span` | `progress-value` |

## Styling hooks

- `data-slot="progress"`
- `data-value`
- `data-max`
- `--progress-value`
- `data-slot="progress-track"`
- `data-slot="progress-indicator"`
- `data-slot="progress-label"`
- `data-slot="progress-value"`
