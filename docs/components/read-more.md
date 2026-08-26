# Read More

Render a preview of long content and reveal the remainder only when the browser confirms that the
content exceeds the configured height.

## Basic Usage

```blade
<hw:read-more>
    {!! $article->body !!}
</hw:read-more>
```

The preview defaults to `320` pixels. Short content remains fully visible and never shows the fade
or trigger.

## Preview height and initial state

```blade
<hw:read-more :collapsed-height="240" :expanded="$showFullArticle">
    {!! $article->body !!}
</hw:read-more>
```

`collapsed-height` is measured in pixels. A height rather than a line count keeps the component
compatible with rich text containing headings, lists, images, and multiple block elements.

The component renders the requested `collapsed` or `expanded` state on the server. The browser may
replace it with `static` after measurement when the content does not overflow.

## Labels and icon

Use props for translated text and any icon shipped by [`<hw:icon>`](icon.md):

```blade
<hw:read-more more-label="Continue reading" less-label="Show less" icon="arrow-down">
    {!! $article->body !!}
</hw:read-more>
```

Rich label content and a custom icon are available through named slots:

```blade
<hw:read-more>
    <x-slot:more><strong>More details</strong></x-slot>
    <x-slot:less><strong>Fewer details</strong></x-slot>
    <x-slot:trigger_icon>
        <svg aria-hidden="true"><!-- custom glyph --></svg>
    </x-slot>

    {!! $article->body !!}
</hw:read-more>
```

Set `icon=""` to omit the icon.

## Trigger appearance

The trigger supports the same `variant` and `size` axes as [`<hw:button>`](button.md). It defaults
to `variant="link"` and `size="default"`.

```blade
<hw:read-more variant="outline" size="sm">...</hw:read-more>
```

## First paint and progressive enhancement

The component emits `--read-more-collapsed-height` in its server-rendered markup. Structural CSS
uses it under `@media (scripting: enabled)` to clamp before the lazily loaded controller connects,
while the trigger and fade remain hidden until overflow is measured. This prevents an expanded
content flash and avoids a control flash for short content.

When scripting is disabled, the clamp query does not match. The full content remains available and
the non-functional trigger stays hidden.

The `scripting` media feature requires Firefox 113+, Chrome 120+, or Safari 17+. Older browsers
degrade to showing the full content until the controller connects. If scripting is enabled but the
application bundle fails to load, the browser still matches `scripting: enabled`; the server clamp
therefore remains active while the trigger remains hidden. This is the explicit tradeoff for
preventing a content flash before an asynchronously loaded controller is available.

## Custom controller and Stimulus composition

Use `controller` when an application controller subclasses the package implementation:

```blade
<hw:read-more controller="article-preview">...</hw:read-more>
```

All identifier-scoped values, targets, and actions follow the custom name. Structural `data-slot`
hooks remain `read-more-*` so the package CSS still works.

Additional controllers and actions merge through HTML attributes or `stimulus`:

```blade
<hw:read-more
    data-controller="analytics"
    data-action="read-more:change->analytics#track"
    :stimulus="stimulus()->controller('tooltip')->action('tooltip', 'show', 'mouseenter')"
>
    ...
</hw:read-more>
```

The component reserves its controller values and `data-state`, `data-ready`, `data-transitioning`,
and `data-pinning`; configure those through props and controller actions rather than root attributes.

## Props

| Prop               | Type             | Default          | Description                                                       |
| ------------------ | ---------------- | ---------------- | ----------------------------------------------------------------- |
| `id`               | `string\|object\|null` | generated  | Root id; accepts a model for [cross-request identity](../recipes/stable-component-ids.md). |
| `collapsed-height` | `int`            | `320`            | Preview height in pixels.                                         |
| `expanded`         | `bool`           | `false`          | Requested initial expansion state.                                |
| `more-label`       | `string`         | `'Read more'`    | Label shown while collapsed.                                      |
| `less-label`       | `string`         | `'Read less'`    | Label shown while expanded.                                       |
| `icon`             | `string`         | `'chevron-down'` | Built-in trigger icon; an empty string omits it.                  |
| `variant`          | `string`         | `'link'`         | Trigger variant from the active preset.                           |
| `size`             | `string`         | `'default'`      | Trigger size from the active preset.                              |
| `controller`       | `string`         | `'read-more'`    | Stimulus identifier, replaceable for application subclasses.      |
| `stimulus`         | `Htmlable\|null` | `null`           | Additional Stimulus bindings merged with the internal controller. |

Regular HTML attributes, including `class`, pass through the component attribute bag.

## Accessibility

- The trigger is a native button with matching `aria-expanded` and `aria-controls`.
- The fade is decorative, carries `aria-hidden="true"`, and is hidden from the DOM while expanded.
- If remeasurement hides a focused trigger, focus moves to the content instead of falling back to
  the document body.
- The complete text remains in the accessibility tree; expansion changes presentation rather than
  loading or removing content.
- Use the component for prose-oriented content. Links, form controls, and other focusable elements
  can remain keyboard-focusable even when they are below the visual cutoff.
- The structural height transition is disabled when `prefers-reduced-motion: reduce` is active.

## Styling hooks

- `data-slot="read-more"`
- `data-slot="read-more-viewport"`
- `data-slot="read-more-content"`
- `data-slot="read-more-fade"`
- `data-slot="read-more-trigger"`
- `data-slot="read-more-trigger-icon"`
- `data-state="static|collapsed|expanded"`
- `data-ready`
- `data-transitioning` while height is moving between collapsed and expanded
- `data-pinning` while an interrupted expansion captures its current rendered height

## Controller

This component depends on the [`read-more`](../controllers/read-more.md) controller.
