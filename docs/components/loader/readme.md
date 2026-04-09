# Loader

Animated SVG spinner to indicate loading. Pure HTML/CSS, no JavaScript.

## Basic usage

```html
<x-hwc-loader />
```

The loader is `hidden` by default and appears via `aria-busy:block` — combine with `aria-busy` on the parent element:

```html
<button type="submit" aria-busy="false">
    Save
    <x-hwc-loader />
</button>
```

## With Turbo Forms

Turbo automatically adds `aria-busy="true"` to forms during submission:

```html
<form method="POST" action="/items">
    @csrf
    <button type="submit">
        Save
        <x-hwc-loader />
    </button>
</form>
```

The spinner appears during submit and disappears when the response arrives.

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `size` | `string` | `'size-5 lg:size-4'` | Tailwind size classes |
| `aria-busy-class` | `string` | `'aria-busy:block'` | Class that shows the loader. Use `group-aria-busy:block` to react to `aria-busy` on a parent with `group` |

## Attributes

The component accepts extra attributes that are merged into the `<svg>`:

```html
<x-hwc-loader class="text-blue-500" id="my-loader" />
```

## Variant with `group`

To show the loader based on `aria-busy` of a parent container:

```html
<div class="group" aria-busy="false">
    <span>Processing...</span>
    <x-hwc-loader aria-busy-class="group-aria-busy:block" />
</div>
```
