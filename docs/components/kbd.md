# Kbd

Keyboard input hint for shortcuts and command labels.

## Usage

```blade
<x-hwc::kbd>⌘K</x-hwc::kbd>

<x-hwc::kbd.group>
    <x-hwc::kbd>⌘</x-hwc::kbd>
    <x-hwc::kbd>Shift</x-hwc::kbd>
    <x-hwc::kbd>P</x-hwc::kbd>
</x-hwc::kbd.group>
```

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `kbd` | `kbd` | `kbd` |
| `kbd.group` | `kbd` | `kbd-group` |

## Styling Hooks

- `data-slot="kbd"`
- `data-slot="kbd-group"`
