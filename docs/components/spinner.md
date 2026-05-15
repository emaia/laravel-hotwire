# Spinner

Animated SVG spinner. Pure HTML/CSS, no JavaScript.

## Basic usage

```blade
<x-hwc::spinner/>
```

## Attributes

The component renders an `<svg role="status" aria-label="Loading">` with `class="animate-spin"`. Any extra attribute is
merged into the `<svg>`:

```blade
<x-hwc::spinner class="text-blue-500 size-4" id="my-spinner"/>
```

## Showing the spinner conditionally

The spinner has no built-in visibility behavior — it always renders. Hide or show it with your own CSS, typically tied
to `aria-busy` on a parent element:

```blade
<button type="submit" aria-busy="false">
    Save
    <x-hwc::spinner class="hidden aria-busy:block"/>
</button>
```

Turbo automatically toggles `aria-busy="true"` on forms during submission, so the spinner appears while the request is
in flight and disappears when the response arrives.
