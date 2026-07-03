# Carousel as a primitive

The [`carousel`](../controllers/carousel.md) controller is a snap engine, not just a way to display photos.
The recipes below use it for things that are **not** galleries — multi-step forms, server-driven signage,
swipe decks and real-time presence. Each one composes with the rest of the package (Turbo Streams,
`<hw:optimistic>`, Mercure/SSE) instead of inventing a parallel framework.

For the day-to-day "I want a gallery" patterns (thumbnails, lightbox, infinite, deep-link, analytics) see
[carousel-patterns.md](./carousel-patterns.md).

## Recipes

- [Multi-step wizard](#multi-step-wizard)
- [Server-driven autoplay (broadcast tick)](#server-driven-autoplay-broadcast-tick)
- [Swipe deck with optimistic streams](#swipe-deck-with-optimistic-streams)
- [Real-time presence on slides](#real-time-presence-on-slides)
- [Live ad slot — budget cap, kill switch, mid-session drops](#live-ad-slot--budget-cap-kill-switch-mid-session-drops)
- [Time-travel state — a "git log" history carousel](#time-travel-state--a-git-log-history-carousel)

## Multi-step wizard

A long form gets split into N steps; each slide is a Turbo Frame with one step's fields. The carousel's
snap engine handles transitions, dots show per-step status (`done` / `current` / `error` / `pending`),
the URL fragment deep-links to a step so the browser back button works, and validation failures keep the
user on the same step until the server is happy.

```blade
<div
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['loop' => false, 'duration' => 18, 'containScroll' => 'trimSnaps'],
            ])
            ->controller('wizard', ['totalSteps' => count($steps)])
            ->action('wizard', 'capture', 'carousel:init')
            ->action('wizard', 'sync', 'carousel:settle')
            ->action('wizard', 'restore', 'turbo:load@window')
            ->action('wizard', 'advance', 'turbo:submit-end')
    }}
>
    <p class="text-sm">{{ __('Step') }}
        <span data-wizard-target="current">1</span> / {{ count($steps) }}
    </p>

    <div data-carousel-viewport>
        <div data-carousel-container>
            @foreach ($steps as $i => $step)
                <section class="min-w-0 flex-[0_0_100%]">
                    <turbo-frame id="wizard-step-{{ $i }}" src="{{ route('signup.step', $step->slug) }}">
                        @include('partials.step-skeleton')
                    </turbo-frame>
                </section>
            @endforeach
        </div>
    </div>

    {{-- Custom dot template encodes step state via data-state --}}
    <div {{ stimulus_target('carousel', 'dotList') }} class="mt-4 flex gap-2"></div>
    <template {{ stimulus_target('carousel', 'dotTemplate') }}>
        <button
            type="button"
            class="size-3 rounded-full transition-colors data-[state=done]:bg-emerald-500 data-[state=current]:bg-blue-500 data-[state=error]:bg-rose-500 data-[state=pending]:bg-gray-300"
            data-state="pending"
            data-wizard-target="dot"
            {{ stimulus_action('carousel', 'scrollTo') }}
        ></button>
    </template>
</div>
```

```js
// resources/js/controllers/wizard_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { totalSteps: Number };
    static targets = ["current", "dot"];

    initialize() { this.states = []; }

    capture(event) {
        this.embla = event.detail.embla;
        this.states = Array(this.totalStepsValue).fill("pending");
        this.#paint();
        this.restore();
    }

    sync() {
        const i = this.embla.selectedScrollSnap();
        this.currentTarget.textContent = i + 1;
        history.replaceState(null, "", `#step-${i + 1}`);
        if (this.states[i] === "pending") this.states[i] = "current";
        this.#paint();
    }

    restore() {
        const match = location.hash.match(/^#step-(\d+)$/);
        if (match) this.embla?.scrollTo(parseInt(match[1], 10) - 1, true);
    }

    advance(event) {
        if (!this.embla) return;
        const i = this.embla.selectedScrollSnap();
        if (event.detail.success) {
            this.states[i] = "done";
            this.embla.scrollNext();
        } else {
            this.states[i] = "error";
        }
        this.#paint();
    }

    #paint() {
        const selected = this.embla?.selectedScrollSnap() ?? 0;
        this.dotTargets.forEach((dot, i) => {
            dot.dataset.state =
                this.states[i] === "done" ? "done" :
                this.states[i] === "error" ? "error" :
                i === selected ? "current" : "pending";
        });
    }
}
```

Why this works:

- **Step content is a `<turbo-frame>`** — each step lazily fetches its own form (`src` triggers loading on
  connect), so the initial page payload stays small.
- **`turbo:submit-end` is the truth signal** — Turbo already tells you whether the submit succeeded
  (`event.detail.success`). If true, mark the step done and advance; if false, mark error and stay put. The
  user sees the validation message rendered inside the frame.
- **Dots double as a roadmap** — `data-state` on each dot drives styling via `data-[state=...]` Tailwind
  variants, no class-juggling JS.
- **Deep-link survives reload** — `#step-3` is restored on `turbo:load`, so refresh and browser-back land on
  the right step.

## Server-driven autoplay (broadcast tick)

No `embla-carousel-autoplay` plugin. Instead, a backend job publishes a tick every N seconds via Laravel
Broadcasting (Reverb / Pusher / Soketi / Ably — whichever you have wired up), and all connected viewers
advance in lockstep. Useful for digital signage, kiosk dashboards and keynote screens that need to stay in
sync across devices.

> The carousel side is the only thing this package contributes here. The broadcasting transport and the
> client subscription (Laravel Echo, native EventSource, etc.) are vanilla Laravel concerns — see
> [Laravel Broadcasting](https://laravel.com/docs/broadcasting). What follows shows the carousel hookup
> and the smallest server/client glue that delivers the tick.

The carousel listens for a custom DOM event and reacts:

```blade
<div
    id="hero-banner"
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['loop' => true, 'duration' => 35],
            ])
            ->action('carousel', 'next', 'hero:tick')
    }}
>
    <div data-carousel-viewport>
        <div data-carousel-container>
            @foreach ($slides as $slide)
                <div class="min-w-0 flex-[0_0_100%]">…</div>
            @endforeach
        </div>
    </div>
</div>
```

Server side, the broadcastable event:

```php
// app/Events/HeroTick.php
final class HeroTick implements ShouldBroadcast
{
    public function broadcastOn(): Channel
    {
        return new Channel('hero-banner');
    }
}
```

```php
// app/Console/Kernel.php
$schedule->call(fn () => broadcast(new HeroTick()))->everyTenSeconds();
```

Client side, a single Echo subscription forwards the broadcast as the DOM event the carousel already
listens to:

```js
// resources/js/echo_bridges.js — imported once from app.js
window.Echo.channel('hero-banner').listen('HeroTick', () => {
    document.getElementById('hero-banner')
        ?.dispatchEvent(new CustomEvent('hero:tick', { bubbles: true }));
});
```

Side effects worth knowing:

- **Lockstep across devices** — every connected viewer advances within the same broadcast cycle. For a row
  of TVs running the same dashboard, the visual sync is effectively perfect.
- **Pause is centralized** — turning the schedule off (or gating it behind a feature flag) pauses every
  viewer at once. No client-side `Autoplay.stop()` to chase.
- **Survives drag** — when a user manually swipes, Embla just resets the snap; the next tick still fires.
  If you want server-side resume-after-interaction, broadcast the tick only after a quiet period (the
  job tracks pointer activity posted from clients).

## Swipe deck with optimistic streams

Tinder/Bumble-style decision UI. Each card is a `like`/`dislike` Turbo form; submitting either action
optimistically removes the card via `<hw:optimistic>`, the carousel re-measures (Embla's `watchSlides`)
and the next card is naturally in view. The server confirms the decision asynchronously and rejects with a
flash if the action wasn't allowed.

```blade
<div
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['loop' => false, 'containScroll' => 'trimSnaps'],
            ])
    }}
>
    <div data-carousel-viewport>
        <div data-carousel-container id="swipe-deck">
            @foreach ($candidates as $candidate)
                @include('partials.swipe-card', ['candidate' => $candidate])
            @endforeach
        </div>
    </div>
</div>
```

```blade
{{-- resources/views/partials/swipe-card.blade.php --}}
<article id="{{ dom_id($candidate) }}" class="min-w-0 flex-[0_0_100%] relative">
    <img src="{{ $candidate->photo_url }}" alt="" class="w-full">
    <h2>{{ $candidate->name }}</h2>

    <div class="absolute inset-x-0 bottom-4 flex justify-between px-6">
        <form method="POST" action="{{ route('deck.dispatch', $candidate) }}" data-turbo-frame="_top">
            @csrf
            <input type="hidden" name="decision" value="pass">
            <hw:optimistic action="remove" target="{{ dom_id($candidate) }}" />
            <button type="submit" aria-label="Pass">👎</button>
        </form>

        <form method="POST" action="{{ route('deck.dispatch', $candidate) }}" data-turbo-frame="_top">
            @csrf
            <input type="hidden" name="decision" value="like">
            <hw:optimistic action="remove" target="{{ dom_id($candidate) }}" />
            <button type="submit" aria-label="Like">❤️</button>
        </form>
    </div>
</article>
```

```php
public function dispatchDecision(Request $request, Candidate $candidate)
{
    try {
        $this->authorize('decide', $candidate);
    } catch (\Throwable $e) {
        // Optimistic remove already happened — bring the card back and explain.
        return turbo_stream()
            ->refresh(method: 'morph')
            ->flash('error', __('You hit the daily decision limit.'))
            ->withResponse(429);
    }

    $candidate->recordDecision($request->user(), $request->string('decision'));

    return turbo_stream()
        ->flash('success', $request->string('decision') === 'like' ? __('Liked') : __('Passed'));
}
```

Why this combination works:

- **Optimistic removes the card instantly** — `<hw:optimistic action="remove" target="...">` dispatches
  before the request leaves, so the swipe feels native even on slow networks.
- **Carousel re-measures automatically** — Embla's `watchSlides` MutationObserver picks up the removed node;
  the next card slides into the snap position with the configured duration. No imperative `scrollNext` call.
- **Rejection path uses `refresh(method: 'morph')`** — when the server says "no" (rate limit, blocked,
  policy), the morph response re-paints the deck with the rejected card back in place, and the flash
  explains why.

## Real-time presence on slides

Show small avatars on each dot indicating other viewers currently on that slide. Every `carousel:select`
POSTs the new index; the server broadcasts the updated counts back via Laravel Broadcasting; the receivers
update a presence overlay over each dot. Useful in collaborative galleries, lobby pages for live auctions,
group photo reviews.

> Same caveat as the autoplay recipe — the broadcasting transport (Reverb, Pusher, etc.) and the Echo
> subscription are vanilla Laravel concerns. The recipe shows the carousel-side wiring and the smallest
> glue around it.

```blade
<div
    {{
        stimulus()
            ->controller('carousel')
            ->controller('presence', [
                'endpoint' => route('presence.update', $gallery),
                'channel' => "gallery.{$gallery->id}",
            ])
            ->action('presence', 'announce', 'carousel:settle')
            ->action('presence', 'apply', 'presence:apply')
    }}
>
    <div data-carousel-viewport>
        <div data-carousel-container>…</div>
    </div>

    <div {{ stimulus_target('carousel', 'dotList') }} class="relative"></div>
    <template {{ stimulus_target('carousel', 'dotTemplate') }}>
        <span class="relative inline-block">
            <button
                type="button"
                class="size-3 rounded-full bg-gray-300"
                {{ stimulus_action('carousel', 'scrollTo') }}
            ></button>
            <span
                class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px] hidden"
                data-presence-target="dotOverlay"
            ></span>
        </span>
    </template>
</div>
```

```js
// resources/js/controllers/presence_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { endpoint: String, channel: String };
    static targets = ["dotOverlay"];

    connect() {
        this.subscription = window.Echo.channel(this.channelValue)
            .listen('PresenceChanged', (payload) => {
                this.element.dispatchEvent(
                    new CustomEvent('presence:apply', { detail: payload.counts, bubbles: true })
                );
            });
    }

    disconnect() {
        window.Echo.leave(this.channelValue);
    }

    announce(event) {
        navigator.sendBeacon(this.endpointValue, JSON.stringify({ index: event.detail.index }));
    }

    apply(event) {
        const counts = event.detail; // [{ index: 0, count: 3 }, { index: 2, count: 1 }]
        this.dotOverlayTargets.forEach((node) => {
            node.textContent = "";
            node.classList.add("hidden");
        });
        for (const { index, count } of counts) {
            const node = this.dotOverlayTargets[index];
            if (node) {
                node.textContent = `👤×${count}`;
                node.classList.remove("hidden");
            }
        }
    }
}
```

Server side, the POST handler updates a Redis-backed presence map and fires a broadcastable event with the
new snapshot:

```php
public function update(Request $request, Gallery $gallery)
{
    Presence::move($gallery, $request->user()->id, $request->integer('index'));

    broadcast(new PresenceChanged(
        gallery: $gallery,
        counts: Presence::countsFor($gallery),
    ))->toOthers();

    return response()->noContent();
}
```

```php
final class PresenceChanged implements ShouldBroadcast
{
    public function __construct(
        public Gallery $gallery,
        public array $counts,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("gallery.{$this->gallery->id}");
    }
}
```

Trade-offs to know:

- **Drag noise** — wire to `carousel:settle` (as above), not `carousel:select`, so a single drag-through
  doesn't beacon every intermediate snap.
- **Privacy** — the channel name `gallery.{id}` is public; switch to `PrivateChannel` plus an auth
  callback in `routes/channels.php` for tenant-scoped galleries.
- **Cleanup** — when a user navigates away, beacon a "disconnect" on `turbo:before-visit@window` and
  `beforeunload@window`, otherwise stale counts linger until a TTL expires.
- **`toOthers()`** — the broadcasting helper skips the originator, so the user that just moved doesn't
  receive their own update bouncing back.

## Live ad slot — budget cap, kill switch, mid-session drops

An ad slot rotates N creatives. Real-world ad ops needs three things that a static carousel can't do:

- **Launch a creative live** — sales closes a deal at 21:00 sharp; the new banner enters the rotation on
  every open page without anyone reloading.
- **Pull a creative when its budget caps** — the moment the campaign hits its impression budget on the
  server, every live viewer loses that slide.
- **Compliance kill switch** — a flagged ad gets yanked from every page in under a second.

Three Laravel-broadcast events, one sister controller, and the carousel's `watchSlides` (which Embla turns
on by default) take care of the re-measure.

```blade
<div
    {{
        stimulus()
            ->controller('carousel', ['options' => ['loop' => true, 'duration' => 30]])
            ->controller('ad-slot', [
                'channel' => "ads.{$slot->key}",
                'impressionEndpoint' => route('ads.impression', $slot),
            ])
            ->action('ad-slot', 'beacon', 'carousel:settle')
    }}
>
    <div data-carousel-viewport>
        <div data-carousel-container>
            @foreach ($slot->liveCreatives() as $creative)
                @include('partials.ad-creative', ['creative' => $creative])
            @endforeach
        </div>
    </div>
</div>
```

```blade
{{-- resources/views/partials/ad-creative.blade.php --}}
<a
    id="{{ dom_id($creative) }}"
    href="{{ $creative->landing_url }}"
    data-creative-id="{{ $creative->id }}"
    target="_blank"
    rel="sponsored"
    class="min-w-0 flex-[0_0_100%] block"
>
    <img src="{{ $creative->image_url }}" alt="{{ $creative->headline }}">
</a>
```

```js
// resources/js/controllers/ad_slot_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { channel: String, impressionEndpoint: String };

    connect() {
        this.sub = window.Echo.channel(this.channelValue)
            .listen('CreativeLaunched', ({ html }) => {
                this.#container().insertAdjacentHTML('beforeend', html);
            })
            .listen('CreativePulled', ({ creativeId }) => {
                this.#container()
                    .querySelector(`[data-creative-id="${creativeId}"]`)
                    ?.remove();
            });
    }

    disconnect() {
        window.Echo.leave(this.channelValue);
    }

    beacon(event) {
        const creativeId = this.#container().children[event.detail.index]?.dataset.creativeId;
        if (creativeId) {
            navigator.sendBeacon(
                this.impressionEndpointValue,
                JSON.stringify({ creative_id: creativeId }),
            );
        }
    }

    #container() {
        return this.element.querySelector('[data-carousel-target="container"]');
    }
}
```

Server side — two broadcastable events that ad ops fires from a dashboard, a cron, or as a side-effect of
the impression counter hitting the budget:

```php
final class CreativeLaunched implements ShouldBroadcast
{
    public function __construct(public AdSlot $slot, public AdCreative $creative) {}

    public function broadcastOn(): Channel
    {
        return new Channel("ads.{$this->slot->key}");
    }

    public function broadcastWith(): array
    {
        return [
            'html' => view('partials.ad-creative', ['creative' => $this->creative])->render(),
        ];
    }
}

final class CreativePulled implements ShouldBroadcast
{
    public function __construct(public AdSlot $slot, public int $creativeId) {}

    public function broadcastOn(): Channel
    {
        return new Channel("ads.{$this->slot->key}");
    }

    public function broadcastWith(): array
    {
        return ['creativeId' => $this->creativeId];
    }
}
```

And the impression endpoint that also enforces the budget:

```php
public function impression(Request $request, AdSlot $slot)
{
    $creative = AdCreative::findOrFail($request->integer('creative_id'));
    $creative->logImpression($request);

    if ($creative->campaign->budgetExhausted()) {
        broadcast(new CreativePulled($slot, $creative->id));
    }

    return response()->noContent();
}
```

Why this composition wins:

- **Live drops without reload** — `broadcast(new CreativeLaunched($slot, $creative))` is one line of
  server code; every open page picks the new creative up within the broadcast latency (tens of ms on
  Reverb / a Pusher channel).
- **Budget is server-truth** — clients don't poll, don't compute, don't decide. The campaign caps when
  the impression count says so on the server, and the broadcast yanks it from every viewer at once.
- **Compliance is one event** — a "remove now" button in a moderation dashboard runs
  `broadcast(new CreativePulled($slot, $creativeId))` and that's it.
- **Impressions on `carousel:settle`** — only count slides the user actually saw at rest, not a frame
  during a drag-through. Same anti-noise pattern as the
  [analytics recipe](./carousel-patterns.md#tracking-with-gtm-analytics).
- **Embla handles the rest** — `watchSlides` re-measures when nodes are inserted/removed; the rotation
  keeps going without an imperative `reInit`.

Trade-offs to know:

- **HTML on the wire** — sending rendered creative HTML over the channel is convenient (the controller
  just appends), but means the broadcast worker has to render Blade. For heavier creatives
  (rich-media, video poster + analytics tags), broadcast just an `{ url }` and have the controller
  `fetch(url)` to hydrate it.
- **Tenant scoping** — public `Channel(...)` is fine for global house ads. For tenant-scoped slots
  swap to `PrivateChannel("ads.{$tenant}.{$slot->key}")` and wire `routes/channels.php`.
- **Removing the current snap** — if the user is dwelling on a slide that gets pulled mid-view, Embla
  snaps to the nearest neighbor automatically. If you'd rather defer the removal until they scroll
  past, queue the pulled IDs and apply them inside the `carousel:select` action instead of right away.
- **Sponsored attribute** — `rel="sponsored"` on the link is what tells search engines the click is
  paid; couple with `target="_blank"` so the host page navigation isn't lost to a click-out.

## Time-travel state — a "git log" history carousel

Every state-mutating action in the app (saving a draft, marking a task done, applying a filter, dragging a
kanban card) writes a snapshot of the affected DOM region to the server and broadcasts it. A small floating
carousel in the corner of the screen shows those snapshots as visual thumbnails — newest pinned to the right,
older ones scrolling off to the left. Click any past snapshot to **restore the page to that exact state**,
optimistically morphed in, server-confirmed via Turbo Stream.

Because the snapshots live on the server and ride a broadcast channel, **anyone watching the same channel
sees the same history in real time** — a teammate's save appears in your history feed, and you can revert
their change. It's git log + git reset, in the corner of every page, with zero new infrastructure beyond
what this stack already provides.

```blade
<div
    {{
        stimulus()
            ->controller('carousel', [
                'options' => ['loop' => false, 'align' => 'end', 'containScroll' => 'trimSnaps', 'dragFree' => true],
            ])
            ->controller('time-travel', ['channel' => "history.{$document->channel_key}"])
            ->action('time-travel', 'tail', 'carousel:slides-changed')
    }}
    class="fixed bottom-4 right-4 w-96 bg-white shadow-xl rounded-lg p-2 z-50"
>
    <p class="text-xs text-gray-500 mb-1 flex items-center gap-2">
        <span>{{ __('Recent changes') }}</span>
        <span data-time-travel-target="counter" class="text-gray-400">({{ $snapshots->count() }})</span>
    </p>

    <div data-carousel-viewport>
        <div data-carousel-container>
            @foreach ($snapshots as $snapshot)
                @include('partials.history-snapshot', ['snapshot' => $snapshot])
            @endforeach
        </div>
    </div>
</div>
```

```blade
{{-- resources/views/partials/history-snapshot.blade.php --}}
<form
    id="{{ dom_id($snapshot) }}"
    method="POST"
    action="{{ route('snapshots.restore', $snapshot) }}"
    data-controller="optimistic--form"
    data-turbo-frame="_top"
    class="min-w-0 flex-[0_0_auto] mr-2"
>
    @csrf

    {{-- Optimistic morph: replace the live editor with this snapshot's HTML
         before the round-trip even leaves. --}}
    <hw:optimistic target="editor" action="replace">
        {!! $snapshot->html !!}
    </hw:optimistic>

    <button type="submit"
            class="block w-24 h-32 border rounded overflow-hidden hover:ring-2 hover:ring-blue-500 bg-white">
        <div class="origin-top-left scale-[0.15] w-[660%] h-[660%] pointer-events-none p-2 text-[8px]">
            {!! $snapshot->html !!}
        </div>
    </button>

    <p class="text-[10px] text-center mt-1 text-gray-500">
        {{ $snapshot->author?->name ?? __('You') }} · {{ $snapshot->created_at->diffForHumans() }}
    </p>
</form>
```

```js
// resources/js/controllers/time_travel_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { channel: String };
    static targets = ["counter"];

    connect() {
        this.echo = window.Echo.channel(this.channelValue)
            .listen('SnapshotCaptured', ({ html }) => {
                this.#container().insertAdjacentHTML('beforeend', html);
                this.#bumpCounter(+1);
            });
    }

    disconnect() {
        window.Echo.leave(this.channelValue);
    }

    tail() {
        const embla = this.#carousel()?.embla;
        if (embla) embla.scrollTo(embla.scrollSnapList().length - 1);
    }

    #bumpCounter(delta) {
        if (!this.hasCounterTarget) return;
        const n = parseInt(this.counterTarget.textContent.replace(/\D/g, ''), 10) + delta;
        this.counterTarget.textContent = `(${n})`;
    }

    #container() {
        return this.element.querySelector('[data-carousel-target="container"]');
    }

    #carousel() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel");
    }
}
```

Server side — a model observer captures the snapshot, a broadcastable event ships it, the restore endpoint
turns it back into reality:

```php
// app/Observers/DocumentObserver.php
public function saved(Document $document): void
{
    $snapshot = Snapshot::create([
        'channel_key' => $document->channel_key,
        'target_id' => 'editor',
        'author_id' => Auth::id(),
        'payload' => $document->only(['title', 'body', 'tags']),
        'html' => view('partials.editor', ['document' => $document])->render(),
    ]);

    broadcast(new SnapshotCaptured($snapshot))->toOthers();
}
```

```php
final class SnapshotCaptured implements ShouldBroadcast
{
    public function __construct(public Snapshot $snapshot) {}

    public function broadcastOn(): Channel
    {
        return new Channel("history.{$this->snapshot->channel_key}");
    }

    public function broadcastWith(): array
    {
        return [
            'html' => view('partials.history-snapshot', ['snapshot' => $this->snapshot])->render(),
        ];
    }
}
```

```php
public function restore(Snapshot $snapshot)
{
    Gate::authorize('restore', $snapshot);

    $document = $snapshot->document;
    $document->update($snapshot->payload);

    return turbo_stream()
        ->replace('editor', view('partials.editor', compact('document')))
        ->flash('success', __('Restored to :when', [
            'when' => $snapshot->created_at->diffForHumans(),
        ]));
    // The save itself fires DocumentObserver::saved → a new SnapshotCaptured
    // broadcasts to the channel → history grows with this restore action,
    // so "undo the undo" is just restoring an older snapshot.
}
```

Why this composition is more than the sum of its parts:

- **Carousel as undo navigator** — no one expects a carousel to be the UI for time-travel, but the snap
  engine + visual thumbnails + horizontal scroll are exactly what a "scrubbable history" wants. `align: 'end'`
  + `dragFree: true` lets the user flick back through the timeline with a thumb gesture, then snap forward
  again.
- **Optimistic restore feels instant** — `<hw:optimistic target="editor" action="replace">` swaps the
  live editor's HTML in the same tick the user clicks. The server's confirming Turbo Stream `replace` is
  a no-op visually if the optimistic morph already matched — Turbo's morph reconciles on identical DOM.
- **Multiplayer audit for free** — the channel name `history.{document}` is per-document, not per-user.
  A teammate's save appears in your timeline; you can revert their change with one click. Pair with the
  [presence recipe](#real-time-presence-on-slides) for "who is editing right now" indicators on the same
  surface.
- **Restoring an old snapshot creates a new snapshot** — the restore endpoint calls `$document->update(...)`,
  which fires the observer, which broadcasts a fresh `SnapshotCaptured`. The timeline never loses data, it
  grows. "Undo the undo" is just restoring an earlier snapshot — no special redo state to track.
- **Per-document broadcast scope** — each document's history rides its own channel, so the timeline
  controller only receives the snapshots that matter to the open document. No cross-document noise.

Trade-offs to know:

- **Snapshot storage** — each save writes a row with rendered HTML. For high-frequency mutations (drag-heavy
  kanban boards, live spreadsheet cells), throttle on the server (one snapshot per N seconds per
  channel+author) or store payload only and re-render HTML at restore time.
- **Auto-scroll bumps the user** — `tail()` on every `slides-changed` snaps the carousel to the latest. If
  a user is scrolled back inspecting old states, the bump is rude. Detect "near the right edge" before
  calling `scrollTo` (e.g. only auto-scroll if the user was on the last snap before the change).
- **Authorization on restore** — anyone receiving the channel can see the snapshots, but the restore endpoint
  is the security boundary. `Gate::authorize('restore', $snapshot)` decides who actually mutates state;
  read-only viewers see the timeline but their POSTs fail.
- **Memory + DOM size** — every snapshot is full HTML in the DOM (twice: optimistic template + thumbnail).
  For long sessions, cap the visible window (oldest snapshots removed from the DOM after N) and let users
  page deeper via a "Load older" Turbo Frame.
- **`->toOthers()`** — the broadcast skips the originator since their own snapshot is already in the DOM
  via the Turbo Stream of the save. Without it, the originator would see a duplicate.
