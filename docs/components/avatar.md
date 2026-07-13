# Avatar

User avatar with image, initials fallback, badge and grouped display primitives.

## Usage

Pass `src` and `name` to render an image with generated initials fallback.

```blade
<hw:avatar src="/avatars/dina.jpg" name="Dina Maia" />
```

When `src` is omitted, the component renders only the fallback.

```blade
<hw:avatar name="Dina Maia" />
```

Use `initials` or `fallback` when the generated fallback should be overridden.

```blade
<hw:avatar name="Dina Maia" initials="EM" />

<hw:avatar fallback="?" />
```

## Composition

Use the composed subcomponents when you need full control over the markup.

```blade
<hw:avatar size="lg" shape="square">
    <hw:avatar.image src="/avatars/ada.jpg" alt="Ada Lovelace" />
    <hw:avatar.fallback>AL</hw:avatar.fallback>
</hw:avatar>
```

The image and fallback are both rendered in the DOM. The image sits over the fallback visually, so the fallback remains
available without JavaScript-driven image error handling.

## Sizes And Shapes

```blade
<hw:avatar name="Ada Lovelace" size="sm" />
<hw:avatar name="Ada Lovelace" />
<hw:avatar name="Ada Lovelace" size="lg" />

<hw:avatar name="Ada Lovelace" shape="square" />
```

## Badge

Add a badge for presence, status, or a small icon.

```blade
<hw:avatar src="/avatars/dina.jpg" name="Dina Maia">
    <hw:avatar.badge />
</hw:avatar>
```

```blade
<hw:avatar src="/avatars/dina.jpg" name="Dina Maia">
    <hw:avatar.badge position="top-start">
        <hw:icon name="check" />
    </hw:avatar.badge>
</hw:avatar>
```

## Groups

Use `avatar.group` for overlapping avatar stacks and `avatar.group-count` for the overflow count. The count follows the
size of the avatars in the group.

```blade
<hw:avatar.group>
    <hw:avatar src="/avatars/ana.jpg" name="Ana Silva" />
    <hw:avatar src="/avatars/dina.jpg" name="Dina Maia" />
    <hw:avatar name="Joao Lima" />
    <hw:avatar.group-count>+3</hw:avatar.group-count>
</hw:avatar.group>
```

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `avatar` | `src` | `null` | Optional image source. |
| `avatar` | `alt` | `null` | Image alt text. Falls back to `name`, then an empty string. |
| `avatar` | `name` | `null` | Display name used to generate fallback initials. |
| `avatar` | `initials` | `null` | Explicit fallback initials. |
| `avatar` | `fallback` | `null` | Explicit fallback text. |
| `avatar` | `size` | `default` | `sm`, `default` or `lg`. |
| `avatar` | `shape` | `circle` | `circle` or `square`. |
| `avatar.image` | `src` | `null` | Image source. |
| `avatar.image` | `alt` | `null` | Image alt text. |
| `avatar.fallback` | `name` | `null` | Name used to generate initials when the slot is empty. |
| `avatar.fallback` | `initials` | `null` | Explicit initials when the slot is empty. |
| `avatar.fallback` | `fallback` | `null` | Explicit fallback text when the slot is empty. |
| `avatar.badge` | `position` | `bottom-end` | `bottom-end`, `bottom-start`, `top-end` or `top-start`. |

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `avatar` | `span` | `avatar` |
| `avatar.image` | `img` | `avatar-image` |
| `avatar.fallback` | `span` | `avatar-fallback` |
| `avatar.badge` | `span` | `avatar-badge` |
| `avatar.group` | `div` | `avatar-group` |
| `avatar.group-count` | `span` | `avatar-group-count` |

## Styling Hooks

- `data-slot="avatar"`
- `data-size="sm|default|lg"`
- `data-shape="circle|square"`
- `data-slot="avatar-image"`
- `data-slot="avatar-fallback"`
- `data-slot="avatar-badge"`
- `data-position="bottom-end|bottom-start|top-end|top-start"`
- `data-slot="avatar-group"`
- `data-slot="avatar-group-count"`
