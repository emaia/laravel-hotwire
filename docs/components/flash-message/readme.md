# Flash Message

Displays Laravel session flash messages as toast notifications
via [@emaia/sonner](https://www.npmjs.com/package/@emaia/sonner).

The component automatically reads from the Laravel session (`success`, `error`, `errors`, `warning`, `info`) or accepts
an explicit message via props. Uses the `notification--toast` Stimulus controller which fires the toast on `connect()`
and removes the element from the DOM.

## Requirements

- `@emaia/sonner` installed in the project (`bun add @emaia/sonner`)
- Controllers published: `php artisan hotwire:controllers notification`

## Setup

The flash message system uses two Stimulus controllers that work together:

| Controller              | Identifier              | Responsibility                                               |
|-------------------------|-------------------------|--------------------------------------------------------------|
| `toaster_controller.js` | `notification--toaster` | Initializes the Sonner container (once)                      |
| `toast_controller.js`   | `notification--toast`   | Fires individual toasts and removes the element from the DOM |

### 1. Toaster container

Add the container in the main layout (typically before `</body>`). The `data-turbo-permanent` attribute ensures the
container persists across Turbo Drive navigations, preventing reinitialization:

```html

<div id="flash-container" data-controller="notification--toaster" data-turbo-permanent></div>
```

### 2. Flash Message component

Include the Blade component inside `<body>`, after the toaster container:

```html

<x-hwc::flash-message/>
```

### Full layout

```html
<!DOCTYPE html>
<html>
<head>...</head>
<body>
{{ $slot }}

<div id="flash-container" data-controller="notification--toaster" data-turbo-permanent></div>
<x-hwc::flash-message/>
</body>
</html>
```

## Usage via session

The component renders automatically when the session contains a flash message:

```php
// Controller
return redirect()->back()->with('success', 'Item created successfully!');
```

```php
// Other session types
return redirect()->back()->with('error', 'Failed to process.');
return redirect()->back()->with('warning', 'Warning: limit almost reached.');
return redirect()->back()->with('info', 'New version available.');
```

With validation errors, the first error from the `MessageBag` is shown automatically:

```php
// Form Request or validate() — shows the first error as a toast
$request->validate([
    'email' => 'required|email',
]);
```

## Explicit message

```html

<x-hwc::flash-message message="Operation completed" type="success"/>
```

## With description

```html

<x-hwc::flash-message
    message="Failed to save"
    description="Please check the required fields"
    type="error"
/>
```

## Props

| Prop          | Type      | Default | Description                                                                                    |
|---------------|-----------|---------|------------------------------------------------------------------------------------------------|
| `message`     | `?string` | `null`  | Toast message. If `null`, reads from session                                                   |
| `description` | `?string` | `null`  | Additional description shown below the message                                                 |
| `type`        | `?string` | `null`  | Toast type: `success`, `error`, `warning`, `info`, `default`. If `null`, detected from session |

## Supported session keys

| Session key           | Toast type                     |
|-----------------------|--------------------------------|
| `success`             | `success`                      |
| `error`               | `error`                        |
| `errors` (MessageBag) | `error` (uses the first error) |
| `warning`             | `warning`                      |
| `info`                | `info`                         |

Explicit props take priority over the session.

## Toaster — Stimulus Values

Configurable via `data-notification--toaster-*-value` on the container:

| Value             | Type      | Default           | Description                                                                                     |
|-------------------|-----------|-------------------|-------------------------------------------------------------------------------------------------|
| `close-button`    | `Boolean` | `true`            | Shows close button on each toast                                                                |
| `duration`        | `Number`  | `4000`            | Duration (ms) before the toast disappears                                                       |
| `expand`          | `Boolean` | `false`           | Toasts expanded by default                                                                      |
| `invert`          | `Boolean` | `false`           | Inverts the color scheme                                                                        |
| `position`        | `String`  | `"bottom-center"` | Position: `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right` |
| `rich-colors`     | `Boolean` | `true`            | Uses rich colors for types (success, error, etc.)                                               |
| `theme`           | `String`  | `"light"`         | Theme: `light`, `dark`, `system`                                                                |
| `visible-toasts`  | `Number`  | `3`               | Maximum number of toasts visible at once                                                        |
| `auto-disconnect` | `Boolean` | `false`           | Destroys the toaster when the controller disconnects                                            |

### Customization examples

Toasts at the top right with dark theme and 5 second duration:

```html

<div
    id="flash-container"
    data-controller="notification--toaster"
    data-notification--toaster-position-value="top-right"
    data-notification--toaster-theme-value="dark"
    data-notification--toaster-duration-value="5000"
    data-turbo-permanent
></div>
```

Without close button and with expanded toasts:

```html

<div
    id="flash-container"
    data-controller="notification--toaster"
    data-notification--toaster-close-button-value="false"
    data-notification--toaster-expand-value="true"
    data-turbo-permanent
></div>
```

## Toast — Stimulus Controller

**Identifier:** `notification--toast`

The controller calls `toast()` from `@emaia/sonner/vanilla` on connect and removes the element from the DOM. It
maintains no state or listeners.

## Turbo integration

- The container uses `data-turbo-permanent` to persist across navigations, keeping Sonner initialized.
- The `<x-hwc::flash-message>` component uses `data-turbo-temporary` to be removed from the Turbo Drive cache,
  preventing duplicate toasts on navigation.
