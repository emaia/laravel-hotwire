# Clear Input

Adds an "X" button to clear an input value. The button appears automatically when the field has a value and hides when it is empty.

**Identifier:** `clear-input`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers clear-input`.

For Blade usage, prefer [`<hw:input type="search">`](../components/input.md#search), which activates this controller by
default, or `<hw:input clearable>` for another text-like type. The component's root is stable with or without clearing.

## Requirements

- No external dependencies.

## Basic Usage

```html
<div data-controller="clear-input" class="relative">
    <input
        type="text"
        name="search"
        data-clear-input-target="input"
        class="pr-8"
    />
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute right-2 top-1/2 -translate-y-1/2 hidden"
    >
        &times;
    </button>
</div>
```

The button is hidden by default (`hidden`) and appears via CSS when the input has a value and is focused or hovered.

## With Auto Submit On Clear

```html
<form data-controller="auto-submit">
    <div data-controller="clear-input" class="relative">
        <input
            type="search"
            name="q"
            data-clear-input-target="input"
            data-action="inputCleared->auto-submit#submit input->auto-submit#debouncedSubmit"
            class="pr-8"
        />
        <button
            type="button"
            data-clear-input-target="clearButton"
            class="clear-input-button absolute right-2 top-1/2 -translate-y-1/2 hidden"
        >
            &times;
        </button>
    </div>
</form>
```

When the user clicks "X", the controller dispatches native `input` first and then `inputCleared`. The `inputCleared` event triggers the form submit automatically. It uses `submit` so the cleared results show instantly, and because `submit` cancels a debounce still pending from typing, the clear produces a single request while typing stays debounced via `debouncedSubmit`.

## With A Pre-Filled Value

If the input already has a value when the page loads, the button appears immediately:

```html
<div data-controller="clear-input" class="relative">
    <input
        type="text"
        name="q"
        value="{{ request('q') }}"
        data-clear-input-target="input"
        class="pr-8"
    />
    <button
        type="button"
        data-clear-input-target="clearButton"
        class="clear-input-button absolute right-2 top-1/2 -translate-y-1/2 hidden"
    >
        &times;
    </button>
</div>
```

## Required CSS

The package foundation supplies the visibility rule. Mark the adjacent button with either the package slot or the
Stimulus target; the controller maintains `clear-input--touched` on the input:

```css
.clear-input--touched:focus + :is([data-slot="clear-input-button"], [data-clear-input-target~="clearButton"]),
.clear-input--touched:hover + :is([data-slot="clear-input-button"], [data-clear-input-target~="clearButton"]),
.clear-input--touched + :is([data-slot="clear-input-button"], [data-clear-input-target~="clearButton"]):hover {
    display: var(--clear-input-button-display, revert) !important;
}
```

Set `--clear-input-button-display` when the visible button needs a specific display mode. The Nova preset sets it to
`inline-flex` and owns the button's position and appearance.

## Behavior

The controller re-syncs the `clear-input--touched` class on every `turbo:render`. With `<hw:meta refresh />` (or `data-turbo-action="morph"`), idiomorph rewrites the input's `class` attribute from server HTML, which does not contain the runtime-added class, so the clear button would no longer appear over `old()`-restored values. The listener re-applies or removes the class based on the current input value after the morph completes.

Listeners are delegated from the controller root, so replacing a target or reconnecting the controller does not retain
listeners on old targets or duplicate clear events. Disabled and readonly inputs cannot be cleared, including disabled
state inherited from a fieldset. The foundation hides their clear button without duplicating immutable state onto it,
so clearing becomes available again when the native control becomes editable.

Escape clears a nonempty `type="search"` input through the same `input` / `inputCleared` event sequence as clicking the
button and stops that keypress from also closing an enclosing overlay. Empty inputs, composing input and keys already
prevented by another controller are left alone. A control such as Multi Select can keep its own Escape handling on the
search input before the event bubbles to this controller.

Drawer, Alert Dialog and the mobile Sidebar also defer their earlier Escape dismissal to an enhanced, nonempty and
editable search inside their own panel. Empty, readonly, disabled or unenhanced fields retain normal overlay dismissal.

Overlay coordination tracks the controller's connection separately from the visual touched class and reads the native
input's current value. Programmatic value changes without an `input` event therefore keep the same Escape behavior.
Keyboard events and `pageshow` resynchronize the visual state, including after browser history restoration; a Turbo morph
that retains the controller also retains its connection state.

Missing targets are tolerated during DOM replacement. Disconnecting or removing a target retires its enhancement state;
the native search cancel button remains available before enhancement or when the custom clear control is absent.

## Targets

| Target        | Description                        |
|---------------|------------------------------------|
| `input`       | The input to be cleared            |
| `clearButton` | The button that triggers the clear |

## Events

| Event          | Description                                         |
|----------------|-----------------------------------------------------|
| `input`        | Native input event fired after the value is cleared |
| `inputCleared` | Fired on the input after clearing. Bubbles.         |
