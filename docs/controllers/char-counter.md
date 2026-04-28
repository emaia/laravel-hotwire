# Char Counter

Displays a live character count for an input or textarea. Supports both count-up and countdown modes.

**Identifier:** `char-counter`  
**Install:** `php artisan hotwire:controllers char-counter`

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
<div data-controller="char-counter">
    <textarea
        name="bio"
        data-char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-char-counter-target="counter">0</span> characters
</div>
```

## Countdown mode

Requires a `maxlength` attribute on the input. Displays the number of remaining characters.

```html
<div data-controller="char-counter">
    <textarea
        name="bio"
        maxlength="160"
        data-char-counter-target="input"
        placeholder="Tell us about yourself..."
    ></textarea>
    <span data-char-counter-target="counter">160</span> characters remaining
</div>
```

Enable countdown mode by adding the `countdown` value:

```html
<div
    data-controller="char-counter"
    data-char-counter-countdown-value="true"
>
    <input
        type="text"
        name="title"
        maxlength="80"
        data-char-counter-target="input"
    />
    <span data-char-counter-target="counter">80</span> left
</div>
```

## With a form field component

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
