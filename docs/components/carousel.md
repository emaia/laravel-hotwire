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
    <img src="{{ $photo->url }}" alt="" class="w-full rounded-md"/>
    @endforeach
</x-hwc::carousel>
```

Each direct child of the default slot is a slide. Slide width and gap come from `slide-size` / `slide-spacing` (CSS
custom properties) — see [sizing](../controllers/carousel.md#markup-contract).

## Props

| Prop                                           | Type          | Default        | Description                                                                                                          |
|------------------------------------------------|---------------|----------------|----------------------------------------------------------------------------------------------------------------------|
| `id`                                           | `?string`     | `uniqid()`     | Root element id                                                                                                      |
| `controller`                                   | `string`      | `carousel`     | Stimulus identifier to mount — set to a [subclass](../controllers/carousel.md#extending-plugins--custom-behavior) (e.g. `gallery`) to use Embla plugins/custom behavior |
| `loop`                                         | `bool`        | `false`        | Infinite looping                                                                                                     |
| `align`                                        | `string`      | `center`       | `start` / `center` / `end`                                                                                           |
| `axis`                                         | `string`      | `x`            | `x` / `y` (vertical needs a height on the viewport)                                                                  |
| `slides-to-scroll`                             | `int\|string` | `auto`         | Slides advanced per move (`auto` paginates by the visible count)                                                     |
| `drag-free`                                    | `bool`        | `false`        | Momentum dragging without snapping                                                                                   |
| `breakpoints`                                  | `?array`      | `null`         | Media-query → option overrides (e.g. responsive `slides-to-scroll`)                                                  |
| `respect-motion-preference`                    | `bool`        | `true`         | Injects a `prefers-reduced-motion` breakpoint that disables the animation                                            |
| `options`                                      | `array`       | `[]`           | Catch-all merged into the Embla options (`duration`, `containScroll`, `direction`, `watchDrag`, …)                   |
| `navigation`                                   | `bool`        | `true`         | Render prev/next buttons                                                                                             |
| `dots`                                         | `bool`        | `true`         | Render pagination dots                                                                                               |
| `slide-size`                                   | `?string`     | `null`         | `--carousel-slide-size` (e.g. `70%`); responsive via Tailwind utilities                                              |
| `slide-spacing`                                | `?string`     | `null`         | `--carousel-slide-spacing` (e.g. `1rem`)                                                                             |
| `class` / `viewport-class` / `container-class` | `string`      | `''`           | Classes for the root / viewport / container                                                                          |
| `dot-class`                                    | `string`      | `''`           | Class for each dot (active state via `aria-[current=true]:`)                                                         |
| `dot-list-class`                               | `string`      | `''`           | Class for the dot-list container (e.g. positioning)                                                                  |
| `dot-list-label`                               | `string`      | `Choose slide` | `aria-label` for the dot-list container                                                                              |
| `nav-class`                                    | `string`      | sensible       | Class for prev/next (disabled state via `disabled:`)                                                                 |
| `nav-wrapper-class`                            | `string`      | `''`           | When set, wraps both nav buttons in a `<div>` with this class (e.g. group them bottom-left); empty leaves them loose |

> `slides-to-scroll` defaults to `auto` (Embla's is `1`) so multi-slide layouts paginate by the visible count.
> Pass `:slides-to-scroll="1"` for one-at-a-time. Use `:` for integers (`:slides-to-scroll="3"`).

## Slots

| Slot           | Purpose                                                                                             |
|----------------|-----------------------------------------------------------------------------------------------------|
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
    <img src="{{ $photo->url }}" alt="" class="w-full"/>
    @endforeach
</x-hwc::carousel>
```

## Custom navigation and dots

Prev/next take inner content via slots; dot appearance is set with `dot-class` (the component renders the dot
`<button>` itself). The `dot_template` slot is only for content _inside_ each dot — leave it off for plain dots.

```html

<x-hwc::carousel dot-class="h-1 w-6 rounded bg-white/40 transition-colors aria-current:bg-white">
    <x-slot:prev_button>
        <svg><!-- chevron-left --></svg>
    </x-slot:prev_button>
    <x-slot:next_button>
        <svg><!-- chevron-right --></svg>
    </x-slot:next_button>

    {{-- slides --}}
</x-hwc::carousel>
```

## Slides with custom markup

```html

<x-hwc::carousel class="relative"
                 dot-list-class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
                 slide-size="80%"
                 slide-spacing="1rem"
                 loop
>
    @foreach ($photos as $photo)
    <div class="min-w-0 overflow-hidden">
        <img src="{{ $photo['url'] }}" alt="" class="w-full h-full rounded-md"/>
    </div>
    @endforeach

    <x-slot:prev_button
        class="absolute left-0 top-1/2 rotate-90- md:rounded-tr-md md:rounded-br-md -translate-y-1/2 bg-white/30 md:bg-white w-8 h-16 md:size-16 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-chevron-left-icon lucide-chevron-left">
            <path d="m15 18-6-6 6-6"/>
        </svg>
    </x-slot:prev_button>

    <x-slot:next_button
        class="absolute right-0 top-1/2 rotate-90- md:rounded-tl-md md:rounded-bl-md -translate-y-1/2 bg-white/30 md:bg-white w-8 h-16 md:size-16 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             class="lucide lucide-chevron-right-icon lucide-chevron-right">
            <path d="m9 18 6-6-6-6"/>
        </svg>
    </x-slot:next_button>

    <x-slot:dot_template
        class="size-2.5 rounded-full bg-white/50 transition-colors aria-current:bg-white"></x-slot:dot_template>

</x-hwc::carousel>
```

## Vertical Slides example

```html

<x-hwc::carousel class="relative"
                 viewport-class="h-150"
                 container-class="flex-col"
                 slide-size="80%"
                 slide-spacing="1rem"
                 axis="y"
                 :navigation="false"
                 loop
>

    @foreach ($photos as $photo)
    <div class="min-h-0 overflow-hidden">
        <img src="{{ $photo->url }}" alt="" class="w-full h-full object-cover rounded-2xl "/>
    </div>
    @endforeach
</x-hwc::carousel> 
```

## Fullscreen example

```html

<x-hwc::carousel class="relative h-screen"
                 dot-list-class="absolute bottom-6 left-6 flex gap-1.5"
                 nav-wrapper-class="absolute bottom-5 right-5 flex gap-2"
                 loop
>

    @foreach ($photos as $photo)
    <div class="min-w-0 overflow-hidden">
        <img src="{{ $photo->url }}" alt="" class="w-full h-screen"/>
    </div>
    @endforeach

    <x-slot:prev_button
        class="rounded-full bg-white/30 md:bg-white w-8 h-16 md:size-16 flex items-center justify-center text-3xl/none">
        &larr;
    </x-slot:prev_button>

    <x-slot:next_button
        class="rounded-full bg-white/30 md:bg-white w-8 h-16 md:size-16 flex items-center justify-center text-3xl/none">
        &rarr;
    </x-slot:next_button>

    <x-slot:dot_template
        class="size-2.5 rounded-full bg-white/50 transition-colors aria-current:bg-white"></x-slot:dot_template>

</x-hwc::carousel>
```

## Turbo

The component wires `turbo:before-cache@window->carousel#teardownForCache`, so the Embla instance is destroyed before
a Turbo snapshot and rebuilt on restore. Any `data-action` you add is unioned with it.

See the [controller docs](../controllers/carousel.md) for events, the markup contract, axis handling and more.
