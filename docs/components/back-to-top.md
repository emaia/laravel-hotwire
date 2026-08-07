# Back to Top

Renders an accessible fixed button that appears after the page scrolls past a threshold and returns the window to the
top.

## Basic usage

Render it once in your layout:

```blade
<hw:back-to-top />
```

The component starts with `data-visible="false"` and `inert`. Its controller reveals the button after
`window.scrollY > 400`, removes `inert` while visible, and restores both states after returning to the top.

## Threshold

Set the number of pixels that must be passed before the button appears:

```blade
<hw:back-to-top :threshold="800" />
```

The comparison is strictly greater than the threshold, so the button remains hidden at exactly `800` pixels.

## Label and icon

The default accessible label is `Back to top` and the default icon is `chevron-up`:

```blade
<hw:back-to-top label="Return to page start" icon="arrow-up" />
```

The label is rendered as `aria-label`. Use any icon name supported by [`<hw:icon>`](icon.md).

Slot content replaces the configured icon:

```blade
<hw:back-to-top label="Return to page start">
    <svg aria-hidden="true" viewBox="0 0 24 24">
        <!-- Custom icon -->
    </svg>
</hw:back-to-top>
```

## Variants and sizes

Back to Top shares the visual variants and sizes from [`<hw:button>`](button.md). It defaults to the `default` variant
and the `icon-lg` size.

```blade
<hw:back-to-top variant="outline" size="icon" />
```

The Nova preset owns the fixed bottom-end position, rounded shape, stacking order, opacity transition, and visible
states. The button uses `z-40`, below overlay surfaces at `z-50`. There are intentionally no position, offset, scroll
behavior, or tooltip props.

## Custom attributes and Stimulus

HTML attributes, classes, and styles pass through to the button:

```blade
<hw:back-to-top class="my-back-to-top" style="margin-bottom: env(safe-area-inset-bottom)" />
```

Compose additional controllers and actions with regular attributes or the `stimulus` prop:

```blade
<hw:back-to-top
    data-controller="analytics"
    data-action="click->analytics#track"
    :stimulus="stimulus()->controller('tooltip')->action('tooltip', 'show', 'mouseenter')"
/>
```

The required `back-to-top` controller and action remain in place. Internal `data-back-to-top-*`, `data-visible`, and
`inert` attributes are reserved; use the component props instead.

## Props

| Prop        | Type             | Default         | Description                                                       |
| ----------- | ---------------- | --------------- | ----------------------------------------------------------------- |
| `threshold` | `int`            | `400`           | Scroll position after which the button becomes visible.           |
| `label`     | `string`         | `'Back to top'` | Accessible name rendered as `aria-label`.                         |
| `icon`      | `string`         | `'chevron-up'`  | Built-in icon rendered when the default slot is empty.            |
| `variant`   | `string`         | `'default'`     | Button visual variant from the active preset.                     |
| `size`      | `string`         | `'icon-lg'`     | Button size from the active preset.                               |
| `stimulus`  | `Htmlable\|null` | `null`          | Additional Stimulus bindings merged with the required controller. |

## Accessibility and motion

- The component always renders a native `<button type="button">` with an accessible label.
- The initial `inert` attribute prevents focus before Stimulus connects. It remains synchronized with
  `data-visible`, so an invisible button cannot receive keyboard focus.
- Scrolling back uses smooth behavior by default and automatically switches to `auto` when
  `prefers-reduced-motion: reduce` is active.
- The Nova opacity transition is also disabled for reduced motion.

## Styling hooks

- `data-slot="back-to-top"`
- `data-variant="<variant>"`
- `data-size="<size>"`
- `data-visible="true|false"`

## Controller

This component depends on the [`back-to-top`](../controllers/back-to-top.md) controller.
