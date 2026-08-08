# Toast

Fires a single toast notification — either reading from the Laravel session or from explicit props. Rendered in a
layout alongside [`<hw:toaster />`](./toaster.md), which hosts the stack the toasts are drawn into.

The component maps to the `toast` Stimulus controller, which hands the payload to the toast manager on connect and
removes itself from the DOM. It keeps no state and no listeners, and needs no third-party package.

An emission that arrives before the viewport has connected is buffered and drained as soon as it does, so it does
not matter whether this component sits above or below `<hw:toaster />` in the document, nor whether it arrives over
a Turbo Stream before the layout has hydrated.

## Requirements

- `<hw:toaster />` rendered once in the layout

## Setup

Place `<hw:toast />` once in your main layout, after `<hw:toaster />`:

```html
<!DOCTYPE html>
<html>
<head>...</head>
<body>
{{ $slot }}

<hw:toaster />
<hw:toast />
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
<hw:toast message="Operation completed" type="success"/>
```

## With description

```html
<hw:toast
    message="Failed to save"
    description="Please check the required fields"
    type="error"
/>
```

## Custom position

Override the toaster's default position for a single toast. The container stays in one place; only this message
appears in the chosen corner:

```html
<hw:toast message="Session expires in 5 min" type="warning" position="top-center"/>
```

Accepted values: `top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end`.

The second segment is a *logical* alignment, matching `align` on Popover, Dropdown and Hover Card: `start` and `end`
follow the document's writing direction, so a stack anchored to `-end` sits on the left in a right-to-left document.

## Props

| Prop          | Type      | Default | Description                                                                                    |
|---------------|-----------|---------|------------------------------------------------------------------------------------------------|
| `message`     | `?string` | `null`  | Toast message. If `null`, reads from session                                                   |
| `description` | `?string` | `null`  | Additional description shown below the message                                                 |
| `type`        | `?string` | `null`  | Toast type: `success`, `error`, `warning`, `info`, `default`. If `null`, detected from session |
| `position`    | `?string` | `null`  | Override the toaster position for this toast only: `top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end`. If `null`, uses the [`<hw:toaster />`](./toaster.md) default |
| `class-name`  | `?string` | `null`  | Extra classes applied to the rendered toast element |

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

`<hw:toast />` uses `data-turbo-temporary`, so cached pages don't replay toasts on back/forward.

Turbo Streams can append rendered `<hw:toast />` markup to the container:

```php
use Illuminate\Support\Facades\Blade;

return turbo_stream()->append('toaster', Blade::render(
    '<hw:toast :message="$message" type="success" />',
    ['message' => 'Saved!'],
));
```

### The `toast()` stream macro

The package registers a `toast()` macro on `TurboStreamBuilder`, so no setup is required:

```php
return turbo_stream()->toast('success', 'Saved!');

// with an optional description
return turbo_stream()->toast('error', 'Failed to save', 'Check the required fields');

// override the position for this toast only
return turbo_stream()->toast('warning', 'Session expires in 5 min', position: 'top-center');

// append into a viewport with a custom id
return turbo_stream()->toast('success', 'Saved!', target: 'my-toaster');

// or chained with other streams
return turbo_stream()
    ->refresh(method: 'morph')
    ->toast('error', 'Could not favorite this post.')
    ->withResponse(403);
```

| Parameter     | Default   | Description                                                     |
|---------------|-----------|-----------------------------------------------------------------|
| `$type`       | —         | `success`, `error`, `warning`, `info` or `default`              |
| `$message`    | —         | Toast message                                                   |
| `$description`| `null`    | Secondary text                                                  |
| `$position`   | `null`    | Overrides the viewport position for this toast                  |
| `$target`     | `toaster` | Id of the viewport to append into — match your `<hw:toaster id>` |

Empty strings are treated as absent, which matters when the values come straight from request input:

```php
return turbo_stream()->toast(
    $request->input('type', 'success'),
    $request->input('message', ''),
    description: $request->input('description', ''),
);
```

If your application already defines a `toast` macro, yours wins — the package only registers its own when the name
is free.

## See also

- [`<hw:toaster />`](./toaster.md) — hosts the toast stack and exposes its config
- [`window.toaster`](./toaster.md#emitting-from-javascript) — emit from your own JavaScript, no import required
