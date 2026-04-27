# Flash Message

Fires a single toast notification — either reading from the Laravel session or from explicit props. Designed to be
rendered inside a layout alongside [`<x-hwc::flash-container />`](../flash-container/readme.md), which hosts the
Sonner instance the toasts are emitted into.

Internally the component maps to the `toast` Stimulus controller (`toast_controller.js`), which calls `toast()`
from `@emaia/sonner/vanilla` on connect and removes the element from the DOM. It maintains no state or listeners.

## Requirements

- `@emaia/sonner` installed in the project
- `<x-hwc::flash-container />` rendered once in the layout
- Controller published: `php artisan hotwire:controllers toast`

> `php artisan hotwire:check` detects all requirements automatically — and `--fix` publishes the missing controllers
> and adds `@emaia/sonner` to your `package.json` `devDependencies` in one go.

## Setup

Place `<x-hwc::flash-message />` once in your main layout, after `<x-hwc::flash-container />`:

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

### Supported session keys

| Session key           | Toast type                     |
|-----------------------|--------------------------------|
| `success`             | `success`                      |
| `error`               | `error`                        |
| `errors` (MessageBag) | `error` (uses the first error) |
| `warning`             | `warning`                      |
| `info`                | `info`                         |

Explicit props take priority over the session.

## Turbo integration

`<x-hwc::flash-message />` uses `data-turbo-temporary`, so cached pages don't replay toasts on back/forward.

Turbo Streams can append rendered `<x-hwc::flash-message />` markup to the container:

```php
use Illuminate\Support\Facades\Blade;

return turbo_stream()->append('flash-container', Blade::render(
    '<x-hwc::flash-message :message="$message" type="success" />',
    ['message' => 'Saved!'],
));
```

## See also

- [`<x-hwc::flash-container />`](../flash-container/readme.md) — hosts the Sonner instance and exposes its config
