# Aspect Ratio

Static wrapper that preserves a media box ratio without JavaScript.

## Usage

```blade
<hw:aspect-ratio ratio="16/9">
    <img src="/cover.jpg" alt="Cover" class="size-full object-cover">
</hw:aspect-ratio>
```

Use `width` and `height` when the ratio comes from intrinsic media dimensions.

```blade
<hw:aspect-ratio width="4" height="3">
    <img src="/photo.jpg" alt="Photo" class="size-full object-cover">
</hw:aspect-ratio>
```

## Props

| Prop | Default | Description |
| --- | --- | --- |
| `ratio` | `16/9` | CSS aspect-ratio value written to `--ratio`. |
| `width` | `null` | Optional ratio numerator. Used with `height`. |
| `height` | `null` | Optional ratio denominator. Used with `width`. |

When both `width` and `height` are present, they take precedence over `ratio` and render `--ratio: width/height`.

## Slot

The default slot is rendered inside the ratio box. Media children usually need their own sizing classes or styles, such
as `class="size-full object-cover"` on an image.

## Styling Hooks

- `data-slot="aspect-ratio"`
- `--ratio`
