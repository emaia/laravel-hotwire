# Char Counter

Displays a live character count for an input or textarea. Use it to show typed characters or remaining characters next to fields with length guidance.

**Identifier:** `char-counter`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with `php artisan hotwire:controllers char-counter`.

## Requirements

- No external dependencies.

## Basic Usage

```html
<div data-controller="char-counter">
    <textarea
        name="bio"
        data-char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-char-counter-target="counter">0</span> characters
</div>
```

## Countdown Mode

Countdown mode requires a `maxlength` attribute on the input and displays the number of remaining characters.

```html
<div
    data-controller="char-counter"
    data-char-counter-countdown-value="true"
>
    <textarea
        name="bio"
        maxlength="160"
        data-char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-char-counter-target="counter">160</span> characters remaining
</div>
```

## With A Form Field Component

```html
<div data-controller="char-counter">
    <label for="summary">Summary</label>
    <input
        id="summary"
        type="text"
        name="summary"
        maxlength="100"
        data-char-counter-target="input"
    />
    <p class="text-sm text-gray-500">
        <span data-char-counter-target="counter">100</span> characters remaining
    </p>
</div>
```

## Behavior

In count-up mode, the counter displays the current input length. In countdown mode, it displays the remaining characters and requires `maxlength`.

The controller re-syncs the counter on every `turbo:render` event. When the page is morphed, such as after a validation redirect with `<hw:meta refresh />` or `data-turbo-action="morph"`, idiomorph preserves the controller and its targets but rewrites the counter span's `innerHTML` back to the server-rendered initial value, typically `"0"`. The listener re-runs `update()` so the counter reflects the current input value after the morph completes.

## Values

| Value       | Type      | Default | Description                                                                         |
|-------------|-----------|---------|-------------------------------------------------------------------------------------|
| `countdown` | `Boolean` | —       | When present, displays remaining characters instead of typed (requires `maxlength`) |

## Targets

| Target    | Description                              |
|-----------|------------------------------------------|
| `input`   | The input or textarea being counted      |
| `counter` | The element where the count is displayed |
