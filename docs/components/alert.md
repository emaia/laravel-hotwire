# Alert

Inline feedback block for status messages, warnings and contextual notices.

## Usage

```blade
<hw:alert>
    <hw:icon name="info" />
    <hw:alert.title>Heads up</hw:alert.title>
    <hw:alert.description>
        You can add components to your app using the installer.
    </hw:alert.description>
</hw:alert>

<hw:alert variant="destructive">
    <hw:alert.title>Upload failed</hw:alert.title>
    <hw:alert.description>The file could not be processed.</hw:alert.description>
    <hw:alert.action>
        <hw:button variant="outline" size="sm">Retry</hw:button>
    </hw:alert.action>
</hw:alert>

<hw:alert class="border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-50">
    <hw:alert.title>Scheduled maintenance</hw:alert.title>
    <hw:alert.description>
        Deployments will be paused for a few minutes tonight.
    </hw:alert.description>
</hw:alert>
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
