# Char Counter

Displays a live character count for an input or textarea. Supports both count-up and countdown modes.

**Identifier:** `form--char-counter`

## Requirements

- No external dependencies.

## Targets

| Target    | Description                              |
|-----------|------------------------------------------|
| `input`   | The input or textarea being counted      |
| `counter` | The element where the count is displayed |

## Stimulus Values

| Value       | Type      | Default | Description                                                                         |
|-------------|-----------|---------|-------------------------------------------------------------------------------------|
| `countdown` | `Boolean` | —       | When present, displays remaining characters instead of typed (requires `maxlength`) |

## Basic usage — count up

```html
<div data-controller="form--char-counter">
    <textarea
        name="bio"
        data-form--char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-form--char-counter-target="counter">0</span> characters
</div>
```

## Countdown mode

Requires a `maxlength` attribute on the input. Displays the number of remaining characters.

```html
<div data-controller="form--char-counter">
    <textarea
        name="bio"
        maxlength="160"
        data-form--char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-form--char-counter-target="counter">160</span> characters remaining
</div>
```

Enable countdown mode by adding the `countdown` value:

```html
<div
    data-controller="form--char-counter"
    data-form--char-counter-countdown-value="true"
>
    <input
        type="text"
        name="title"
        maxlength="80"
        data-form--char-counter-target="input"
    />
    <span data-form--char-counter-target="counter">80</span> left
</div>
```

## With a form field component

```html
<div data-controller="form--char-counter">
    <label for="summary">Summary</label>
    <input
        id="summary"
        type="text"
        name="summary"
        maxlength="100"
        data-form--char-counter-target="input"
    />
    <p class="text-sm text-gray-500">
        <span data-form--char-counter-target="counter">100</span> characters remaining
    </p>
</div>
```
