# Toggle

Button primitive with two-state `aria-pressed` semantics.

Use `<hw:toggle>` for lightweight UI state, filters and Turbo actions. Use `<hw:checkbox>` or `<hw:switch>` for boolean
settings that must always submit an explicit on/off value.

## Quick example

```blade
<hw:toggle :pressed="$post->featured">
    Featured
</hw:toggle>
```

## Props

| Prop          | Type           | Default     | Description                                                                 |
|---------------|----------------|-------------|-----------------------------------------------------------------------------|
| `name`        | `string\|null` | —           | Render a hidden input with this name for optional form/filter submission     |
| `value`       | `mixed`        | `"on"`      | Hidden input value and `change` event detail value                           |
| `pressed`     | `bool\|string` | `false`     | Initial pressed state                                                        |
| `variant`     | `string`       | `"default"` | `default` or `outline` in the Nova preset                                    |
| `size`        | `string`       | `"default"` | `default`, `sm` or `lg` in the Nova preset                                   |
| `type`        | `string`       | `"button"`  | Native button type                                                           |
| `auto-submit` | `bool`         | `false`     | Add `change->auto-submit#submit`; requires an ancestor `auto-submit` controller |

Any other HTML attribute (`id`, `class`, `disabled`, `aria-*`, `data-*`) passes through to the button. Internal
`data-toggle-*` attributes are protected; use props instead.

## Form filters

Passing `name` renders a hidden input associated with the toggle. The input is enabled only while the toggle is pressed,
so unpressed toggles are omitted from the submitted form data:

```blade
<hw:form method="get" action="/posts" frame="posts" auto-submit>
    <hw:toggle name="filters[]" value="featured" :pressed="request()->collect('filters')->contains('featured')" auto-submit>
        Featured
    </hw:toggle>
</hw:form>

<turbo-frame id="posts">
    ...
</turbo-frame>
```

This keeps Toggle suitable for filters and action state without replacing checkbox/switch behavior. If your server needs
an explicit false value, use `<hw:checkbox unchecked-value="0">` or `<hw:switch unchecked-value="0">` instead.

## Variants and sizes

```blade
<hw:toggle>Default</hw:toggle>
<hw:toggle variant="outline">Outline</hw:toggle>

<hw:toggle size="sm">Small</hw:toggle>
<hw:toggle size="lg">Large</hw:toggle>
```

## Icon state

The toggle root includes a `group/toggle` marker so icons can react to the pressed state without adding a class to the
button manually:

```blade
<hw:toggle name="status" value="done" :pressed="request('status') === 'done">
    <x-lucide-check-circle class="text-muted-foreground group-data-[state=on]/toggle:text-foreground" />
    Done
</hw:toggle>
```

For fillable icons, use the same named group marker for `fill-*` utilities:

```blade
<x-lucide-star class="group-aria-pressed/toggle:fill-foreground" />
```

## Events

The controller dispatches a bubbling native `change` event after user interaction:

```js
button.addEventListener("change", (event) => {
    console.log(event.detail.pressed, event.detail.value)
})
```

## Required controllers

`<hw:toggle>` mounts the `toggle` controller. `auto-submit` is only required when the `auto-submit` prop is used.
