# Toggle Group

Coordinates a group of `toggle` buttons so single groups keep at most one pressed item and hidden inputs stay in sync.

**Identifier:** `toggle-group`
**Install:** `php artisan hotwire:controllers toggle-group`

## Requirements

- No external dependencies.
- Items are expected to also use the `toggle` controller.

## Targets

| Target | Description                                   |
|--------|-----------------------------------------------|
| `item` | Button controlled by the `toggle` controller. |

## Stimulus Values

| Value  | Type     | Default    | Description                                                                        |
|--------|----------|------------|------------------------------------------------------------------------------------|
| `type` | `String` | `multiple` | Use `single` to keep only one item pressed. Any other value behaves as `multiple`. |

## Actions

| Action              | Description                                              |
|---------------------|----------------------------------------------------------|
| `toggle-group#sync` | Reconcile group state after an item dispatches `change`. |

## Basic usage

```html
<div data-controller="toggle-group" data-action="change->toggle-group#sync" data-toggle-group-type-value="single">
    <input id="align-left-input" data-toggle-input type="hidden" name="alignment" value="left" disabled>
    <button
        type="button"
        data-controller="toggle"
        data-action="click->toggle#toggle"
        data-toggle-group-target="item"
        data-toggle-input-id-value="align-left-input"
        data-toggle-pressed-value="false"
        data-toggle-value-value="left"
        aria-pressed="false"
        data-state="off"
    >Left</button>
</div>
```

Most apps should use `<hw:toggle-group>` instead of wiring this controller manually.

## Behavior

For `type="single"`, clicking an off item turns off every other item. Clicking the currently pressed item clears the
group. For `type="multiple"`, items remain independent and the controller only syncs hidden inputs.

The controller does not install global listeners or timers, so `disconnect()` cleanup is not required.
