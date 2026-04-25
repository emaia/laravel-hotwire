# Flash Container

Initializes the [Sonner](https://www.npmjs.com/package/@emaia/sonner) toaster once per page and persists it across
Turbo Drive navigations. It's the host element for every toast fired by [`<x-hwc::flash-message />`](../flash-message/readme.md)
or by appended Turbo Streams.

Internally the component maps to the `toaster` Stimulus controller (`toaster_controller.js`), which calls Sonner's
`Toaster()` factory on connect.

## Requirements

- `@emaia/sonner` installed in the project
- Controller published: `php artisan hotwire:controllers toaster`

> `php artisan hotwire:check` detects both requirements automatically — and `--fix` publishes the missing controllers
> and adds `@emaia/sonner` to your `package.json` `devDependencies` in one go.

## Setup

Place the container once in your main layout (typically before `</body>`):

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
use Illuminate\Support\Facades\Blade;

return turbo_stream()->append('flash-container', Blade::render(
    '<x-hwc::flash-message :message="$message" type="success" />',
    ['message' => 'Saved!'],
));
```

## Props

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

## Customization examples

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

## Escape hatch

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

## Turbo integration

- The container uses `data-turbo-permanent` by default — Sonner stays initialized across Turbo Drive navigations.
- Turbo Streams can append rendered `<x-hwc::flash-message />` markup to the container — see the
  `Blade::render()` example in the [Setup](#setup) section.

## See also

- [`<x-hwc::flash-message />`](../flash-message/readme.md) — fires individual toasts from session or props
