# Log

Utility controller for debugging. Logs Stimulus events to the browser console.

**Identifier:** `dev--log`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `dev--log#log` | Logs the event to `console.log` |

## Usage — click debug

```html
<button
    data-controller="dev--log"
    data-action="click->dev--log#log"
>
    Click to see in console
</button>
```

## Turbo event debug

```html
<turbo-frame
    id="my-frame"
    data-controller="dev--log"
    data-action="turbo:before-fetch-request->dev--log#log turbo:frame-render->dev--log#log"
>
    ...
</turbo-frame>
```

## Input debug

```html
<input
    type="text"
    data-controller="dev--log"
    data-action="input->dev--log#log focus->dev--log#log blur->dev--log#log"
/>
```

## Console output

```
Logging event...
Event: PointerEvent { type: 'click', target: button, ... }
```
