# Kbd

Keyboard input hint for shortcuts and command labels.

## Usage

```blade
<hw:kbd>⌘K</hw:kbd>

<hw:kbd.group>
    <hw:kbd>⌘</hw:kbd>
    <hw:kbd>Shift</hw:kbd>
    <hw:kbd>P</hw:kbd>
</hw:kbd.group>
```

## With Button

Use `<hw:kbd>` inside a button when the shortcut should be visible. Wire the behavior separately with the Button
`hotkey` prop:

```blade
<hw:button type="submit" hotkey="cmd+s">
    Save <hw:kbd>⌘S</hw:kbd>
</hw:button>
```

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `kbd` | `kbd` | `kbd` |
| `kbd.group` | `kbd` | `kbd-group` |

## Styling Hooks

- `data-slot="kbd"`
- `data-slot="kbd-group"`
