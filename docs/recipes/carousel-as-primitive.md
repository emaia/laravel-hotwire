# Carousel as a primitive

[`<hw:carousel>`](../components/carousel.md) can provide a snap engine for interfaces that are not galleries. These
recipes keep the component's current wiring and compose it with Turbo, optimistic streams and Laravel Broadcasting.

For thumbnail, lightbox, infinite-feed, deep-link and analytics examples, see
[Carousel patterns](./carousel-patterns.md).

## Recipes

- [Multi-step wizard](#multi-step-wizard)
- [Server-driven autoplay](#server-driven-autoplay)
- [Swipe deck with optimistic streams](#swipe-deck-with-optimistic-streams)
- [Real-time presence](#real-time-presence)
- [Live ad slot](#live-ad-slot)
- [Time-travel history](#time-travel-history)

## Multi-step wizard

Each slide is a lazy Turbo Frame. The component renders the track and dots, while a `wizard` controller records step
state and advances only after a successful Turbo submission.

```blade
<hw:carousel
    slide-size="100%"
    :navigation="false"
    counter
    :options="['duration' => 18]"
    dot-list-class="mt-4 flex gap-2"
    dot-class="size-3 rounded-full transition-colors data-[state=done]:bg-emerald-500 data-[state=current]:bg-blue-500 data-[state=error]:bg-rose-500 data-[state=pending]:bg-gray-300"
    :stimulus="
        stimulus()
            ->controller('wizard', ['totalSteps' => count($steps)])
            ->action('wizard', 'sync', 'carousel:settle')
            ->action('wizard', 'restore', 'turbo:load@window')
            ->action('wizard', 'advance', 'turbo:submit-end')
    "
>
    @foreach ($steps as $i => $step)
        <section>
            <turbo-frame id="wizard-step-{{ $i }}" src="{{ route('signup.step', $step->slug) }}" loading="lazy">
                @include('partials.step-skeleton')
            </turbo-frame>
        </section>
    @endforeach

    <x-slot:dot_template data-wizard-target="dot" data-state="pending"></x-slot>
</hw:carousel>
```

```js
// resources/js/controllers/wizard_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { totalSteps: Number };
    static targets = ["dot"];

    connect() {
        this.initialized = false;
        this.initializeFrame = requestAnimationFrame(() => this.#initializeWhenReady());
    }

    disconnect() {
        cancelAnimationFrame(this.initializeFrame);
    }

    sync() {
        const embla = this.#embla();
        if (!embla) return;
        this.#initialize(embla);

        const index = embla.selectedScrollSnap();
        history.replaceState(null, "", `#step-${index + 1}`);
        this.#paint(embla);
    }

    restore(embla = this.#embla()) {
        if (!embla) return;

        const match = location.hash.match(/^#step-(\d+)$/);
        if (match) embla.scrollTo(Number(match[1]) - 1, true);
    }

    advance(event) {
        const embla = this.#embla();
        if (!embla || event.target.closest("turbo-frame") === null) return;
        this.#initialize(embla);

        const index = embla.selectedScrollSnap();
        this.states[index] = event.detail.success ? "done" : "error";
        if (event.detail.success) embla.scrollNext();
        this.#paint(embla);
    }

    #initializeWhenReady() {
        const embla = this.#embla();
        if (!embla) {
            this.initializeFrame = requestAnimationFrame(() => this.#initializeWhenReady());
            return;
        }

        this.initializeFrame = null;
        this.#initialize(embla);
    }

    #initialize(embla) {
        if (this.initialized) return;

        this.initialized = true;
        cancelAnimationFrame(this.initializeFrame);
        this.initializeFrame = null;
        this.states = Array(this.totalStepsValue).fill("pending");
        this.restore(embla);
        this.#paint(embla);
    }

    #paint(embla) {
        const selected = embla.selectedScrollSnap();
        this.dotTargets.forEach((dot, index) => {
            dot.dataset.state =
                this.states[index] === "done"
                    ? "done"
                    : this.states[index] === "error"
                      ? "error"
                      : index === selected
                        ? "current"
                        : "pending";
        });
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

`carousel:settle` has no index detail, so `sync()` resolves the live Embla instance when the event arrives. A cancellable
animation-frame retry handles initial state and hash restoration when lazy controller modules register out of order.
The dot slot's attributes merge onto each generated dot button; no raw dot list or template is needed.

## Server-driven autoplay

A Laravel broadcast can advance every connected display in the same cycle without an autoplay plugin.

```blade
<hw:carousel
    id="hero-banner"
    slide-size="100%"
    loop
    :navigation="false"
    :dots="false"
    :options="['duration' => 35]"
    data-action="hero:tick->carousel#next"
>
    @foreach ($slides as $slide)
        <div>…</div>
    @endforeach
</hw:carousel>
```

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

Schedule the broadcast in Laravel's current console routes file:

```php
// routes/console.php
use App\Events\HeroTick;
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => broadcast(new HeroTick()))->everyTenSeconds();
```

```js
// resources/js/echo_bridges.js, imported once from app.js
window.Echo.channel("hero-banner").listen("HeroTick", () => {
    document.getElementById("hero-banner")?.dispatchEvent(new CustomEvent("hero:tick", { bubbles: true }));
});
```

This centralizes pause and kill-switch behavior on the server. A manual swipe does not break the schedule; the next
tick advances from the user's current snap.

## Swipe deck with optimistic streams

Each decision form removes its card before the request leaves. Both forms must mount `optimistic--form`; the
`<hw:optimistic>` template alone does not dispatch anything.

```blade
<hw:carousel id="swipe-deck" slide-size="100%" align="start" :navigation="false" :dots="false">
    @foreach ($candidates as $candidate)
        @include('partials.swipe-card', ['candidate' => $candidate])
    @endforeach
</hw:carousel>
```

```blade
{{-- resources/views/partials/swipe-card.blade.php --}}
<article id="{{ dom_id($candidate) }}" class="relative">
    <img src="{{ $candidate->photo_url }}" alt="" class="w-full" />
    <h2>{{ $candidate->name }}</h2>

    <div class="absolute inset-x-0 bottom-4 flex justify-between px-6">
        <form
            method="POST"
            action="{{ route('deck.dispatch', $candidate) }}"
            data-controller="optimistic--form"
            data-turbo-frame="_top"
        >
            @csrf
            <input type="hidden" name="decision" value="pass" />
            <hw:optimistic action="remove" target="{{ dom_id($candidate) }}" />
            <button type="submit" aria-label="Pass">Pass</button>
        </form>

        <form
            method="POST"
            action="{{ route('deck.dispatch', $candidate) }}"
            data-controller="optimistic--form"
            data-turbo-frame="_top"
        >
            @csrf
            <input type="hidden" name="decision" value="like" />
            <hw:optimistic action="remove" target="{{ dom_id($candidate) }}" />
            <button type="submit" aria-label="Like">Like</button>
        </form>
    </div>
</article>
```

```php
use Illuminate\Auth\Access\AuthorizationException;

public function dispatchDecision(Request $request, Candidate $candidate)
{
    $decision = $request->validate([
        'decision' => ['required', 'string', 'in:like,pass'],
    ])['decision'];

    try {
        $this->authorize('decide', $candidate);
    } catch (AuthorizationException) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->toast('error', __('You hit the daily decision limit.'))
            ->withResponse(429);
    }

    $candidate->recordDecision($request->user(), $decision);

    return turbo_stream()->toast(
        'success',
        $decision === 'like' ? __('Liked') : __('Passed'),
    );
}
```

The endpoint accepts only the plain string decisions `like` and `pass`, and only the expected authorization denial is
converted into a recovery response; unexpected failures still propagate. Embla's default `watchSlides` observes the
optimistic removal and remeasures the track. On rejection, the morph refresh restores the server-authoritative deck and
its rejected card.

## Real-time presence

Show a count inside each generated dot. Announce the initial snap once Embla becomes ready, then announce later settled
snaps with Laravel authentication and CSRF protection.

```blade
<hw:carousel
    slide-size="100%"
    dot-class="relative size-3 rounded-full bg-muted aria-current:bg-primary"
    :stimulus="
        stimulus()
            ->controller('presence', [
                'endpoint' => route('presence.update', $gallery),
                'channel' => sprintf('gallery.%s', $gallery->id),
            ])
            ->action('presence', 'announce', 'carousel:settle')
    "
>
    @foreach ($gallery->slides as $slide)
        <div>…</div>
    @endforeach

    <x-slot:dot_template>
        <span
            data-presence-target="dotOverlay"
            class="absolute -top-5 left-1/2 -translate-x-1/2 text-[10px]"
            hidden
        ></span>
    </x-slot>
</hw:carousel>
```

```js
// resources/js/controllers/presence_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { endpoint: String, channel: String };
    static targets = ["dotOverlay"];

    connect() {
        this.initialAnnounceRan = false;
        this.readyFrame = requestAnimationFrame(() => this.#announceWhenReady());
        this.subscription = window.Echo.channel(this.channelValue).listen("PresenceChanged", ({ counts }) =>
            this.#apply(counts),
        );
    }

    disconnect() {
        cancelAnimationFrame(this.readyFrame);
        window.Echo.leave(this.channelValue);
    }

    announce() {
        const embla = this.#embla();
        if (!embla) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrf) return;

        const headers = {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf,
        };

        void fetch(this.endpointValue, {
            method: "POST",
            keepalive: true,
            credentials: "same-origin",
            headers,
            body: JSON.stringify({ index: embla.selectedScrollSnap() }),
        }).catch(() => {});
    }

    #apply(counts) {
        this.dotOverlayTargets.forEach((node) => {
            node.textContent = "";
            node.hidden = true;
        });

        for (const { index, count } of counts) {
            const node = this.dotOverlayTargets[index];
            if (!node) continue;
            node.textContent = String(count);
            node.hidden = false;
        }
    }

    #announceWhenReady() {
        if (this.initialAnnounceRan) return;

        if (!this.#embla()) {
            this.readyFrame = requestAnimationFrame(() => this.#announceWhenReady());
            return;
        }

        this.initialAnnounceRan = true;
        this.readyFrame = null;
        this.announce();
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

```php
use App\Http\Controllers\GalleryPresenceController;
use Illuminate\Support\Facades\Route;

Route::post('/galleries/{gallery}/presence', [GalleryPresenceController::class, 'update'])
    ->middleware('auth')
    ->name('presence.update');
```

```php
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

public function update(Request $request, Gallery $gallery)
{
    Gate::authorize('view', $gallery);

    $slideCount = $gallery->slides()->count();
    $validIndexes = $slideCount === 0 ? [] : range(0, $slideCount - 1);
    $validated = $request->validate([
        'index' => ['required', 'integer', Rule::in($validIndexes)],
    ]);

    Presence::move($gallery, $request->user()->id, $validated['index']);

    broadcast(new PresenceChanged(
        gallery: $gallery,
        counts: Presence::countsFor($gallery),
    ));

    return response()->noContent();
}
```

The route requires authentication, the policy protects the gallery, and the accepted index cannot exceed its current
slide count. The event broadcasts to every subscriber, including the posting browser, so the originator receives the
same server-authoritative counts as everyone else. No Echo socket header or `toOthers()` call is used. Use a
`PrivateChannel` and `routes/channels.php` authorization for tenant-scoped galleries, and expire stale presence entries
with a server-side TTL.

## Live ad slot

A broadcast can append a newly launched creative or remove one that reached its budget. Embla observes both mutations
and rebuilds its snaps without an imperative `reInit()`. The controller records the initial creative once Embla is
ready, then records later settled creatives through the existing action.

```blade
<hw:carousel
    slide-size="100%"
    loop
    :navigation="false"
    :dots="false"
    :options="['duration' => 30]"
    :stimulus="
        stimulus()
            ->controller('ad-slot', [
                'channel' => sprintf('ads.%s', $slot->key),
                'impressionEndpoint' => route('ads.impression', $slot),
            ])
            ->action('ad-slot', 'recordImpression', 'carousel:settle')
    "
>
    @foreach ($slot->liveCreatives() as $creative)
        @include('partials.ad-creative', ['creative' => $creative])
    @endforeach
</hw:carousel>
```

```blade
{{-- resources/views/partials/ad-creative.blade.php --}}
<a
    id="{{ dom_id($creative) }}"
    href="{{ $creative->landing_url }}"
    data-creative-id="{{ $creative->id }}"
    target="_blank"
    rel="sponsored"
    class="block"
>
    <img src="{{ $creative->image_url }}" alt="{{ $creative->headline }}" />
</a>
```

```js
// resources/js/controllers/ad_slot_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { channel: String, impressionEndpoint: String };

    connect() {
        this.initialImpressionRan = false;
        this.readyFrame = requestAnimationFrame(() => this.#recordInitialImpressionWhenReady());
        this.subscription = window.Echo.channel(this.channelValue)
            .listen("CreativeLaunched", ({ html }) => {
                this.#container()?.insertAdjacentHTML("beforeend", html);
            })
            .listen("CreativePulled", ({ creativeId }) => {
                this.#container()?.querySelector(`[data-creative-id="${creativeId}"]`)?.remove();
            });
    }

    disconnect() {
        cancelAnimationFrame(this.readyFrame);
        window.Echo.leave(this.channelValue);
    }

    recordImpression() {
        const embla = this.#embla();
        if (!embla) return;

        const creative = this.#container()?.children[embla.selectedScrollSnap()];
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!creative?.dataset.creativeId || !csrf) return;

        void fetch(this.impressionEndpointValue, {
            method: "POST",
            keepalive: true,
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrf,
            },
            body: JSON.stringify({ creative_id: creative.dataset.creativeId }),
        }).catch(() => {});
    }

    #container() {
        return this.element.querySelector("[data-carousel-container]");
    }

    #recordInitialImpressionWhenReady() {
        if (this.initialImpressionRan) return;

        if (!this.#embla()) {
            this.readyFrame = requestAnimationFrame(() => this.#recordInitialImpressionWhenReady());
            return;
        }

        this.initialImpressionRan = true;
        this.readyFrame = null;
        this.recordImpression();
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

The impression endpoint remains the budget authority:

```php
public function impression(Request $request, AdSlot $slot)
{
    $validated = $request->validate([
        'creative_id' => ['required', 'integer'],
    ]);

    $this->authorize('view', $slot);

    $creative = $slot->creatives()->findOrFail($validated['creative_id']);
    $creative->logImpression($request);

    if ($creative->campaign->budgetExhausted()) {
        broadcast(new CreativePulled($slot, $creative->id));
    }

    return response()->noContent();
}
```

The endpoint validates the ID, authorizes access to the slot, and scopes the creative lookup through that slot before
recording anything. `CreativeLaunched` should broadcast rendered `partials.ad-creative` HTML; `CreativePulled` only
needs the creative ID. For tenant-scoped inventory, use a private channel. Keep the impression payload small because
browser keepalive requests have a limited body budget.

## Time-travel history

A compact carousel can act as a visual history scrubber. New snapshots arrive over Broadcasting, and restoring one
performs an immediate optimistic replacement followed by a server-authoritative Turbo morph.

```blade
<aside class="bg-background fixed right-4 bottom-4 w-96 rounded-lg p-2 shadow-xl">
    <p class="text-muted-foreground mb-1 text-xs">{{ __('Recent changes') }}</p>

    <hw:carousel
        slide-size="6.5rem"
        slide-spacing="0.5rem"
        :slides-to-scroll="1"
        align="end"
        drag-free
        counter
        :navigation="false"
        :dots="false"
        :stimulus="
            stimulus()
                ->controller('time-travel', [
                    'channel' => sprintf('history.%s', $document->channel_key),
                ])
                ->action('time-travel', 'tail', 'carousel:slides-changed')
        "
    >
        @foreach ($snapshots as $snapshot)
            @include('partials.history-snapshot', ['snapshot' => $snapshot])
        @endforeach
    </hw:carousel>
</aside>
```

```blade
{{-- resources/views/partials/history-snapshot.blade.php --}}
<form
    id="{{ dom_id($snapshot) }}"
    method="POST"
    action="{{ route('snapshots.restore', $snapshot) }}"
    data-controller="optimistic--form"
    data-turbo-frame="_top"
>
    @csrf

    {{-- Replace immediately; the server response below performs the confirming morph. --}}
    <hw:optimistic target="editor" action="replace">
        {!! $snapshot->html !!}
    </hw:optimistic>

    <button type="submit" class="bg-background block h-32 w-24 overflow-hidden rounded border">
        <span class="sr-only">{{ __('Restore snapshot from :when', ['when' => $snapshot->created_at]) }}</span>
        <span class="pointer-events-none block origin-top-left scale-[0.15] text-[8px]">
            {!! $snapshot->html !!}
        </span>
    </button>
</form>
```

Both `{!! $snapshot->html !!}` expressions and the broadcast `insertAdjacentHTML()` assume trusted server-rendered
markup. Escape user fields in the Blade partial and sanitize any intentionally supported rich HTML before storing the
snapshot; never persist or broadcast arbitrary raw user input.

```js
// resources/js/controllers/time_travel_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = { channel: String };

    connect() {
        this.subscription = window.Echo.private(this.channelValue).listen("SnapshotCaptured", ({ html }) => {
            this.#container()?.insertAdjacentHTML("beforeend", html);
        });
    }

    disconnect() {
        window.Echo.leave(this.channelValue);
    }

    tail() {
        const embla = this.#embla();
        if (!embla) return;

        const last = embla.scrollSnapList().length - 1;
        if (last < 0) return;

        const previousLast = Math.max(0, last - 1);
        const nearPreviousEnd = Math.max(0, previousLast - 1);
        if (embla.selectedScrollSnap() < nearPreviousEnd) return;

        embla.scrollTo(last);
    }

    #container() {
        return this.element.querySelector("[data-carousel-container]");
    }

    #embla() {
        return this.application.getControllerForElementAndIdentifier(this.element, "carousel")?.embla;
    }
}
```

The broadcast event uses a `PrivateChannel`:

```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): PrivateChannel
{
    return new PrivateChannel("history.{$this->snapshot->channel_key}");
}
```

Authorize that channel in `routes/channels.php` with the same document access policy:

```php
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('history.{channelKey}', function (User $user, string $channelKey): bool {
    $document = Document::where('channel_key', $channelKey)->first();

    return $document !== null && $user->can('view', $document);
});
```

The observer stores the document payload and trusted rendered editor HTML, then broadcasts a rendered
`partials.history-snapshot` row. The restore endpoint applies that payload and explicitly asks Turbo to morph the
replacement:

```php
public function restore(Snapshot $snapshot)
{
    Gate::authorize('restore', $snapshot);

    $document = $snapshot->document;
    $document->update($snapshot->payload);

    return turbo_stream()
        ->replace(
            'editor',
            view('partials.editor', compact('document')),
            method: 'morph',
        )
        ->toast('success', __('Snapshot restored.'));
}
```

The optimistic action is a normal Turbo Stream `replace`; the confirming response is the actual morph. Updating the
document can create and broadcast another snapshot through the same observer, making an undo itself part of history.
`tail()` follows an appended snapshot only when the selection is on the previous last snap or one snap before it. Cap
the visible window for long sessions and authorize every restore.
