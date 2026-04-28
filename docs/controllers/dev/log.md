# Log

Utility controller for debugging. Logs Stimulus events to the browser console.

**Identifier:** `log`
**Install:** `php artisan hotwire:controllers dev/log`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `log#log` | Logs the event to `console.log` |

## Usage — click debug

```html
<button
    data-controller="log"
    data-action="click->log#log"
>
    Click to see in console
</button>
```

## Turbo event debug

```html
<turbo-frame
    id="my-frame"
    data-controller="log"
    data-action="turbo:before-fetch-request->log#log turbo:frame-render->log#log"
>
    ...
</turbo-frame>
```

## Input debug

```html
<input
    type="text"
    data-controller="log"
    data-action="input->log#log focus->log#log blur->log#log"
/>
```

## Console output

```
Logging event...
Event: PointerEvent { type: 'click', target: button, ... }
```
