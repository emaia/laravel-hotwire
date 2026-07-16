# Toggle

Synchronizes a two-state button with `aria-pressed`, `data-state` and an optional hidden input.

**Identifier:** `toggle`  
**Install:** `php artisan hotwire:controllers toggle`

## Requirements

- No external dependencies.

## Actions

| Action          | Description                                            |
|-----------------|--------------------------------------------------------|
| `toggle#toggle` | Toggles the pressed state unless the element is disabled |

## Stimulus Values

| Value     | Type      | Default | Description                                      |
|-----------|-----------|---------|--------------------------------------------------|
| `pressed` | `Boolean` | `false` | Current pressed state                            |
| `value`   | `String`  | `"on"`  | Value written to the associated hidden input     |
| `inputId` | `String`  | —       | Hidden input id to synchronize when form-backed  |

## Basic usage

```html
<button
    type="button"
    data-controller="toggle"
    data-action="click->toggle#toggle"
    data-toggle-pressed-value="false"
    aria-pressed="false"
    data-state="off"
>
    Featured
</button>
```

The controller keeps `aria-pressed` and `data-state` in sync and dispatches a bubbling `change` event after user toggles:

```js
button.addEventListener("change", (event) => {
    console.log(event.detail.pressed)
})
```

Most apps should use `<hw:toggle>` instead of wiring this controller manually.
