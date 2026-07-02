# Alert

Inline feedback block for status messages, warnings and contextual notices.

## Usage

```blade
<x-hwc::alert>
    <x-hwc::icon name="info" />
    <x-hwc::alert.title>Heads up</x-hwc::alert.title>
    <x-hwc::alert.description>
        You can add components to your app using the installer.
    </x-hwc::alert.description>
</x-hwc::alert>

<x-hwc::alert variant="destructive">
    <x-hwc::alert.title>Upload failed</x-hwc::alert.title>
    <x-hwc::alert.description>The file could not be processed.</x-hwc::alert.description>
    <x-hwc::alert.action>
        <x-hwc::button variant="outline" size="sm">Retry</x-hwc::button>
    </x-hwc::alert.action>
</x-hwc::alert>

<x-hwc::alert class="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-50">
    <x-hwc::alert.title>Scheduled maintenance</x-hwc::alert.title>
    <x-hwc::alert.description>
        Deployments will be paused for a few minutes tonight.
    </x-hwc::alert.description>
</x-hwc::alert>
```

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `variant` | `default` | `default` or `destructive`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `alert` | `div` with `role="alert"` | `alert` |
| `alert.title` | `div` | `alert-title` |
| `alert.description` | `div` | `alert-description` |
| `alert.action` | `div` | `alert-action` |

## Styling Hooks

- `data-slot="alert"`
- `data-variant="default|destructive"`
- `data-slot="alert-title"`
- `data-slot="alert-description"`
- `data-slot="alert-action"`
