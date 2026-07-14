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

## Composition

The root renders a track and indicator automatically. Use the track and indicator subcomponents when you need to attach
attributes directly to those elements.

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

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `progress` | `div` | `progress` |
| `progress.track` | `div` | `progress-track` |
| `progress.indicator` | `div` | `progress-indicator` |
| `progress.label` | `span` | `progress-label` |
| `progress.value` | `span` | `progress-value` |

## Styling Hooks

- `data-slot="progress"`
- `data-value`
- `data-max`
- `--progress-value`
- `data-slot="progress-track"`
- `data-slot="progress-indicator"`
- `data-slot="progress-label"`
- `data-slot="progress-value"`
