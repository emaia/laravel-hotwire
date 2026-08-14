# Slider

Keeps the visual fill of a native range input synchronized with its current value.

**Identifier:** `slider`

**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers slider`.

## Requirements

- No external dependencies.
- Mount on an `<input type="range">`.

## Actions

| Action          | Description                                             |
|-----------------|---------------------------------------------------------|
| `slider#update` | Recalculates the percentage and writes `--slider-value` |

## Basic Usage

```html
<input
    type="range"
    min="0"
    max="100"
    value="25"
    aria-label="Volume"
    data-controller="slider"
    data-action="input->slider#update"
>
```

On connect and input, the controller writes a clamped percentage:

```css
--slider-value: 25%;
```

Use the variable in a track gradient. The `<hw:slider>` component and Nova preset provide this wiring automatically.

## Form Reset

The controller listens to the owning form's native `reset` event and refreshes on the next animation frame, after the
browser has restored the input's default value. It also refreshes after `turbo:morph-element` and rebinds when a morph
changes the input's form owner. The listeners and any pending frame are removed in `disconnect()`.

The input remains fully interactive and submittable without this controller. `<hw:slider>` renders the initial
`--slider-value` server-side, so the fill is already correct on first paint and stays correct even if the controller
never loads — the controller only keeps it in sync as the value changes. On a bare `<input type="range">` without that
inline value, the fill stays at the preset default until the controller connects.
