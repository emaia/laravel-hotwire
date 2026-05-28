# Clear Input

Adds an "X" button to clear an input value. The button appears automatically when the field has a value and hides when
it is empty.

**Identifier:** `clear-input`  
**Install:** `php artisan hotwire:controllers clear-input`

## Requirements

- No external dependencies.

## Targets

| Target        | Description                        |
|---------------|------------------------------------|
| `input`       | The input to be cleared            |
| `clearButton` | The button that triggers the clear |

## Events

| Event          | Description                                 |
|----------------|---------------------------------------------|
| `inputCleared` | Fired on the input after clearing. Bubbles. |

## Basic usage

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

## Required CSS

The controller automatically injects the styles that control button visibility:

```css
/* Automatically injected by the controller */
.clear-input--touched:focus + .clear-input-button,
.clear-input--touched:hover + .clear-input-button,
.clear-input--touched + .clear-input-button:hover {
    display: block !important;
}
```

The `clear-input--touched` class is added/removed automatically as the input receives or loses a value.

## With auto-submit on clear

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

When the user clicks "X", the `inputCleared` event triggers the form submit automatically. It uses `submit`
so the cleared results show instantly — and because `submit` cancels a debounce still pending from typing,
the clear produces a single request — while typing stays debounced via `debouncedSubmit`.

## With a pre-filled value

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

## Turbo morph support

The controller re-syncs the `clear-input--touched` class on every `turbo:render`. With `@turboRefreshMethod('morph')` (
or `data-turbo-action="morph"`), idiomorph rewrites the input's `class` attribute from server HTML — which does not
contain the runtime-added class — so the clear button would no longer appear over `old()`-restored values. The listener
re-applies (or removes) the class based on the current input value after the morph completes.
