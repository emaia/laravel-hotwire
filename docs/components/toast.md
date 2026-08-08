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

### Convenience macro

`TurboStreamBuilder` is `Macroable` — register a `toast()` shortcut once in a service provider and reuse
it everywhere:

```php
// app/Providers/AppServiceProvider.php
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    TurboStreamBuilder::macro('toast', function (string $type, string $message, ?string $description = null, ?string $position = null) {
        return $this->append('toaster', Blade::render(
            '<hw:toast :type="$type" :message="$message" :description="$description" :position="$position" />',
            compact('type', 'message', 'description', 'position'),
        ));
    });
}
```

Then any controller or stream chain becomes a one-liner. Note the empty-string defaults below: request input
forwarded straight into the macro often arrives empty, and the manager omits a title or description it was handed
as an empty string rather than rendering a blank row.

```php
return turbo_stream()->toast('success', 'Saved!');

// with an optional description
return turbo_stream()->toast('error', 'Failed to save', 'Check the required fields');

// override the position for this toast only
return turbo_stream()->toast('warning', 'Session expires in 5 min', position: 'top-center');

// or chained with other streams
return turbo_stream()
    ->refresh(method: 'morph')
    ->toast('error', 'Could not favorite this post.')
    ->withResponse(403);
```

## See also

- [`<hw:toaster />`](./toaster.md) — hosts the toast stack and exposes its config
- [`window.toaster`](./toaster.md#emitting-from-javascript) — emit from your own JavaScript, no import required
