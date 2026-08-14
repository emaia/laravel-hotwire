# Slider

Native scalar range input with Laravel field integration and a shadcn Base Nova-inspired visual treatment.

`<hw:slider>` keeps browser pointer, touch, keyboard, accessibility and form submission behavior. The filled portion of
the track is rendered server-side, so it is correct on first paint; Stimulus only keeps it in sync as the value changes.

## Quick example

```blade
<hw:field name="temperature" label="Temperature" description="Choose a value from 0 to 100.">
    <hw:slider :value="$temperature" :min="0" :max="100" :step="1" />
</hw:field>
```

## Props

| Prop                | Type                       | Default             | Description                                                        |
|---------------------|----------------------------|---------------------|--------------------------------------------------------------------|
| `name`              | `string\|null`             | —                   | Input name. Inherited from `<hw:field>` when omitted               |
| `id`                | `string\|null`             | derived from `name` | Override the auto-derived id                                       |
| `value`             | `mixed`                    | `null`              | Initial scalar value, merged with old input unless `old` is false  |
| `min`               | `int\|float\|string\|null` | browser default     | Minimum value. The native default is `0`                           |
| `max`               | `int\|float\|string\|null` | browser default     | Maximum value. The native default is `100`                         |
| `step`              | `int\|float\|string\|null` | browser default     | Step interval, including decimal values or `any`                   |
| `orientation`       | `horizontal\|vertical`    | `horizontal`        | Visual and interaction orientation                                 |
| `errorKey`          | `string\|null`             | derived from `name` | Override the Laravel validation key                                |
| `old`               | `bool`                     | `true`              | Restore the value from flashed old input                           |
| `auto-submit`       | `bool\|string`             | `false`             | Add auto-submit wiring; sliders default to debounced input submit  |
| `auto-submit-delay` | `int\|string\|null`        | `null`              | Per-field debounce override                                        |
| `class`             | `string`                   | `""`                | Merged on the native range input                                   |
| `stimulus`          | `Htmlable\|null`           | `null`              | Fluent Stimulus attributes merged with the internal controller     |

Any other native attribute such as `disabled`, `form`, `list`, `dir`, `aria-*` or `data-*` passes through to the input.

When `value` is omitted, the browser chooses the native midpoint. Omitting `min`, `max` and `step` preserves the native
defaults instead of adding redundant HTML attributes.

## Accessible Name

Every slider needs an accessible name. Prefer a visible `<hw:field>` label, or add `aria-label`/`aria-labelledby` when
the control has no visible label. The `name` attribute only controls form submission and does not label the slider.

## Vertical

```blade
<hw:slider
    name="volume"
    :value="50"
    :min="0"
    :max="100"
    orientation="vertical"
    aria-label="Volume"
/>
```

Vertical sliders keep the minimum at the bottom and use the native range input keyboard behavior.

## RTL

Horizontal sliders inherit direction from the document or accept `dir="rtl"` directly:

```blade
<hw:slider name="position" :value="25" dir="rtl" aria-label="Position" />
```

## Laravel Validation

The component derives `id`, `errorKey`, old input and ARIA attributes using the same contract as the other field-aware
controls:

```blade
<hw:field name="filters[price]" error-key="filters.price" label="Maximum price">
    <hw:slider :value="500" :min="0" :max="1000" :step="10" />
</hw:field>
```

Native range inputs always have a value, so `required` does not provide meaningful missing-value validation and is not
emitted on the input. A required `<hw:field>` can still display its visual label marker.

## Auto-submit

Range inputs emit frequent `input` events while dragging, so `auto-submit` is debounced by default:

```blade
<hw:form method="get" action="/products" auto-submit auto-submit-delay="300">
    <hw:slider name="max_price" :value="request('max_price', 500)" :max="1000" aria-label="Maximum price" auto-submit />
</hw:form>
```

Use `auto-submit="immediate"` when every input event should submit without debounce.

## Single Value Scope

Unlike shadcn's Base UI Slider, `<hw:slider>` intentionally supports one native thumb. Range selection, multiple thumbs
and collision behavior require a custom state machine and are outside this component's scalar form contract.

For live text output, listen to the native `input` event with an application controller. The component does not impose a
number formatter or output structure.

## Controller integrations

The `slider` controller auto-loads with the component and keeps `--slider-value` synchronized. An ancestor
`<hw:form auto-submit>` supplies `auto-submit` when that prop is enabled.

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="slider"`
