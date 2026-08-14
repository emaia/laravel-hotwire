# Toast

Hands a toast to the manager on connect and removes the element from the DOM. This is the low-level controller
behind `<hw:toast>`, and can be rendered directly when needed.

**Identifier:** `toast`

## Requirements

- The `toaster` controller on the page. If it has not connected yet the emission is buffered, so ordering in the
  document does not matter.

## Values

| Value         | Type     | Default     | Description                                                  |
|---------------|----------|-------------|--------------------------------------------------------------|
| `message`     | `String` | —           | Main toast message (required)                                |
| `description` | `String` | `null`      | Secondary text shown below the message                       |
| `type`        | `String` | `"default"` | Toast type: `default`, `success`, `error`, `warning`, `info` |
| `position`    | `String` | `""`        | Override the viewport position for this toast only           |
| `class-name`  | `String` | `""`        | Extra classes applied to the rendered toast                  |

Empty strings are treated as absent: `message` and `description` are omitted from the card rather than rendering a
blank row, which is what keeps the icon aligned when request input is forwarded straight into a stream macro.

## Basic Usage

```html
<div
    data-controller="toast"
    data-toast-message-value="Saved successfully!"
    data-toast-type-value="success"
></div>
```

The element is removed from the DOM immediately after the toast fires.

## With Turbo Stream

Add the viewport once in the application layout:

```html
<!-- resources/views/layouts/app.blade.php -->
<body>
    <div
        data-controller="toaster"
        data-toaster-position-value="top-end"
    ></div>

    @yield('content')
</body>
```

Then append a toast element from a Turbo Stream response:

```php
return turbo_stream()
    ->append('toaster', <<<'HTML'
        <div
            data-controller="toast"
            data-toast-message-value="Saved!"
            data-toast-type-value="success"
        ></div>
    HTML);
```

For session flash messages, use [`<hw:toast>`](../components/toast.md), which reads the supported Laravel session
keys and renders this controller for you.

## Available types

Type is communicated by a glyph, not by a coloured surface. Every card uses the popover tokens; `error` is the only
type that tints, because `destructive` is the only status colour in the token set.

| `type`    | Icon              |
|-----------|-------------------|
| `default` | none              |
| `success` | circle-check      |
| `error`   | octagon-x, tinted |
| `warning` | triangle-alert    |
| `info`    | info              |
