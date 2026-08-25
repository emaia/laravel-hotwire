# Toast

Fires a single toast notification from explicit props — the unit a Turbo Stream appends, and the escape hatch for
firing a toast from anywhere in a template. It draws into the stack hosted by [`<hw:toaster />`](./toaster.md).

For the ordinary case — a redirect carrying a flash message — you do not need this component at all:
[`<hw:toaster />` reads the session itself](./toaster.md#session-flash).

The component maps to the `toast` Stimulus controller, which hands the payload to the toast manager on connect and
removes itself from the DOM. It keeps no state and no listeners, and needs no third-party package.

An emission that arrives before the viewport has connected is buffered and drained as soon as it does, so it does
not matter whether this component sits above or below `<hw:toaster />` in the document, nor whether it arrives over
a Turbo Stream before the layout has hydrated.

## Requirements

- `<hw:toaster />` rendered once in the layout

## Usage via session

With no `message` prop the component falls back to the session, using the same keys and the same precedence as the
viewport — see [Session flash](./toaster.md#session-flash) for the table.

```php
return redirect()->back()->with('success', 'Item created successfully!');
```

Since `<hw:toaster />` already does this, placing `<hw:toast />` in a layout is redundant. It is not harmful: the
message is claimed once per request, so whichever renders first fires it and the other stays silent. Reach for the
standalone tag when you want the flash rendered somewhere other than next to the viewport, and set
`<hw:toaster :flash="false" />` to make the split explicit.

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
| `message`     | `?string` | `null`  | Toast message. If `null`, claims the session flash                                             |
| `description` | `?string` | `null`  | Additional description shown below the message                                                 |
| `type`        | `?string` | `null`  | Toast type: `success`, `error`, `warning`, `info`, `default`. If `null`, detected from session |
| `position`    | `?string` | `null`  | Override the toaster position for this toast only: `top-start`, `top-center`, `top-end`, `bottom-start`, `bottom-center`, `bottom-end`. If `null`, uses the [`<hw:toaster />`](./toaster.md) default |
| `class-name`  | `?string` | `null`  | Extra classes applied to the rendered toast element |

### Supported session keys

See [Session flash](./toaster.md#session-flash) on the viewport for the full table — `toast`, `success`, `error`,
`errors`, `warning` and `info`, read in that order.

Explicit props take priority over the session. An explicit `message` also means the component does *not* claim the
flashed one, which is left for the viewport (or a later `<hw:toast />`) to render; only the `type` still falls back to
it.

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

The same macro exists on `RedirectResponse`, with the same `type`, `message`, `description` and `position`, so a
controller that answers both a frame and a full redirect writes the call once per branch — see
[Session flash](./toaster.md#session-flash).

## See also

- [`<hw:toaster />`](./toaster.md) — hosts the toast stack and exposes its config
- [`window.toaster`](./toaster.md#emitting-from-javascript) — emit from your own JavaScript, no import required

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="toast-trigger"`
