# Navbar

Navigation bar for real links and buttons.

Use `<hw:navbar>` for route or section navigation that should render as `<nav>` with links and `aria-current`, not as
tabs. Use `<hw:tabs>` only when the control switches tab panels in the same document.

## Quick example

```blade
<hw:navbar aria-label="Sections">
    <hw:navbar.item href="/parks/1/basic-information">
        Basic information
    </hw:navbar.item>

    <hw:navbar.item href="/parks/1/content" current>
        Content
    </hw:navbar.item>
</hw:navbar>
```

## Props

| Component     | Prop            | Type                   | Default      | Description                                                     |
|---------------|-----------------|------------------------|--------------|-----------------------------------------------------------------|
| `navbar`      | `variant`       | `line\|pills`          | `line`       | Visual style in the Nova preset.                                |
| `navbar`      | `orientation`   | `horizontal\|vertical` | `horizontal` | Layout direction. Invalid values use horizontal.                |
| `navbar`      | `overflow`      | `scroll\|visible`      | `scroll`     | Mobile overflow hook for horizontal navigation.                 |
| `navbar`      | `sticky`        | `bool`                 | `false`      | Wraps the navbar in an internal sticky surface.                 |
| `navbar`      | `sticky-side`   | `top\|bottom`          | `top`        | Sticky side when `sticky` is enabled.                           |
| `navbar`      | `sticky-offset` | `string\|int\|float`   | `0`          | Sticky offset when `sticky` is enabled.                         |
| `navbar.item` | `href`          | `string\|null`         | `null`       | URL. Items render as anchors when present.                      |
| `navbar.item` | `current`       | `bool`                 | `false`      | Marks the item as the current page/section.                     |
| `navbar.item` | `disabled`      | `bool`                 | `false`      | Disables buttons or makes links inert with ARIA-disabled state. |
| `navbar.item` | `as`            | `string\|null`         | derived      | Override the rendered tag.                                      |
| `navbar.item` | `type`          | `string`               | `button`     | Button type when rendering a button.                            |

Any other HTML attribute on `<hw:navbar>` passes through to `<nav>`. Attributes on `<hw:navbar.item>` pass through to
the item element.

## Sticky navbar

Use explicit composition when you need full sticky wrapper control:

```blade
<hw:sticky side="top" offset="4rem">
    <hw:navbar aria-label="Park sections">
        <hw:navbar.item href="#basic" current>Basic</hw:navbar.item>
        <hw:navbar.item href="#content">Content</hw:navbar.item>
        <hw:navbar.item href="#media">Media</hw:navbar.item>
    </hw:navbar>
</hw:sticky>
```

For the common top/bottom sticky navbar, use the built-in sugar:

```blade
<hw:navbar aria-label="Park sections" sticky sticky-offset="4rem">
    <hw:navbar.item href="#basic" current>Basic</hw:navbar.item>
    <hw:navbar.item href="#content">Content</hw:navbar.item>
    <hw:navbar.item href="#media">Media</hw:navbar.item>
</hw:navbar>
```

`sticky`, `sticky-side`, and `sticky-offset` are only convenience props. Use `<hw:sticky>` directly when you need a
custom tag, custom surface behavior, or non-navbar sticky content.

## Vertical navbar

```blade
<aside>
    <hw:navbar orientation="vertical" aria-label="On this page" sticky sticky-offset="4rem">
        <hw:navbar.item href="#overview" current>Overview</hw:navbar.item>
        <hw:navbar.item href="#settings">Settings</hw:navbar.item>
    </hw:navbar>
</aside>
```

Anchor links use native browser scrolling. Add `scroll-margin-top` to targets in the app when sticky headers would cover
the scrolled section.

## Current state

Set `current` explicitly from your route or page state:

```blade
<hw:navbar.item
    :href="route('dashboard.parks.content.edit', $park)"
    :current="request()->routeIs('dashboard.parks.content.*')"
>
    Content
</hw:navbar.item>
```

Current links receive `data-current="true"` and `aria-current="page"`. Buttons receive `data-current="true"` without
`aria-current`.

## Protected links

```blade
<hw:navbar aria-label="Park sections">
    <hw:navbar.item :href="route('dashboard.parks.content.edit', $park)" current>
        Content
    </hw:navbar.item>

    @can('updateMedia', $park)
        <hw:navbar.item :href="route('dashboard.parks.media.edit', $park)">
            Media
        </hw:navbar.item>
    @endcan
</hw:navbar>
```

## Disabled items

```blade
<hw:navbar.item href="/billing" disabled>Billing</hw:navbar.item>
<hw:navbar.item disabled>Coming soon</hw:navbar.item>
```

Disabled anchors omit `href`, receive `aria-disabled="true"`, and are removed from tab order. Disabled buttons receive
the native `disabled` attribute.

## Styling Hooks

- `data-slot="navbar"`
- `data-slot="navbar-item"`
- `data-variant="line|pills"`
- `data-orientation="horizontal|vertical"`
- `data-overflow="scroll|visible"`
- `data-current="true|false"`
- `data-disabled="true"`

## Required controllers

None.
