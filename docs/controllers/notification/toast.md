# Toast

Fires a toast via [Sonner](https://sonner.emilkowal.ski/) on connect and removes the element from the DOM. This is the
low-level controller used by `<x-hwc::flash-message>` and can also be rendered directly when needed.

**Identifier:** `notification--toast`

## Requirements

- `@emaia/sonner`
- The `notification--toaster` controller initialized on the page (creates the Sonner container).

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `message` | `String` | — | Main toast message (required) |
| `description` | `String` | `null` | Secondary text shown below the message |
| `type` | `String` | `"default"` | Toast type: `default`, `success`, `error`, `warning`, `info` |

## Basic usage

```html
<div
    data-controller="notification--toast"
    data-notification--toast-message-value="Saved successfully!"
    data-notification--toast-type-value="success"
></div>
```

The element is removed from the DOM immediately after the toast fires.

## With Turbo Stream

Add the toaster container once in the application layout:

```html
<!-- resources/views/layouts/app.blade.php -->
<body>
    <div
        data-controller="notification--toaster"
        data-notification--toaster-position-value="top-right"
    ></div>

    @yield('content')
</body>
```

Then append a toast element from a Turbo Stream response:

```php
return turbo_stream()
    ->append('flash-container', <<<'HTML'
        <div
            data-controller="notification--toast"
            data-notification--toast-message-value="Saved!"
            data-notification--toast-type-value="success"
        ></div>
    HTML);
```

For session flash messages, use [`<x-hwc::flash-message>`](../../components/flash-message/readme.md), which reads the
supported Laravel session keys and renders this controller for you.

## Available types

| `type` | Behavior |
|--------|----------|
| `default` | Neutral toast |
| `success` | Green toast |
| `error` | Red toast |
| `warning` | Yellow toast |
| `info` | Blue toast |
