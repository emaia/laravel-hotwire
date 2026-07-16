# Toggle Group

Pressed-button group for single or multiple lightweight choices.

Use `<hw:toggle-group>` when the UI should behave like a set of toggles with `aria-pressed`. Use `<hw:radio-group>` for
mutually exclusive form choices that must submit one value, and `<hw:checkbox-group>` for checkbox semantics.

## Quick example

```blade
<hw:toggle-group type="single" name="alignment" :value="request('alignment')" aria-label="Text alignment">
    <hw:toggle-group.item value="left">Left</hw:toggle-group.item>
    <hw:toggle-group.item value="center">Center</hw:toggle-group.item>
    <hw:toggle-group.item value="right">Right</hw:toggle-group.item>
</hw:toggle-group>
```

## Props

| Component           | Prop                | Type                   | Default      | Description                                                                      |
|---------------------|---------------------|------------------------|--------------|----------------------------------------------------------------------------------|
| `toggle-group`      | `type`              | `single\|multiple`     | `multiple`   | Selection mode. `single` keeps at most one item pressed.                         |
| `toggle-group`      | `name`              | `string\|null`         | —            | Hidden input name for form submission. Multiple groups append `[]` when missing. |
| `toggle-group`      | `value`             | `mixed`                | `null`       | Selected value or selected value array.                                          |
| `toggle-group`      | `orientation`       | `horizontal\|vertical` | `horizontal` | Layout and ARIA orientation.                                                     |
| `toggle-group`      | `variant`           | `string`               | `default`    | `default` or `outline` in the Nova preset.                                       |
| `toggle-group`      | `size`              | `string`               | `default`    | `default`, `sm` or `lg` in the Nova preset.                                      |
| `toggle-group`      | `disabled`          | `bool\|string`         | `false`      | Disables every item and hidden input.                                            |
| `toggle-group`      | `connected`         | `bool\|string`         | `false`      | Removes spacing and connects item borders.                                       |
| `toggle-group`      | `old`               | `bool`                 | `true`       | Restores selected values from `old()` input.                                     |
| `toggle-group`      | `id`                | `string\|null`         | derived      | Base id for item hidden inputs and error reference.                              |
| `toggle-group`      | `errorKey`          | `string\|null`         | derived      | Override when HTML `name` differs from the Laravel validation key.               |
| `toggle-group`      | `auto-submit`       | `bool\|string`         | `false`      | Add auto-submit wiring to group change events.                                   |
| `toggle-group`      | `auto-submit-delay` | `int\|string\|null`    | `null`       | Per-field debounce override when `auto-submit="debounced"` is used.              |
| `toggle-group.item` | `value`             | `mixed`                | required     | Submitted item value and controller value.                                       |
| `toggle-group.item` | `pressed`           | `bool\|string\|null`   | `null`       | Force an item on in addition to the group `value`.                               |
| `toggle-group.item` | `disabled`          | `bool\|string\|null`   | `null`       | Disable only this item.                                                          |

Any other HTML attribute passes through. Internal `data-toggle-group-*` and `data-toggle-*` attributes are protected; use
props instead.

## Multiple Values

```blade
<hw:toggle-group type="multiple" name="formats" :value="old('formats', ['bold'])" connected>
    <hw:toggle-group.item value="bold">Bold</hw:toggle-group.item>
    <hw:toggle-group.item value="italic">Italic</hw:toggle-group.item>
    <hw:toggle-group.item value="underline">Underline</hw:toggle-group.item>
</hw:toggle-group>
```

Multiple groups submit one hidden input per pressed item. Passing `name="formats"` renders `name="formats[]"` so PHP
receives an array.

## Single Values

```blade
<hw:toggle-group type="single" name="density" :value="$user->density" variant="outline">
    <hw:toggle-group.item value="compact">Compact</hw:toggle-group.item>
    <hw:toggle-group.item value="comfortable">Comfortable</hw:toggle-group.item>
</hw:toggle-group>
```

Single groups allow the current item to be cleared. If the submitted field must always have exactly one value, use
`<hw:radio-group>` instead.

## Form Filters

```blade
<hw:form method="get" action="/posts" frame="posts" auto-submit>
    <hw:toggle-group type="multiple" name="status" :value="request()->array('status')" auto-submit>
        <hw:toggle-group.item value="draft">Draft</hw:toggle-group.item>
        <hw:toggle-group.item value="published">Published</hw:toggle-group.item>
        <hw:toggle-group.item value="archived">Archived</hw:toggle-group.item>
    </hw:toggle-group>
</hw:form>
```

The group emits bubbling `change` events from its toggle buttons, so `auto-submit` can submit after the controller has
enforced single/multiple state and synchronized hidden inputs.

## Styling Hooks

- `data-slot="toggle-group"`
- `data-slot="toggle-group-item"`
- `data-toggle-group-type-value="single|multiple"`
- `data-orientation="horizontal|vertical"`
- `data-connected="true"`
- `data-variant="default|outline"`
- `data-size="default|sm|lg"`
- `data-state="on|off"`

## Required controllers

`<hw:toggle-group>` mounts `toggle-group` on the root and each item mounts `toggle`. `auto-submit` is only required when
the `auto-submit` prop is used.
