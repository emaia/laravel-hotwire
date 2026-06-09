# Password Visibility

Toggles a password input between hidden and visible. Keeps the toggle button's `aria-pressed` and `aria-label`
in sync, and dispatches a `password-visibility:change` event so external code (icon swap, analytics) can react
without coupling to the DOM.

**Identifier:** `password-visibility`  
**Install:** `php artisan hotwire:controllers password-visibility`

## Requirements

- No external dependencies.

## Targets

| Target   | Description                                                      |
|----------|------------------------------------------------------------------|
| `input`  | The password input. Required.                                    |
| `button` | The visibility toggle button. Optional — used to mirror ARIA state. |

## Values

| Value         | Type   | Default          | Description                                          |
|---------------|--------|------------------|------------------------------------------------------|
| `show-label`  | String | `Show password`  | `aria-label` applied to the button while hidden.     |
| `hide-label`  | String | `Hide password`  | `aria-label` applied to the button while visible.    |

## Actions

| Action   | Description                                                                |
|----------|----------------------------------------------------------------------------|
| `toggle` | Flips between hidden and visible.                                          |
| `show`   | Forces visibility on. Idempotent — no event if already visible.            |
| `hide`   | Forces visibility off. Idempotent — no event if already hidden.            |

## Events

| Event                        | Detail              | Description                                              |
|------------------------------|---------------------|----------------------------------------------------------|
| `password-visibility:change` | `{ visible: bool }` | Fired on the controller element when the state changes.  |

## Behavior on connect

On every `connect()` the input is forced back to `type="password"` — visibility is not persisted across
Turbo morphs or Drive navigations. This is intentional: if the form re-renders after a failed submit, the
user should not see the password they just typed displayed in plain text.

## Basic usage

```html

<div data-controller="password-visibility" class="relative">
    <input
        type="password"
        name="password"
        autocomplete="current-password"
        data-password-visibility-target="input"
        class="pr-10"
    />
    <button
        type="button"
        data-password-visibility-target="button"
        data-action="password-visibility#toggle"
        class="absolute right-2 top-1/2 -translate-y-1/2"
        aria-label="Show password"
    >
        👁
    </button>
</div>
```

The `aria-label` you write in the markup is replaced by the value-driven label as soon as the controller
connects, so it only matters for users who load the page with JavaScript disabled.

## Localized labels

```html

<div
    data-controller="password-visibility"
    data-password-visibility-show-label-value="Mostrar senha"
    data-password-visibility-hide-label-value="Ocultar senha"
>
    <input
        type="password"
        name="password"
        data-password-visibility-target="input"
    />
    <button
        type="button"
        data-password-visibility-target="button"
        data-action="password-visibility#toggle"
    >...</button>
</div>
```

## Swapping icons via a companion controller

The controller does not render or swap icons — it only manages state. Wire a small companion
controller to the `password-visibility:change` event and let it flip whatever icons you ship.

```html

<div data-controller="password-visibility password-visibility-icon"
     data-action="password-visibility:change->password-visibility-icon#swap">
    <input type="password" data-password-visibility-target="input" />
    <button
        type="button"
        data-password-visibility-target="button"
        data-action="password-visibility#toggle"
    >
        <svg data-password-visibility-icon-target="show" class="h-5 w-5">...</svg>
        <svg data-password-visibility-icon-target="hide" class="h-5 w-5" hidden>...</svg>
    </button>
</div>
```

```js
// resources/js/controllers/password_visibility_icon_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["show", "hide"];

    swap(event) {
        this.showTarget.hidden = event.detail.visible;
        this.hideTarget.hidden = !event.detail.visible;
    }
}
```

Both controllers live on the same element, so the `password-visibility:change` event fires
exactly where the companion's action listener is declared — no bubbling, no `event.target`
querying. Swap `hidden` for a class toggle if your icon library needs it.

## Pairing with `password` and `password_confirmation`

Each input needs its own controller instance. Mount two separate containers — one per input — so the
toggles stay independent.

```html

<div data-controller="password-visibility">
    <input type="password" name="password" data-password-visibility-target="input" />
    <button type="button" data-password-visibility-target="button"
            data-action="password-visibility#toggle">👁</button>
</div>

<div data-controller="password-visibility">
    <input type="password" name="password_confirmation" data-password-visibility-target="input" />
    <button type="button" data-password-visibility-target="button"
            data-action="password-visibility#toggle">👁</button>
</div>
```
