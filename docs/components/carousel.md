# Carousel

Slider/carousel powered by [Embla Carousel](https://www.embla-carousel.com). Wraps the
[`carousel`](../controllers/carousel.md) controller with navigation buttons, pagination dots, responsive options and
CSS-variable sizing — all wired for you.

Requires `embla-carousel`: `bun add embla-carousel` (install it manually). The controller and its `carousel.css` are
published with `php artisan hotwire:controllers carousel`.

## Basic usage

```html
<x-hwc::carousel slide-size="80%" slide-spacing="1rem" loop>
    @foreach ($photos as $photo)
    <img src="{{ $photo->url }}" alt="" class="w-full rounded-md" />
    @endforeach
</x-hwc::carousel>
```

Each direct child of the default slot is a slide. Slide width and gap come from `slide-size` / `slide-spacing` (CSS
custom properties) — see [sizing](../controllers/carousel.md#markup-contract).

## Props

| Prop                                           | Type          | Default        | Description                                                                                        |
| ---------------------------------------------- | ------------- | -------------- | -------------------------------------------------------------------------------------------------- |
| `id`                                           | `?string`     | `uniqid()`     | Root element id                                                                                    |
| `loop`                                         | `bool`        | `false`        | Infinite looping                                                                                   |
| `align`                                        | `string`      | `center`       | `start` / `center` / `end`                                                                         |
| `axis`                                         | `string`      | `x`            | `x` / `y` (vertical needs a height on the viewport)                                                |
| `slides-to-scroll`                             | `int\|string` | `auto`         | Slides advanced per move (`auto` paginates by the visible count)                                   |
| `drag-free`                                    | `bool`        | `false`        | Momentum dragging without snapping                                                                 |
| `breakpoints`                                  | `?array`      | `null`         | Media-query → option overrides (e.g. responsive `slides-to-scroll`)                                |
| `respect-motion-preference`                    | `bool`        | `true`         | Injects a `prefers-reduced-motion` breakpoint that disables the animation                          |
| `options`                                      | `array`       | `[]`           | Catch-all merged into the Embla options (`duration`, `containScroll`, `direction`, `watchDrag`, …) |
| `navigation`                                   | `bool`        | `true`         | Render prev/next buttons                                                                           |
| `dots`                                         | `bool`        | `true`         | Render pagination dots                                                                             |
| `slide-size`                                   | `?string`     | `null`         | `--carousel-slide-size` (e.g. `70%`); responsive via Tailwind utilities                            |
| `slide-spacing`                                | `?string`     | `null`         | `--carousel-slide-spacing` (e.g. `1rem`)                                                           |
| `class` / `viewport-class` / `container-class` | `string`      | `''`           | Classes for the root / viewport / container                                                        |
| `dot-class`                                    | `string`      | `''`           | Class for each dot (active state via `aria-[current=true]:`)                                       |
| `dot-list-class`                               | `string`      | `''`           | Class for the dot-list container (e.g. positioning)                                                |
| `dot-list-label`                               | `string`      | `Choose slide` | `aria-label` for the dot-list container                                                            |
| `nav-class`                                    | `string`      | sensible       | Class for prev/next (disabled state via `disabled:`)                                               |

> `slides-to-scroll` defaults to `auto` (Embla's is `1`) so multi-slide layouts paginate by the visible count.
> Pass `:slides-to-scroll="1"` for one-at-a-time. Use `:` for integers (`:slides-to-scroll="3"`).

## Slots

| Slot           | Purpose                                                                                             |
| -------------- | --------------------------------------------------------------------------------------------------- |
| default        | The slides (each direct child is a slide)                                                           |
| `prev_button`  | Custom previous-button content (defaults to `‹`)                                                    |
| `next_button`  | Custom next-button content (defaults to `›`)                                                        |
| `dot_template` | Inner content for each dot button (the component renders the `<button>`; style it with `dot-class`) |

The slot's **own attributes merge onto the button** the component renders — pass `class` (appended), `aria-label`
(overrides the default), or any attribute:

```html
<x-slot:prev_button class="rounded-full bg-white/80 p-2" aria-label="Previous slide">
    <svg><!-- chevron-left --></svg>
</x-slot:prev_button>
```

## Responsive multi-slide (3-up desktop, 1-up mobile)

Slide width is CSS, advance count is an Embla option — use the same breakpoint in both:

```html
<x-hwc::carousel
    align="start"
    :breakpoints="['(min-width: 768px)' => ['slidesToScroll' => 3]]"
    class="[--carousel-slide-size:100%] [--carousel-slide-spacing:1rem] md:[--carousel-slide-size:33.333%]"
>
    @foreach ($photos as $photo)
    <img src="{{ $photo->url }}" alt="" class="w-full" />
    @endforeach
</x-hwc::carousel>
```

## Custom navigation and dots

Prev/next take inner content via slots; dot appearance is set with `dot-class` (the component renders the dot
`<button>` itself). The `dot_template` slot is only for content _inside_ each dot — leave it off for plain dots.

```html
<x-hwc::carousel dot-class="h-1 w-6 rounded bg-white/40 transition-colors aria-[current=true]:bg-white">
    <x-slot:prev_button
        ><svg><!-- chevron-left --></svg></x-slot:prev_button
    >
    <x-slot:next_button
        ><svg><!-- chevron-right --></svg></x-slot:next_button
    >

    {{-- slides --}}
</x-hwc::carousel>
```

## Turbo

The component wires `turbo:before-cache@window->carousel#teardownForCache`, so the Embla instance is destroyed before
a Turbo snapshot and rebuilt on restore. Any `data-action` you add is unioned with it.

See the [controller docs](../controllers/carousel.md) for events, the markup contract, axis handling and more.
