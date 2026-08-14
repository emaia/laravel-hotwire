# Carousel patterns

Practical recipes built on [`<hw:carousel>`](../components/carousel.md) and the
[`carousel`](../controllers/carousel.md) controller. The component owns the standard viewport, container, navigation,
dots, counter, Turbo cache cleanup and slide geometry; the small controllers below only coordinate application
behavior.

For non-gallery interfaces such as wizards, swipe decks and live timelines, see
[Carousel as a primitive](./carousel-as-primitive.md).

## Recipes

- [Thumbnail navigation](#thumbnail-navigation)
- [Lightbox modal](#lightbox-modal)
- [Infinite slides via Turbo Stream](#infinite-slides-via-turbo-stream)
- [Deep-linkable slides](#deep-linkable-slides)
- [GTM analytics](#gtm-analytics)

## Thumbnail navigation

Use one carousel for the hero and another for the thumbnail strip. A controller around both holds a Stimulus `carousel`
outlet that points specifically to the hero. Thumbnail clicks use that outlet; hero selection events only update
`aria-current`.

```blade
<div data-controller="thumbnails" data-thumbnails-carousel-outlet="#product-gallery">
    <hw:carousel
        id="product-gallery"
        slide-size="100%"
        :navigation="false"
        :dots="false"
        data-action="carousel:select->thumbnails#mark"
    >
        @foreach ($photos as $photo)
            <img src="{{ $photo->url }}" alt="{{ $photo->alt }}" class="w-full" />
        @endforeach
    </hw:carousel>

    <hw:carousel
        slide-size="4rem"
        slide-spacing="0.5rem"
        :slides-to-scroll="1"
        align="start"
        drag-free
        contain-scroll="keepSnaps"
        :navigation="false"
        :dots="false"
        class="mt-2"
        data-action="click->thumbnails#jump"
    >
        @foreach ($photos as $i => $photo)
            <button
                type="button"
                data-thumbnails-target="item"
                data-thumbnails-index-param="{{ $i }}"
                class="size-16 aria-current:ring-2 aria-current:ring-blue-500"
                @if ($loop->first) aria-current="true" @endif
            >
                <img src="{{ $photo->thumb_url }}" alt="{{ $photo->alt }}" class="size-full object-cover" />
            </button>
        @endforeach
    </hw:carousel>
</div>
```

```js
// resources/js/controllers/thumbnails_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["item"];
    static outlets = ["carousel"];

    jump(event) {
        const button = event.target.closest("[data-thumbnails-index-param]");
        if (!button || !this.element.contains(button) || !this.hasCarouselOutlet) return;

        this.carouselOutlet.scrollTo({
            params: { index: Number(button.dataset.thumbnailsIndexParam) },
        });
    }

    mark(event) {
        const index = event.detail.index;
        this.itemTargets.forEach((item, itemIndex) => {
            item.toggleAttribute("aria-current", itemIndex === index);
        });
    }
}
```

`slide-size` supplies the flex basis used by structural CSS. The thumbnail buttons therefore need no `flex-basis`
utility. `mark()` never calls `scrollTo()`, so the hero's `carousel:select` cannot feed back into navigation.

## Lightbox modal

Each slide can be a Turbo Frame link. The carousel component still owns all carousel wiring; the server returns the
matching modal frame.

```blade
<hw:carousel slide-size="100%" :dots="false">
    @foreach ($photos as $photo)
        <a href="{{ route('lightbox.show', $photo) }}" data-turbo-frame="lightbox">
            <img src="{{ $photo->url }}" alt="{{ $photo->alt }}" class="w-full" />
        </a>
    @endforeach
</hw:carousel>

<hw:modal id="lightbox" frame="lightbox" />
```

No carousel coordination is needed when the modal closes. Update the `lightbox` frame with the normal Turbo response.

## Infinite slides via Turbo Stream

Run the threshold check once when Embla becomes ready, then again on every `carousel:select`. The component's `counter`
option stays correct because the controller updates it on Embla's `slidesChanged` event.

```blade
<hw:carousel
    id="photo-feed"
    slide-size="100%"
    counter
    :dots="false"
    :stimulus="
        stimulus()
            ->controller('infinite-slides', [
                'url' => route('photos.more'),
                'threshold' => 2,
            ])
            ->action('infinite-slides', 'maybeLoad', 'carousel:select')
    "
>
    @foreach ($photos as $photo)
        @include('partials.slide', ['photo' => $photo])
    @endforeach
</hw:carousel>
```

```js
// resources/js/controllers/infinite_slides_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        threshold: { type: Number, default: 2 },
    };

    connect() {
        this.connected = true;
        this.initialLoadRan = false;
        this.readyFrame = requestAnimationFrame(() => this.#loadWhenReady());
    }

    disconnect() {
        this.connected = false;
        cancelAnimationFrame(this.readyFrame);
        this.abortController?.abort();
        this.abortController = null;
        this.loading = false;
    }

    async maybeLoad() {
        const embla = this.#embla();
        if (!embla || this.loading || this.exhausted) return;

        const remaining = embla.scrollSnapList().length - 1 - embla.selectedScrollSnap();
        if (remaining > this.thresholdValue) return;

        const lastSlide = this.element.querySelector("[data-carousel-container] > :last-child");
        if (!lastSlide?.dataset.photoId) return;

        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set("after", lastSlide.dataset.photoId);

        this.abortController?.abort();
        const abortController = new AbortController();
        this.abortController = abortController;
        this.loading = true;
        try {
            const response = await fetch(url, {
                headers: { Accept: "text/vnd.turbo-stream.html" },
                credentials: "same-origin",
                signal: abortController.signal,
            });

            if (!this.connected || abortController.signal.aborted) return;
            if (response.status === 204) {
                this.exhausted = true;
                return;
            }
            if (!response.ok) throw new Error(`Could not load slides (${response.status})`);

            const html = await response.text();
            if (!this.connected || abortController.signal.aborted) return;
            window.Turbo.renderStreamMessage(html);
        } catch (error) {
            if (error?.name !== "AbortError") console.error(error);
        } finally {
            if (this.abortController === abortController) {
                this.abortController = null;
                this.loading = false;
            }
        }
    }

    #loadWhenReady() {
        if (this.initialLoadRan) return;

        if (!this.#embla()) {
            this.readyFrame = requestAnimationFrame(() => this.#loadWhenReady());
            return;
        }

        this.initialLoadRan = true;
        this.readyFrame = null;
        void this.maybeLoad();
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

The endpoint appends to the component-generated container with a selector-targeted stream:

```php
public function more(Request $request)
{
    $photos = Photo::query()
        ->where('id', '>', $request->integer('after'))
        ->limit(8)
        ->get();

    if ($photos->isEmpty()) {
        return response()->noContent();
    }

    return turbo_stream()->appendAll(
        '#photo-feed [data-carousel-container]',
        view('partials.slides-batch', compact('photos')),
    );
}
```

```blade
{{-- resources/views/partials/slide.blade.php --}}
<div id="{{ dom_id($photo) }}" data-photo-id="{{ $photo->id }}">
    <picture
        class="bg-muted block aspect-square"
        {{
            stimulus()->controller('lazy-image', [
                'url' => $photo->thumb_url,
                'alt' => $photo->caption,
                'interval' => 1500,
                'imgClass' => 'size-full object-cover',
            ])
        }}
    >
        <hw:spinner class="m-auto" />
    </picture>
</div>
```

Embla's `watchSlides` observes the appended children and remeasures automatically. `dom_id($photo)` keeps each slide
addressable for later updates or removals, while `data-photo-id` supplies the next cursor. For long feeds, use
`carousel:slides-in-view` to defer expensive per-slide work until a slide approaches the viewport. Disconnect aborts an
in-flight request, and the connected guard prevents a late response from rendering a stream into a page that left.

## Deep-linkable slides

`carousel:settle` has no event detail. Resolve Embla in the settled handler and read `selectedScrollSnap()` only after
motion has stopped.

```blade
<hw:carousel
    slide-size="100%"
    :stimulus="
        stimulus()
            ->controller('hash-slides')
            ->action('hash-slides', 'sync', 'carousel:settle')
            ->action('hash-slides', 'restore', 'turbo:load@window')
    "
>
    @foreach ($photos as $photo)
        <img src="{{ $photo->url }}" alt="{{ $photo->alt }}" class="w-full" />
    @endforeach
</hw:carousel>
```

```js
// resources/js/controllers/hash_slides_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.restoreFrame = requestAnimationFrame(() => this.#restoreWhenReady());
    }

    disconnect() {
        cancelAnimationFrame(this.restoreFrame);
    }

    sync() {
        const embla = this.#embla();
        if (!embla) return;

        const index = embla.selectedScrollSnap();
        const hash = index === 0 ? "" : `#slide-${index + 1}`;
        history.replaceState(null, "", `${location.pathname}${location.search}${hash}`);
    }

    restore(embla = this.#embla()) {
        if (!embla) return;

        const match = location.hash.match(/^#slide-(\d+)$/);
        if (match) embla.scrollTo(Number(match[1]) - 1, true);
    }

    #restoreWhenReady() {
        const embla = this.#embla();
        if (!embla) {
            this.restoreFrame = requestAnimationFrame(() => this.#restoreWhenReady());
            return;
        }

        this.restoreFrame = null;
        this.restore(embla);
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

Using `settle` instead of `select` avoids writing transient snaps while the user drags. The cancellable animation-frame
retry handles the initial hash even when lazy controller modules register in an unexpected order; `turbo:load` handles
later Turbo visits.

## GTM analytics

Track a settled slide rather than every intermediate selection. A cancellable animation-frame retry emits both the
mounted event and the initial slide view once the carousel is ready; later settled events retain the same `view` action.

```blade
<hw:carousel
    slide-size="100%"
    :stimulus="
        stimulus()
            ->controller('gtm', ['id' => 'GTM-XXXXXXX'])
            ->controller('carousel-analytics', ['name' => $gallery->slug])
            ->action('carousel-analytics', 'view', 'carousel:settle')
            ->action('carousel-analytics', 'flush', 'turbo:before-visit@window')
            ->action('carousel-analytics', 'flush', 'beforeunload@window')
    "
>
    @foreach ($gallery->slides as $slide)
        <div data-slide-sku="{{ $slide->sku }}">…</div>
    @endforeach
</hw:carousel>
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

    connect() {
        this.mounted = false;
        window.dataLayer ??= [];
        this.mountFrame = requestAnimationFrame(() => this.#trackMountedWhenReady());
    }

    disconnect() {
        cancelAnimationFrame(this.mountFrame);
    }

    view() {
        const embla = this.#embla();
        if (!embla) return;

        const index = embla.selectedScrollSnap();
        this.maxReached = Math.max(this.maxReached, index);
        if (this.seen.has(index)) return;

        this.seen.add(index);
        const slide = this.element.querySelector("[data-carousel-container]")?.children[index];

        window.dataLayer.push({
            event: "carousel_view",
            carousel: this.nameValue,
            index,
            sku: slide?.dataset.slideSku ?? null,
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

    #trackMountedWhenReady() {
        if (this.mounted) return;

        const embla = this.#embla();
        if (!embla) {
            this.mountFrame = requestAnimationFrame(() => this.#trackMountedWhenReady());
            return;
        }

        this.mounted = true;
        this.mountFrame = null;
        window.dataLayer.push({
            event: "carousel_mounted",
            name: this.nameValue,
        });
        this.view();
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

`dataLayer.push()` is synchronous. If you send the final payload to your own Laravel endpoint instead, use an
authenticated keepalive request so Laravel receives explicit JSON and CSRF headers:

```js
function postAnalytics(url, payload) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) return;

    void fetch(url, {
        method: "POST",
        keepalive: true,
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf,
        },
        body: JSON.stringify(payload),
    }).catch(() => {});
}
```

Keep keepalive payloads small. This endpoint does not broadcast, so it does not need an Echo socket header.
