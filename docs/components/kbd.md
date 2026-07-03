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

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `kbd` | `kbd` | `kbd` |
| `kbd.group` | `kbd` | `kbd-group` |

## Styling Hooks

- `data-slot="kbd"`
- `data-slot="kbd-group"`
