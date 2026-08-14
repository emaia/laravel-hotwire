# Toggle

Synchronizes a two-state button with `aria-pressed`, `data-state` and an optional hidden input. Most apps should use `<hw:toggle>` instead of wiring this controller manually.

**Identifier:** `toggle`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers toggle`.

## Requirements

- No external dependencies.

## Basic Usage

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

The controller keeps `aria-pressed` and `data-state` in sync.

## Listening For Changes

The controller dispatches a bubbling `change` event after user toggles:

```js
button.addEventListener("change", (event) => {
    console.log(event.detail.pressed)
})
```

## Behavior

Calling `toggle#toggle` toggles the pressed state unless the element is disabled. When configured with `inputId`, the controller also synchronizes the associated hidden input.

## Values

| Value     | Type      | Default | Description                                     |
|-----------|-----------|---------|-------------------------------------------------|
| `pressed` | `Boolean` | `false` | Current pressed state                           |
| `value`   | `String`  | `"on"`  | Value written to the associated hidden input    |
| `inputId` | `String`  | —       | Hidden input id to synchronize when form-backed |

## Actions

| Action          | Description                                             |
|-----------------|---------------------------------------------------------|
| `toggle#toggle` | Toggles the pressed state unless the element is disabled |
