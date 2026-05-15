# Popover

Anchored dialog toggled by a trigger button. Wraps the `popover` Stimulus controller and handles click-outside, Escape and cross-popover dismissal automatically.

## Quick example

```blade
<x-hwc::popover class="popover" trigger-class="btn-outline" content-class="w-80">
    <x-slot:trigger>Open menu</x-slot:trigger>

    <div class="p-4">
        <p>Popover content</p>
        <input type="text" autofocus />
    </div>
</x-hwc::popover>
```

## Props

| Prop           | Type           | Default        | Description                                                |
|----------------|----------------|----------------|------------------------------------------------------------|
| `id`           | `string\|null` | auto-generated | Base id; derives `-trigger` and `-content`                 |
| `class`        | `string`       | `""`           | Merged on the wrapper `<div>`                              |
| `triggerClass` | `string`       | `""`           | Applied to the trigger `<button>`                          |
| `contentClass` | `string`       | `""`           | Applied to the content `<div>`                             |
| `placement`    | `string`       | `"left"`       | Anchors content to the wrapper's `left` or `right` edge    |

Invalid `placement` values fall back to `"left"`.

## Slots

| Slot      | Description                                        |
|-----------|----------------------------------------------------|
| `trigger` | Content of the trigger button (defaults to `Open`) |
| default   | Popover body                                       |

## Behavior

- Toggles `aria-expanded` on the trigger and `aria-hidden` on the content.
- Closes on Escape, click outside the wrapper, or when another popover opens (via the `basecoat:popover` event).
- If the content has an element with `[autofocus]`, it receives focus after the open transition completes.
- The wrapper is rendered with `position: relative` inline so the absolute-positioned content anchors to it out of the box.

## Placement

When the trigger sits near the right edge of the viewport, the default left-anchored content overflows the page horizontally. Use `placement="right"` to anchor the content to the wrapper's right edge instead — it expands leftward into the viewport:

```blade
<x-hwc::popover class="popover" placement="right">
    <x-slot:trigger>Notificações</x-slot:trigger>
    <div class="w-80 p-4">...</div>
</x-hwc::popover>
```

This applies `style="right: 0; left: auto;"` inline on the content, overriding the `left: 0` default. A `data-placement="left|right"` attribute is also emitted on the content for any CSS hooks you want to add.

## Required controllers

`hotwire:check` looks for `popover`.
