# Flash Message

Displays Laravel session flash messages as toast notifications
via [@emaia/sonner](https://www.npmjs.com/package/@emaia/sonner).

The flash system is composed of **two** Blade components:

- `<x-hwc::flash-container />` — initializes Sonner once per page, persists across Turbo Drive navigations.
- `<x-hwc::flash-message />` — fires a single toast, reading from the Laravel session or from explicit props.

Internally each maps to a dedicated Stimulus controller:

| Controller              | Identifier              | Used by                    | Responsibility                                               |
|-------------------------|-------------------------|----------------------------|--------------------------------------------------------------|
| `toaster_controller.js` | `toaster` | `<x-hwc::flash-container>` | Initializes the Sonner container (once)                      |
| `toast_controller.js`   | `toast`   | `<x-hwc::flash-message>`   | Fires individual toasts and removes the element from the DOM |

## Requirements

- `@emaia/sonner` installed in the project
- Controllers published: `php artisan hotwire:controllers notification`

> `php artisan hotwire:check` detects both requirements automatically — and `--fix` publishes the missing controllers
> and adds `@emaia/sonner` to your `package.json` `devDependencies` in one go.

## Setup

Add the container once in your main layout (typically before `</body>`), then render `<x-hwc::flash-message />`
after it:

```html
<!DOCTYPE html>
<html>
<head>...</head>
<body>
{{ $slot }}

<x-hwc::flash-container />
<x-hwc::flash-message />
</body>
</html>
```

The container defaults to `id="flash-container"` and ships with `data-turbo-permanent`, so it survives Turbo Drive
navigations and keeps the Sonner instance alive. The default id also lets you target it from Turbo Streams:

```php
return turbo_stream()->append('flash-container', view('partials.flash-message', [
    'message' => 'Saved!',
    'type' => 'success',
]));
```

## Usage via session

The `<x-hwc::flash-message />` component renders automatically when the session contains a flash message:

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

## `<x-hwc::flash-message />` props

| Prop          | Type      | Default | Description                                                                                    |
|---------------|-----------|---------|------------------------------------------------------------------------------------------------|
| `message`     | `?string` | `null`  | Toast message. If `null`, reads from session                                                   |
| `description` | `?string` | `null`  | Additional description shown below the message                                                 |
| `type`        | `?string` | `null`  | Toast type: `success`, `error`, `warning`, `info`, `default`. If `null`, detected from session |

### Supported session keys

| Session key           | Toast type                     |
|-----------------------|--------------------------------|
| `success`             | `success`                      |
| `error`               | `error`                        |
| `errors` (MessageBag) | `error` (uses the first error) |
| `warning`             | `warning`                      |
| `info`                | `info`                         |

Explicit props take priority over the session.

## `<x-hwc::flash-container />` props

Props below map to Sonner's [`ToasterConfig`](https://github.com/emilkowalski/sonner). Nullable props are only
emitted when you set them, so Sonner's own defaults still apply.

| Prop                   | Type      | Default           | Description                                                                                     |
|------------------------|-----------|-------------------|-------------------------------------------------------------------------------------------------|
| `id`                   | `string`  | `flash-container` | Element id — also used as the default target for Turbo Stream appends                           |
| `position`             | `string`  | `bottom-center`   | `top-left`, `top-center`, `top-right`, `bottom-left`, `bottom-center`, `bottom-right`           |
| `theme`                | `string`  | `light`           | `light`, `dark`, `system`                                                                       |
| `duration`             | `int`     | `4000`            | Duration in ms before the toast disappears                                                      |
| `visible-toasts`       | `int`     | `3`               | Maximum number of toasts visible at once                                                        |
| `close-button`         | `bool`    | `true`            | Shows close button on each toast                                                                |
| `rich-colors`          | `bool`    | `true`            | Uses rich colors for types (success, error, etc.)                                               |
| `expand`               | `bool`    | `false`           | Toasts expanded by default                                                                      |
| `invert`               | `bool`    | `false`           | Inverts the color scheme                                                                        |
| `auto-disconnect`      | `bool`    | `false`           | Destroys the toaster when the controller disconnects                                            |
| `turbo-permanent`      | `bool`    | `true`            | Renders `data-turbo-permanent` on the container                                                 |
| `class`                | `string`  | `''`              | CSS class applied to the container `<div>` itself                                               |
| `gap`                  | `?int`    | `null`            | Vertical gap between toasts (px)                                                                |
| `hotkey`               | `?string` | `null`            | Keyboard shortcut to focus toasts (e.g. `alt+T`, `alt+KeyT`) — comma/space separated            |
| `dir`                  | `?string` | `null`            | `ltr`, `rtl`, `auto`                                                                            |
| `offset`               | `?string` | `null`            | Edge offset — `"16px"` or JSON like `{"top":"20px"}`                                            |
| `mobile-offset`        | `?string` | `null`            | Same shape as `offset`, applied on mobile                                                       |
| `swipe-directions`     | `?string` | `null`            | Allowed swipe-to-dismiss directions — comma separated (`left,right,top,bottom`)                 |
| `class-name`           | `?string` | `null`            | Forwarded to Sonner's `className` (applied to the toast list)                                   |
| `container-aria-label` | `?string` | `null`            | `aria-label` on the Sonner container                                                            |
| `custom-aria-label`    | `?string` | `null`            | `aria-label` used for each toast                                                                |

### Customization examples

Top-right, dark theme, 5s duration:

```html
<x-hwc::flash-container
    position="top-right"
    theme="dark"
    :duration="5000"
/>
```

Expanded toasts, no close button:

```html
<x-hwc::flash-container
    :close-button="false"
    :expand="true"
/>
```

Offset tuning with hotkey and swipe directions:

```html
<x-hwc::flash-container
    offset='{"top":"20px","right":"20px"}'
    mobile-offset="12px"
    hotkey="alt+T"
    swipe-directions="left,right"
/>
```

Custom id (useful if you need more than one target, or want a different Turbo Stream anchor):

```html
<x-hwc::flash-container id="my-toaster" />
```

### Escape hatch

If you need a Sonner option that isn't exposed as a prop, drop down to the controller directly — the component
is just a thin wrapper:

```html
<div
    id="flash-container"
    data-controller="toaster"
    data-toaster-position-value="top-right"
    data-turbo-permanent
></div>
```

## Toast — Stimulus Controller

**Identifier:** `toast`

The controller calls `toast()` from `@emaia/sonner/vanilla` on connect and removes the element from the DOM. It
maintains no state or listeners.

## Turbo integration

- `<x-hwc::flash-container />` uses `data-turbo-permanent` by default — Sonner keeps initialized across Turbo Drive
  navigations.
- `<x-hwc::flash-message />` uses `data-turbo-temporary`, so cached pages don't replay toasts on back/forward.
- Turbo Streams can append rendered `<x-hwc::flash-message />` partials to the container:
  `turbo_stream()->append('flash-container', view('partials.flash-message', [...]))`.
