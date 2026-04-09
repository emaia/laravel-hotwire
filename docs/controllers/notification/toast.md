# Toast

Fires a toast via [Sonner](https://sonner.emilkowal.ski/) on connect and removes the element from the DOM. Designed to be rendered by Turbo Stream, enabling flash messages without additional JavaScript.

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

The typical usage is via Turbo Stream at the end of a Laravel controller action:

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

```php
// In the Laravel controller, after saving:
return redirect()->back()->with('toast', ['message' => 'Saved!', 'type' => 'success']);
```

```html
<!-- resources/views/partials/toast.blade.php -->
@if (session('toast'))
    <div
        data-controller="notification--toast"
        data-notification--toast-message-value="{{ session('toast.message') }}"
        data-notification--toast-type-value="{{ session('toast.type', 'default') }}"
    ></div>
@endif
```

## Available types

| `type` | Behavior |
|--------|----------|
| `default` | Neutral toast |
| `success` | Green toast |
| `error` | Red toast |
| `warning` | Yellow toast |
| `info` | Blue toast |
