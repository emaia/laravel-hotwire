# Carousel

Slider/carousel powered by [Embla Carousel](https://www.embla-carousel.com). Wraps the Embla instance with
declarative targets for navigation buttons and pagination dots, syncs `disabled` state automatically, dispatches
Stimulus events for integration with other controllers, and cleans itself up on Turbo cache and disconnect.

**Identifier:** `carousel`
**Install:** `php artisan hotwire:controllers carousel`

> The published files are `carousel_controller.js` and `carousel.css` (imported by the controller). Slide size and
> spacing are set with CSS custom properties — see the [Markup contract](#markup-contract).

## Requirements

- `embla-carousel` `^8.6.0` (`bun add embla-carousel`)

> `php artisan hotwire:check --fix` adds `embla-carousel` (pinned to the version this package targets) to your
> `package.json` automatically when any view uses `<x-hwc::carousel>` or a `data-controller="carousel"` element.

## Targets

| Target        | Required | Description                                                                                               |
|---------------|----------|-----------------------------------------------------------------------------------------------------------|
| `viewport`    | Optional | The element with `overflow:hidden` that Embla measures. Falls back to the controller element itself       |
| `container`   | Optional | The flex container that holds the slides — informational; Embla finds it via `viewport.firstElementChild` |
| `prevButton`  | Optional | Previous-slide button. Disabled automatically when `canScrollPrev` is false                               |
| `nextButton`  | Optional | Next-slide button. Disabled automatically when `canScrollNext` is false                                   |
| `dotList`     | Optional | Container that the controller fills with one button per snap                                              |
| `dotTemplate` | Optional | `<template>` cloned for each dot. Falls back to a bare `<button>` when absent                             |

## Stimulus Values

| Value     | Type     | Default | Description                                                                                                                                                                                                            |
|-----------|----------|---------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `options` | `Object` | `{}`    | Embla [options](https://www.embla-carousel.com/api/options/) — `loop`, `align`, `axis`, `slidesToScroll`, `dragFree`, `containScroll`, `duration`, `startIndex`, `breakpoints`, etc. Changes trigger `embla.reInit()`. |

## Styling state

The controller stays presentation-free — it only manages **semantic state**, and you style from it:

- The **active dot** is marked `aria-current="true"`. Style it with the `aria-[current=true]:` Tailwind variant (or
  `[aria-current="true"] { … }` in plain CSS) right on the dot template.
- **Prev/Next** use the native `disabled` attribute. Style them with the `disabled:` variant (or `button:disabled`).

```html

<button
    type="button"
    class="size-2.5 rounded-full bg-white/50 transition-colors aria-[current=true]:bg-white"
    data-action="carousel#scrollTo"
></button>

<button
    data-carousel-target="prevButton"
    data-action="carousel#prev"
    class="disabled:pointer-events-none disabled:opacity-40"
>
    ‹
</button>
```

There's no presentation class to configure (and so nothing to safelist) — the utility lives literally in the markup,
where Tailwind scans it.

## Actions

| Action             | Description                                                           |
|--------------------|-----------------------------------------------------------------------|
| `next`             | Scroll to the next snap (`embla.scrollNext()`)                        |
| `prev`             | Scroll to the previous snap (`embla.scrollPrev()`)                    |
| `scrollTo`         | Scroll to a specific snap by index — pass `data-carousel-index-param` |
| `play`             | Start autoplay — no-op unless the Autoplay plugin is enabled          |
| `stop`             | Stop autoplay — no-op unless the Autoplay plugin is enabled           |
| `teardownForCache` | Destroy the Embla instance — wire to `turbo:before-cache@window`      |

## Events dispatched

The controller dispatches `CustomEvent`s on its root element (they bubble):

| Event                     | `detail`                                                                              |
|---------------------------|---------------------------------------------------------------------------------------|
| `carousel:init`           | `{ embla }` — the live Embla instance, useful for plugins/analytics                   |
| `carousel:select`         | `{ index, previousIndex, slidesInView }`                                              |
| `carousel:scroll`         | `{ progress }` — scroll progress `0..1`; fires on every frame, keep the handler cheap |
| `carousel:settle`         | (empty) — fired after a scroll comes to rest                                          |
| `carousel:slides-in-view` | `{ inView: number[] }` — slide indexes currently in the viewport (lazy-load trigger)  |
| `carousel:slides-changed` | (empty) — fired when slides are added or removed (e.g. by a Turbo Stream)             |

Wire them with `data-action`:

```html

<div data-controller="carousel" data-action="carousel:select->analytics#track">…</div>
```

## Markup contract

The minimum required structure:

```html

<div data-controller="carousel">
    <div data-carousel-target="viewport">
        <div data-carousel-target="container">
            <div>slide 1</div>
            <div>slide 2</div>
            <div>slide 3</div>
        </div>
    </div>
</div>
```

The controller's CSS file handles the structure: `overflow:hidden` on the viewport, `display:flex` on the container,
and per-slide sizing/gap through two custom properties (the "Embla way"):

| Property                   | Default | Description                                                              |
|----------------------------|---------|--------------------------------------------------------------------------|
| `--carousel-slide-size`    | `100%`  | Flex basis of each slide (`50%` → two-per-view, `33.333%` → three, etc.) |
| `--carousel-slide-spacing` | `0px`   | Gap between slides (applied via the padding method, loop/RTL-safe)       |

Set them on the carousel root (the `<x-hwc::carousel>` component does this from its `slideSize`/`slideSpacing` props):

```html

<div data-controller="carousel" style="--carousel-slide-size: 50%; --carousel-slide-spacing: 1rem">…</div>
```

Prefer the custom properties over putting `flex-[…]` utilities on the slides — the controller's slide rule is scoped
(`… [data-carousel-target="container"] > *`) and wins on specificity, so a utility on the slide would be ignored.

**Axis:** the controller mirrors the Embla `axis` onto `data-carousel-axis` on the root, and the CSS applies the
matching `touch-action` (`pan-y` horizontal / `pan-x` vertical) and gap/flex direction. Vertical (`axis: 'y'`) needs a
height on the viewport — set it via `viewportClass`/your own CSS.

If you omit the viewport target, the controller element itself is used as the viewport — fine for the simplest
case, but using an explicit target lets you place navigation/dots outside the clipped area.

> **Nested carousels:** the CSS selectors use descendant combinators, so an inner carousel's `viewport`/`container`
> targets also match the outer carousel's rules. Both rules (`overflow:hidden` and `display:flex`) are always wanted
> on a viewport/container anyway, so there's no behavioral conflict — just be aware that any custom selector you
> add against `data-controller~="carousel"` will reach inner instances too.

## Configuring with the Stimulus builder

Writing the `options` value inline as a JSON string (`data-carousel-options-value='{"loop":true}'`) gets noisy
fast. Use the package's [Stimulus attribute helpers](../stimulus-helpers.md) — pass a PHP array, let the builder
JSON-encode it for you, and chain the rest of the wiring:

```blade
<div
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['loop' => true, 'align' => 'center'],
            ])
            ->action('carousel', 'teardownForCache', 'turbo:before-cache@window')
    }}
>
    …
</div>
```

renders to the same attributes the controller expects:

```html

<div
    data-controller="carousel"
    data-carousel-options-value='{"loop":true,"align":"center"}'
    data-action="turbo:before-cache@window->carousel#teardownForCache"
></div>
```

The same goes for targets and actions on the children:

```blade
<div {{ stimulus_target('carousel', 'viewport') }}>…</div>
<button
    {{ stimulus_target('carousel', 'prevButton') }}
    {{ stimulus_action('carousel', 'prev') }}
>
    ‹
</button>
```

All examples below use this style.

## With navigation and dots

```blade
<div
    {{
        stimulus()
            ->controller('carousel', ['options' => $options])
            ->action('carousel', 'teardownForCache', 'turbo:before-cache@window')
    }}
    class="relative"
>
    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $photo)
                <div>
                    <img src="{{ $photo->url }}" alt="" />
                </div>
            @endforeach
        </div>
    </div>

    <button
        type="button"
        {{ stimulus_target('carousel', 'prevButton') }}
        {{ stimulus_action('carousel', 'prev') }}
        class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-white/80 p-2 disabled:pointer-events-none disabled:opacity-40"
        aria-label="Previous"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="lucide lucide-chevron-left-icon lucide-chevron-left"
        >
            <path d="m15 18-6-6 6-6" />
        </svg>
    </button>

    <button
        type="button"
        {{ stimulus_target('carousel', 'nextButton') }}
        {{ stimulus_action('carousel', 'next') }}
        class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-white/80 p-2 disabled:pointer-events-none disabled:opacity-40"
        aria-label="Next"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="lucide lucide-chevron-right-icon lucide-chevron-right"
        >
            <path d="m9 18 6-6-6-6" />
        </svg>
    </button>

    <div
        {{ stimulus_target('carousel', 'dotList') }}
        class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5"
        role="group"
        aria-label="Choose slide"
    ></div>

    <template {{ stimulus_target('carousel', 'dotTemplate') }}>
        <button
            type="button"
            class="size-2.5 rounded-full bg-white/50 transition-colors aria-current:bg-white"
            {{ stimulus_action('carousel', 'scrollTo') }}
        ></button>
    </template>
</div>
```

The controller fills `dotList` with one cloned `dotTemplate` per snap, sets `data-carousel-index-param` on each
clone so `scrollTo` knows where to go, and marks the active dot with `aria-current="true"` (style it with the
`aria-[current=true]:` variant). Each dot also gets an `aria-label` — "Go to slide N", or "Go to group N" when
`slidesToScroll` groups slides (snaps fewer than slides). Dots are rebuilt only when the snap count changes (init,
`reInit`, `slidesChanged`), not on every selection, so dot focus is preserved while navigating.

## Vertical orientation

```blade
<div
    {{ stimulus_controller('carousel', ['options' => [...$options, 'axis' => 'y']]) }}
    class="h-96"
>
    <div {{ stimulus_target('carousel', 'viewport') }} class="h-full">
        <div {{ stimulus_target('carousel', 'container') }}>
            <div>slide 1</div>
            <div>slide 2</div>
        </div>
    </div>
</div>
```

When `axis: "y"` is set, the controller's CSS still applies `display:flex` to the container — add `flex-col` (or
`flex-direction: column`) yourself, plus a fixed height on the viewport.

## Breakpoints

Embla supports media-query overrides via the `breakpoints` option — exactly the kind of payload that's painful to
hand-write as JSON and pleasant as a PHP array:

```blade
<div
    {{
        stimulus_controller('carousel', [
            'options' => [
                ...$options,
                'slidesToScroll' => 1,
                'breakpoints' => [
                    '(min-width: 768px)' => ['slidesToScroll' => 2],
                    '(min-width: 1280px)' => ['slidesToScroll' => 3],
                ],
            ],
        ])
    }}
>
    …
</div>
```

> `breakpoints` only overrides **Embla options** — it does **not** change how many slides are visible. Slide width is
> CSS (`--carousel-slide-size`); see the next section to make both responsive together.

## Responsive slides per view (e.g. 3-up desktop, 1-up mobile)

This is the one setup that lives in **two places**, because Embla doesn't control slide width — your CSS does:

- **How many slides are visible** → the `--carousel-slide-size` custom property (a CSS media query).
- **How many slides advance per page** → Embla's `slidesToScroll`, made responsive with `breakpoints`.

Use the **same breakpoint** in both so they flip together. Three-per-page from `md` up (advancing a page of three),
one-per-page on mobile:

```blade
<div
    {{
        stimulus()
            ->controller('carousel', ['options' => ['loop' => true, 'align' => 'center', 'breakpoints' => [
                '(min-width: 768px)' => ['slidesToScroll' => 2],
                '(min-width: 1280px)' => ['slidesToScroll' => 3],
            ]]], ['active-dot' => 'bg-white'])
            ->action('carousel', 'teardownForCache', 'turbo:before-cache@window')
    }}
    class="relative [--carousel-slide-size:100%] md:[--carousel-slide-size:45%] lg:[--carousel-slide-size:25%] [--carousel-slide-spacing:1rem]"
>
    <div
        {{ stimulus_target('carousel', 'viewport') }}
        class="overflow-hidden"
    >
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $photo)
                <img src="{{ $photo->url }}" alt="" class="w-full h-96 object-cover md:rounded-md">
            @endforeach
        </div>
    </div>
</div>
```

- `[--carousel-slide-size:…]` are Tailwind arbitrary custom-property utilities — no extra CSS file needed (plain CSS
  works too: a class setting `--carousel-slide-size` inside a `@media` query). `33.333%` × 3 fills the row; the spacing
  sits inside each slide (padding method), so it still fits.
- `align: 'start'` keeps pages aligned to the edge — `center` would offset multi-slide pages.
- The dots become one per **page** (Embla groups the snaps), and the controller labels them "Go to group N"
  automatically.
- Embla re-inits on the breakpoint change (matchMedia) and on the slide-width change (ResizeObserver) — nothing to
  wire up.
- For clean pages, keep the slide count a multiple of `slidesToScroll`; the default `containScroll: 'trimSnaps'`
  trims redundant snaps otherwise.

## Reactive options

Setting `data-carousel-options-value` at runtime (via another controller, a Turbo Stream replacing the attribute,
etc.) calls `embla.reInit(...)` automatically, so the carousel picks up the new configuration without remounting.

## Turbo compatibility

- Wire `turbo:before-cache@window->carousel#teardownForCache` so the Embla-applied inline `transform` does not get
  cached into the snapshot — otherwise the restored page would briefly show the slides in the wrong position.
- The controller cleans up on `disconnect()`: removes Embla listeners, calls `embla.destroy()`, clears references.
  Turbo Drive navigations, Frame replacements and morphs all go through Stimulus disconnect/connect and re-mount
  correctly.
- Embla's own observers (`watchSlides`, `watchResize`) handle slides added by Turbo Streams inside the container
  without manual reInit.
