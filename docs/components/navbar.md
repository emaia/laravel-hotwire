# Navbar

Navigation bar for real links and buttons.

Use `<hw:navbar>` for route or section navigation that should render as `<nav>` with links and `aria-current`, not as
tabs. Use `<hw:tabs>` only when the control switches tab panels in the same document.

## Quick example

```blade
<hw:navbar aria-label="Sections" :items="[
    ['label' => 'Basic information', 'href' => '/parks/1/basic-information'],
    ['label' => 'Content', 'href' => '/parks/1/content', 'current' => true],
]" />
```

Use `<hw:navbar.item>` directly when an item needs custom markup or HTML attributes.

## Props

| Component     | Prop            | Type                          | Default      | Description                                                     |
|---------------|-----------------|-------------------------------|--------------|-----------------------------------------------------------------|
| `navbar`      | `variant`       | `line\|pills`                 | `line`       | Visual style in the selected preset.                            |
| `navbar`      | `orientation`   | `horizontal\|vertical`        | `horizontal` | Layout direction. Invalid values use horizontal.                |
| `navbar`      | `overflow`      | `scroll\|visible`             | `scroll`     | Mobile overflow hook for horizontal navigation.                 |
| `navbar`      | `sticky`        | `bool`                        | `false`      | Wraps the navbar in an internal sticky surface.                 |
| `navbar`      | `sticky-side`   | `top\|bottom`                 | `top`        | Sticky side when `sticky` is enabled.                           |
| `navbar`      | `sticky-offset` | `string\|int\|float`          | `0`          | Sticky offset when `sticky` is enabled.                         |
| `navbar`      | `items`         | `array`                       | `[]`         | Plain item descriptors rendered before composed slot items.     |
| `navbar.item` | `href`          | `string\|null`                | `null`       | URL. Items render as anchors when present.                      |
| `navbar.item` | `current`       | `bool`                        | `false`      | Marks the item as the current page/section.                     |
| `navbar.item` | `disabled`      | `bool`                        | `false`      | Disables buttons or makes links inert with ARIA-disabled state. |
| `navbar.item` | `as`            | `a\|button\|span\|null`       | derived      | Override the rendered tag with a validated allowed value.       |
| `navbar.item` | `type`          | `button\|submit\|reset`       | `button`     | Native type when rendering a button.                            |
| `navbar.item` | `frame`         | `string\|object\|false\|null` | `null`       | Turbo Frame target when rendering an enabled anchor.            |

Any other HTML attribute on `<hw:navbar>` passes through to `<nav>`. Attributes on `<hw:navbar.item>` pass through to
the item element.

## Items API

| Key        | Required | Description                                          |
|------------|----------|------------------------------------------------------|
| `label`    | yes      | Escaped string, integer or `Stringable`; trusted `Htmlable` also renders directly. |
| `href`     | no       | URL as a string or `Stringable`; its presence derives an anchor by default. |
| `current`  | no       | Marks the item as the current page or section.       |
| `disabled` | no       | Applies the existing disabled link/button semantics. |
| `as`       | no       | Explicit `a`, `button` or `span` tag.                |
| `type`     | no       | Native button type. Defaults to `button`.            |
| `frame`    | no       | Turbo Frame target for an enabled anchor.            |

Generated items use `<hw:navbar.item>` and render before the root slot. Current state is never inferred from the URL or
item position. Filter the source array for authorization and use manual composition for per-item attributes, classes,
Stimulus bindings or conditional Blade content.

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

Current links receive `data-active="true"` and `aria-current="page"`. Buttons receive `data-active="true"` without
`aria-current`.

`as` is trimmed, lowercased, and restricted to `a`, `button`, or `span`; unsupported values are rejected. By default an
item with `href` renders as `a`, otherwise it renders as `button`.

Use `frame` to target anchor navigation. Objects resolve through `dom_id()`; null, false, empty, and whitespace-only
values omit the metadata. Explicit `data-turbo-frame` wins and can be bound to `false` to suppress the prop. Button,
span, current non-link, and disabled items do not emit frame metadata.

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

## Styling hooks

- `data-slot="navbar"`
- `data-slot="navbar-item"`
- `data-slot="sticky"`
- `data-variant="line|pills"`
- `data-orientation="horizontal|vertical"`
- `data-overflow="scroll|visible"`
- `data-active="true|false"`
- `data-disabled="true"`

## Controller integrations

None.
