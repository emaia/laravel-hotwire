# Accordion

Two paths depending on whether the accordion's state needs JavaScript involvement at all.

- **Static FAQ-style accordion?** Lead with native `<details>` / `<summary>`. The browser gives
  you the disclosure widget, ARIA, and keyboard handling for free.
- **State driven from JS or the server?** Use the [`disclosure` controller](../controllers/disclosure.md)
  to compose patterns like single-open accordions, programmatic open via outlets, server-rendered
  initial state, or URL-driven sections.

The native path covers most FAQ-style cases. The controller path takes over when you need to
*react* to open/close or *drive* it from outside.

---

## Start here — Native `<details>`

```html

<section class="accordion divide-y divide-gray-200 rounded border border-gray-200">
    <details class="group">
        <summary class="flex w-full cursor-pointer items-center justify-between gap-4 p-4
                        text-left text-sm font-medium hover:underline
                        focus-visible:outline focus-visible:outline-2">
            Is it accessible?
            <svg viewBox="0 0 24 24" class="size-4 shrink-0 transition-transform
                                            group-open:rotate-180">
                <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2"
                      stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
        </summary>
        <div class="p-4 pt-0 text-sm">
            Yes. The browser handles the WAI-ARIA disclosure pattern, focus, and keyboard.
        </div>
    </details>
    {{-- repeat <details> per item --}}
</section>
```

Default browser styling shows a triangle marker. Hide it with:

```css
summary::-webkit-details-marker { display: none; }
summary { list-style: none; }
```

### Single-open with 10 lines

Close siblings whenever a `<details>` opens, scoped to the `.accordion` container so multiple
groups stay independent:

```html
<script>
    document.querySelectorAll(".accordion").forEach((accordion) => {
        accordion.addEventListener("toggle", (event) => {
            if (!event.target.matches("details[open]")) return;
            accordion.querySelectorAll("details").forEach((other) => {
                if (other !== event.target) other.open = false;
            });
        }, true); // capture: <details> toggle does not bubble
    });
</script>
```

The `toggle` event is native to `<details>`; no Stimulus controller required.

### Animating with `::details-content`

```css
@layer components {
    details::details-content {
        block-size: 0;
        opacity: 0;
        overflow: hidden;
        transition: content-visibility 200ms allow-discrete, block-size 200ms, opacity 200ms;
    }

    details[open]::details-content {
        block-size: auto;
        block-size: calc-size(auto, size);
        opacity: 1;
    }

    @media (prefers-reduced-motion: reduce) {
        details::details-content { transition: none; }
    }
}
```

Browser support note: `::details-content` and `calc-size(auto)` are Chrome 129+/Safari 18.4+/
Firefox 134+ — the page degrades to instant open/close in older browsers without breaking the
underlying behavior.

### When `<details>` is NOT the right answer

- You need to open or close it programmatically from another controller (outlets).
- You need an event payload (`disclosure:change`) to wire analytics or other UI off transitions.
- You need to render the initial state from server data and have it survive Turbo morphs cleanly.
- Your design uses non-button triggers or splits the trigger from the content in a way
  `<summary>` cannot represent.

Reach for the patterns below in those cases.

---

## Pattern 1 — Independent disclosures

The simplest accordion: render N disclosures inside one container. Each item opens and closes
independently. This matches the WAI-ARIA "disclosure" pattern, not the more restrictive
"accordion" widget.

```blade
<div class="divide-y divide-gray-200 rounded border border-gray-200">
    @foreach ($faqs as $faq)
        <div data-controller="disclosure" class="p-4">
            <button
                type="button"
                data-disclosure-target="trigger"
                data-action="disclosure#toggle"
                aria-expanded="false"
                class="flex w-full items-center justify-between text-left font-medium"
            >
                <span>{{ $faq->question }}</span>
                <svg class="h-5 w-5 transition-transform" style="transform: rotate(var(--rot, 0deg))">
                    <path d="m18 15-6-6-6 6"/>
                 </svg>
            </button>
            <div data-disclosure-target="content" hidden class="mt-3 text-gray-700">
                {{ $faq->answer }}
            </div>
        </div>
    @endforeach
</div>
```

**When to use:** FAQ lists, settings groups, documentation sidebars — anywhere users may want
several panels open at once. Recommended default.

### Chevron rotation

Drive the chevron from the trigger's `aria-expanded`:

```css
[data-disclosure-target="trigger"][aria-expanded="true"] svg {
    --rot: 180deg;
}
```

Or with Tailwind arbitrary variants:

```html

<svg class="h-5 w-5 transition-transform in-aria-expanded:rotate-180">...</svg>
```

## Animating with CSS Grid

The base `disclosure` controller toggles the `hidden` attribute — a binary switch, no transition.
For a smooth height animation, swap `disclosure` for the small custom controller below. It uses
the `grid-template-rows: 0fr → 1fr` trick, which animates auto-height content without measuring.

Browser support: Chrome 117+, Safari 17.4+, Firefox 121+ — universal in modern browsers.

### Markup

The content needs three levels: an outer **grid container** the controller animates, an
**overflow-hidden** clipper, and the **content** itself.

```html

<div data-controller="animated-disclosure">
    <button type="button"
            data-animated-disclosure-target="trigger"
            data-action="animated-disclosure#toggle"
            aria-expanded="false">Question</button>
    <div data-animated-disclosure-target="content"
         class="grid grid-rows-[0fr] transition-[grid-template-rows] duration-300 ease-out
                motion-reduce:transition-none"
         hidden>
        <div class="overflow-hidden">
            <p class="pt-3 text-gray-700">Answer body.</p>
        </div>
    </div>
</div>
```

### Controller

```js
// resources/js/controllers/animated_disclosure_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["trigger", "content"];
    static values = { open: { type: Boolean, default: false } };

    connect() {
        this.contentTarget.style.gridTemplateRows = this.openValue ? "1fr" : "0fr";
        this.sync();
    }

    toggle() { this.openValue ? this.close() : this.open(); }

    open() {
        if (this.openValue) return;

        this.openValue = true;
        this.contentTarget.hidden = false;

        // Next frame so the browser registers hidden=false before transitioning.
        requestAnimationFrame(() => {
            this.contentTarget.style.gridTemplateRows = "1fr";
        });

        this.sync();
        this.dispatch("change", { detail: { open: true } });
    }

    close() {
        if (!this.openValue) return;

        this.openValue = false;
        this.contentTarget.style.gridTemplateRows = "0fr";

        if (this.prefersReducedMotion()) {
            this.contentTarget.hidden = true;
        } else {
            this.contentTarget.addEventListener("transitionend", this.hideAfterTransition, { once: true });
        }

        this.sync();
        this.dispatch("change", { detail: { open: false } });
    }

    hideAfterTransition = (event) => {
        if (event.propertyName !== "grid-template-rows") return;
        if (!this.openValue) this.contentTarget.hidden = true;
    };

    sync() {
        if (this.hasTriggerTarget) {
            this.triggerTarget.setAttribute("aria-expanded", this.openValue ? "true" : "false");
        }
    }

    prefersReducedMotion() {
        return window.matchMedia?.("(prefers-reduced-motion: reduce)")?.matches ?? false;
    }
}
```

### Why not extend the base `disclosure` controller?

The base controller toggles `content.hidden` synchronously inside `open()` / `close()` —
that fights any CSS transition. Subclassing would require overriding both methods and
`sync()`, leaving very little of the parent intact. A standalone controller with the same
API is clearer than an inheritance dance.

### Trade-offs

- Adds two wrapper divs to the content markup (grid container + overflow clipper).
- Couples the controller to the grid-template-rows animation strategy.
- Needs `transitionend` cleanup and reduced-motion handling.

Use the base `disclosure` when animation isn't required — the markup stays flat and the
controller stays smaller.

## Pattern 2 — Single-open accordion

Only one panel open at a time. Layer a small "controller of controllers" on top of the
disclosures and let it react to `disclosure:change` events bubbling up.

```html

<div data-controller="single-open-accordion">
    <div data-controller="disclosure" data-action="disclosure:change->single-open-accordion#sync">
        <button data-disclosure-target="trigger" data-action="disclosure#toggle"
                aria-expanded="false">Item 1
        </button>
        <div data-disclosure-target="content" hidden>...</div>
    </div>

    <div data-controller="disclosure" data-action="disclosure:change->single-open-accordion#sync">
        <button data-disclosure-target="trigger" data-action="disclosure#toggle"
                aria-expanded="false">Item 2
        </button>
        <div data-disclosure-target="content" hidden>...</div>
    </div>

    <div data-controller="disclosure" data-action="disclosure:change->single-open-accordion#sync">
        <button data-disclosure-target="trigger" data-action="disclosure#toggle"
                aria-expanded="false">Item 3
        </button>
        <div data-disclosure-target="content" hidden>...</div>
    </div>
</div>
```

```js
// resources/js/controllers/single_open_accordion_controller.js
import {Controller} from "@hotwired/stimulus";

export default class extends Controller {
    static outlets = ["disclosure"];

    sync(event) {
        if (!event.detail.open) return;

        const opened = event.target;
        for (const outlet of this.disclosureOutlets) {
            if (outlet.element !== opened) outlet.close();
        }
    }
}
```

The disclosure children declare themselves as outlets of the accordion automatically when both
identifiers are present (Stimulus matches by `data-controller~="disclosure"`). The accordion only
reacts when an item *opens* — closing any other item without re-triggering the cascade.

**When to use:** mutually exclusive content (e.g., a settings panel where only one section makes
sense to expand at a time), strict design constraints.

**Trade-offs:** the WAI-ARIA accordion widget calls for additional keyboard navigation (`Home`,
`End`, arrows to move between triggers). This recipe handles state but not roving keyboard
navigation — add `tabs`-style key handlers if your accordion truly behaves as a tab group.

## Pattern 3 — Server-rendered open state

When the open item is determined by the server (e.g., the current section in a documentation
tree), render `data-disclosure-open-value="true"` and the matching ARIA on the active one:

```blade
@foreach ($sections as $section)
    <div data-controller="disclosure"
         data-disclosure-open-value="{{ $section->id === $current->id ? 'true' : 'false' }}">
        <button type="button"
                data-disclosure-target="trigger"
                data-action="disclosure#toggle"
                aria-expanded="{{ $section->id === $current->id ? 'true' : 'false' }}">
            {{ $section->title }}
        </button>
        <div data-disclosure-target="content" @if ($section->id !== $current->id) hidden @endif>
            {{ $section->summary }}
        </div>
    </div>
@endforeach
```

The controller still re-syncs on `connect()`, but matching SSR markup to the value avoids any
flash of incorrect state and works with the page cached by Turbo Drive.

## Pattern 4 — Drive open state from a URL

Pair Pattern 3 with `?section=billing` to make accordion state shareable:

```php
public function show(Request $request)
{
    return view('settings.index', [
        'sections' => Section::all(),
        'current'  => Section::firstWhere('slug', $request->query('section', 'general')),
    ]);
}
```

The accordion is now an addressable URL pattern, refresh-safe, and Drive-friendly — open links
land on the right panel.

## See also

- [Disclosure controller](../controllers/disclosure.md) — the underlying primitive.
- [Tabs controller](../controllers/tabs.md) — for mutually exclusive content where users navigate
  between panels rather than expand them inline.
