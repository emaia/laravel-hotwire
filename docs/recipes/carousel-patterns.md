# Carousel patterns

Practical recipes built on the [`carousel`](../controllers/carousel.md) controller. Each one leans on what the
package already ships — Stimulus helpers, the `lazy-image`/`modal` controllers, Turbo Streams — instead of
custom JS frameworks.

> Looking for patterns that use the carousel as a generic snap engine for non-gallery UIs (multi-step
> wizards, server-driven signage, swipe decks, real-time presence)? See
> [carousel-as-primitive.md](./carousel-as-primitive.md).

## Recipes

- [Thumbnail navigation (two synced carousels)](#thumbnail-navigation-two-synced-carousels)
- [Open a slide in a lightbox modal](#open-a-slide-in-a-lightbox-modal)
- [Infinite slides via Turbo Stream](#infinite-slides-via-turbo-stream)
- [Deep-linkable slides (URL fragment)](#deep-linkable-slides-url-fragment)
- [Tracking with GTM analytics](#tracking-with-gtm-analytics)

## Thumbnail navigation (two synced carousels)

A product page with a hero image and a strip of thumbnails. Clicking a thumb scrolls the main carousel; dragging
the main carousel highlights the matching thumb. Two `carousel` controllers stacked with a tiny sister
controller that bridges them via Stimulus outlets.

```blade
<div
    {{
        stimulus()
            ->controller('carousel')
            ->controller('gallery-sync', outlets: ['thumbnails' => '[data-thumbnail-strip]'])
            ->action('gallery-sync', 'follow', 'carousel:select')
    }}
>
    <div {{ stimulus_target('carousel', 'viewport') }} class="aspect-square">
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $photo)
                <div class="min-w-0 flex-[0_0_100%]">
                    <img src="{{ $photo->url }}" alt="">
                </div>
            @endforeach
        </div>
    </div>
</div>

<div
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['containScroll' => 'keepSnaps', 'dragFree' => true],
            ], [
                'activeDot' => 'ring-2 ring-blue-500',
            ])
            ->controller('thumbnails')
            ->action('thumbnails', 'jump', 'click')
    }}
    data-thumbnail-strip
    class="mt-2"
>
    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $i => $photo)
                <button
                    type="button"
                    class="size-16 min-w-0 flex-[0_0_auto] mr-2"
                    data-thumbnails-index-param="{{ $i }}"
                >
                    <img src="{{ $photo->thumb_url }}" alt="">
                </button>
            @endforeach
        </div>
    </div>
</div>
```

Two thin sister controllers do the bridging:

```js
// resources/js/controllers/gallery_sync_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static outlets = ["thumbnails"];

    follow(event) {
        this.thumbnailsOutlet.scrollTo(event.detail.index);
    }
}

// resources/js/controllers/thumbnails_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    jump(event) {
        const button = event.target.closest("[data-thumbnails-index-param]");
        if (!button) return;
        const carousel = this.application.getControllerForElementAndIdentifier(
            this.element,
            "carousel",
        );
        carousel.scrollTo({ params: { index: parseInt(button.dataset.thumbnailsIndexParam, 10) } });
    }

    scrollTo(index) {
        this.application.getControllerForElementAndIdentifier(this.element, "carousel")
            .scrollTo({ params: { index } });
    }
}
```

The thumbnail strip uses `containScroll: 'keepSnaps'` + `dragFree: true` so users can swipe through thumbs without
the strip snapping like the main carousel. The active-dot class lights up the current thumb.

## Open a slide in a lightbox modal

Each slide is a clickable link to a `<x-hwc::modal>` frame that paints a bigger view server-side. No image
duplication — the modal pulls a dedicated `lightbox.show` route.

```blade
<div {{ stimulus_controller('carousel', ['options' => $options]) }}>
    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $photo)
                <a
                    href="{{ route('lightbox.show', $photo) }}"
                    data-turbo-frame="lightbox"
                    class="min-w-0 flex-[0_0_100%]"
                >
                    <img src="{{ $photo->url }}" alt="">
                </a>
            @endforeach
        </div>
    </div>
</div>

<x-hwc::modal id="lightbox" frame="lightbox" />
```

Server side, the route returns a Turbo Frame response with the larger asset and any metadata you want next to
it. Closing the modal is the standard `turbo_stream()->update('lightbox')` from the controller, no carousel
coordination needed.

## Infinite slides via Turbo Stream

Trigger a server fetch when the user reaches the last snap; the response appends new slides into the carousel
container. Embla's `watchSlides` picks them up and `carousel:slides-changed` lets you update any external
counters.

```blade
<div
    {{
        stimulus()
            ->controller('carousel')
            ->controller('infinite-slides', [
                'url' => route('photos.more', ['after' => $photos->last()->id]),
            ])
            ->controller('slide-counter')
            ->action('infinite-slides', 'maybeLoad', 'carousel:select')
            ->action('slide-counter', 'capture', 'carousel:init')
            ->action('slide-counter', 'refresh', 'carousel:slides-changed')
    }}
>
    <p>{{ __('Slide') }} <span data-slide-counter-target="current">1</span>
       {{ __('of') }} <span data-slide-counter-target="total">{{ $photos->count() }}</span></p>

    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }} id="carousel-slides">
            @foreach ($photos as $photo)
                @include('partials.slide', ['photo' => $photo])
            @endforeach
        </div>
    </div>
</div>
```

```js
// resources/js/controllers/infinite_slides_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { url: String, threshold: { type: Number, default: 2 } };

    maybeLoad(event) {
        const embla = event.detail.embla ?? this.cached;
        if (event.detail.embla) this.cached = event.detail.embla;
        const remaining = embla.scrollSnapList().length - event.detail.index;
        if (remaining > this.thresholdValue || this.loading) return;

        this.loading = true;
        fetch(this.urlValue, { headers: { Accept: "text/vnd.turbo-stream.html" } })
            .then((r) => r.text())
            .then((html) => window.Turbo.renderStreamMessage(html))
            .finally(() => (this.loading = false));
    }
}
```

The controller endpoint returns one `turbo-stream`:

```php
public function more(Request $request)
{
    $photos = Photo::where('id', '>', $request->integer('after'))->limit(8)->get();

    return turbo_stream()->append(
        'carousel-slides',
        view('partials.slides-batch', compact('photos'))
    );
}
```

Reuse the `slide-counter` controller from the carousel docs — the new `slides-changed` wire keeps the total
fresh as the stream grows the list.

### The slide partial — async thumbnails with `lazy-image`

When the stream appends new slides, the underlying asset (thumbnail, conversion, signed URL) may still be
processing — typically a queued Job. The [`lazy-image`](../controllers/lazy-image.md) controller polls the
asset URL on an interval, so the slide can ship a placeholder immediately and swap in the real image as soon
as the file exists. No need to delay the Turbo Stream until the asset is ready.

```blade
{{-- resources/views/partials/slide.blade.php --}}
<div id="{{ dom_id($photo) }}" class="min-w-0 flex-[0_0_100%]">
    <picture
        {{
            stimulus()->controller('lazy-image', [
                'url' => $photo->thumb_url,
                'alt' => $photo->caption,
                'interval' => 1500,
                'imgClass' => 'w-full h-full object-cover',
            ])
        }}
        class="block aspect-square bg-gray-100"
    >
        <x-hwc::spinner class="m-auto" />
    </picture>
</div>
```

Three things to notice:

- **`dom_id($photo)`** keeps each slide individually addressable — handy if a later Turbo Stream wants to
  `update` or `remove` a specific slide (e.g. the asset failed to generate, or the user deleted the photo).
- **`<x-hwc::spinner>`** is the placeholder rendered server-side; the user sees motion immediately, even
  before the asset URL becomes 200 OK.
- **`interval: 1500`** keeps polling under control on a long feed — Embla itself doesn't care which slides
  are still loading, so a steady poll across multiple in-flight slides stays cheap.

Pair it with [`carousel:slides-in-view`](../controllers/carousel.md#events-dispatched) if you want to only
**start** polling once the slide is about to be visible — useful when the user paginates fast and may scroll
past several pending slides without ever looking at them. The default behavior (polling on mount) is fine
for most cases.

## Deep-linkable slides (URL fragment)

Lets a user share a link like `/gallery#slide-3` and land on the right slide. Two wires: write the hash on
`carousel:select`, read it back on `turbo:load@window`.

```blade
<div
    {{
        stimulus()
            ->controllers('carousel', 'hash-slides')
            ->action('hash-slides', 'capture', 'carousel:init')
            ->action('hash-slides', 'sync', 'carousel:settle')
            ->action('hash-slides', 'restore', 'turbo:load@window')
    }}
>
    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($photos as $photo)
                <div class="min-w-0 flex-[0_0_100%]">…</div>
            @endforeach
        </div>
    </div>
</div>
```

```js
// resources/js/controllers/hash_slides_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    capture(event) { this.embla = event.detail.embla; this.restore(); }

    sync() {
        if (!this.embla) return;
        const i = this.embla.selectedScrollSnap();
        const hash = i === 0 ? "" : `#slide-${i + 1}`;
        history.replaceState(null, "", `${location.pathname}${location.search}${hash}`);
    }

    restore() {
        if (!this.embla) return;
        const match = location.hash.match(/^#slide-(\d+)$/);
        if (!match) return;
        this.embla.scrollTo(parseInt(match[1], 10) - 1, /* immediate */ true);
    }
}
```

Using `carousel:settle` (instead of `select`) avoids polluting `history` with intermediate snaps as the user
drags through. `turbo:load@window` handles both first paint and Turbo Drive navigations back to the page.

## Tracking with GTM analytics

What you usually want to measure on a carousel:

- **Mount** — how many users ever saw this gallery (and how big it was).
- **Slide view** — which slides actually got dwell time (not drag-through noise).
- **Depth** — how far the user reached before leaving the page.

The [`gtm`](../controllers/gtm.md) controller already loads GTM and exposes a `gtm#event` action that pushes
into `dataLayer`. Pair it with `carousel:settle` so you only count slides the user lingered on, and add a
tiny `carousel-analytics` sister controller for the per-slide payload (since `gtm#event`'s params are static).

```blade
<div
    {{
        stimulus()
            ->controller('carousel')
            ->controller('gtm', ['id' => 'GTM-XXXXXXX'])
            ->controller('carousel-analytics', ['name' => $gallery->slug])
            ->action('carousel-analytics', 'view', 'carousel:settle')
            ->action('carousel-analytics', 'flush', 'turbo:before-visit@window')
            ->action('carousel-analytics', 'flush', 'beforeunload@window')
            ->action('gtm', 'event', 'carousel:init', [
                'eventName' => 'carousel_mounted',
                'eventPayload' => ['name' => $gallery->slug],
            ])
    }}
>
    <div {{ stimulus_target('carousel', 'viewport') }}>
        <div {{ stimulus_target('carousel', 'container') }}>
            @foreach ($gallery->slides as $slide)
                <div data-slide-sku="{{ $slide->sku }}" class="min-w-0 flex-[0_0_100%]">…</div>
            @endforeach
        </div>
    </div>
</div>
```

```js
// resources/js/controllers/carousel_analytics_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { name: String };

    initialize() {
        this.maxReached = 0;
        this.seen = new Set();
    }

    view(event) {
        const { index } = event.detail;
        this.maxReached = Math.max(this.maxReached, index);

        // Avoid double-counting if the user navigates back and forth.
        if (this.seen.has(index)) return;
        this.seen.add(index);

        const slideElement = this.#containerEl()?.children[index];

        window.dataLayer.push({
            event: "carousel_view",
            carousel: this.nameValue,
            index,
            sku: slideElement?.dataset.slideSku ?? null,
        });
    }

    flush() {
        if (this.seen.size === 0) return;
        window.dataLayer.push({
            event: "carousel_depth",
            carousel: this.nameValue,
            max_index: this.maxReached,
            views: this.seen.size,
        });
        this.seen.clear();
    }

    #containerEl() {
        return this.element.querySelector('[data-carousel-target="container"]');
    }
}
```

What goes into `dataLayer`:

```log
{ event: "carousel_mounted", name: "summer-2026" }
{ event: "carousel_view",    carousel: "summer-2026", index: 0, sku: "P-101" }
{ event: "carousel_view",    carousel: "summer-2026", index: 1, sku: "P-102" }
// user leaves
{ event: "carousel_depth",   carousel: "summer-2026", max_index: 1, views: 2 }
```

Why each wire:

- `carousel:init → gtm#event` — fires once when Stimulus mounts; the payload is static so `gtm#event` is
  enough, no sister controller needed for this one.
- `carousel:settle → carousel-analytics#view` — Embla only fires `settle` after motion stops, so you don't
  count slides the user was just dragging through. The sister controller dedupes back-and-forth and reads
  per-slide data attributes (SKU, asset id, whatever you put there).
- `turbo:before-visit@window` **and** `beforeunload@window → flush` — covers both Turbo Drive navigations
  (the common case) and real page unloads (closing the tab, external links). `dataLayer.push` is
  synchronous, so the event lands before the page goes away.

For server-side analytics (e.g. a custom `/analytics/track` endpoint instead of GTM), swap `window.dataLayer.push`
for a `navigator.sendBeacon(url, JSON.stringify(payload))` — same wires, different sink.
