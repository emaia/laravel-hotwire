# Sticky

Generic top or bottom sticky surface primitive.

Use `<hw:sticky>` when content should stay visible while the page scrolls: section navigation, persistent form actions,
or compact summary bars. It does not add JavaScript and does not manage scrollspy, hash history, or dynamic offsets.

## Quick example

```blade
<hw:sticky side="top" offset="0">
    <hw:navbar aria-label="Sections">
        <hw:navbar.item href="#overview" current>Overview</hw:navbar.item>
        <hw:navbar.item href="#settings">Settings</hw:navbar.item>
    </hw:navbar>
</hw:sticky>
```

## Props

| Prop      | Type                 | Default | Description                                        |
|-----------|----------------------|---------|----------------------------------------------------|
| `side`    | `top\|bottom`        | `top`   | Viewport edge to stick to. Invalid values use top. |
| `offset`  | `string\|int\|float` | `0`     | CSS variable value for the sticky edge offset.     |
| `surface` | `bool`               | `true`  | Adds the Nova surface treatment when true.         |
| `as`      | `string`             | `div`   | HTML tag for the wrapper.                          |

Any other HTML attribute passes through to the wrapper.

## Bottom action bar

```blade
<hw:sticky side="bottom" offset="0" as="footer">
    <div class="mx-auto flex max-w-5xl justify-end gap-2 p-3">
        <hw:button variant="outline" type="button">Cancel</hw:button>
        <hw:button type="submit" form="park-form">Save</hw:button>
    </div>
</hw:sticky>
```

## Without surface

Disable the default border/background when the child component owns the visual treatment:

```blade
<hw:sticky :surface="false" offset="1rem">
    <hw:card>...</hw:card>
</hw:sticky>
```

## Styling Hooks

- `data-slot="sticky"`
- `data-side="top|bottom"`
- `data-surface="true|false"`
- `--sticky-offset`

## Required controllers

None.
