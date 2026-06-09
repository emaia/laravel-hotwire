# Disclosure

Show/hide collapsible inline content with proper ARIA. Toggles the `hidden` attribute on the
content target and keeps `aria-expanded` in sync on an optional trigger target.

The base primitive behind FAQ items, "read more" sections, collapsible panels, and the accordion
recipe.

**Identifier:** `disclosure`  
**Install:** `php artisan hotwire:controllers disclosure`

## Requirements

- No external dependencies.

## Consider native `<details>` first

For static FAQs, settings groups, and other purely visual disclosures, native `<details>` /
`<summary>` gives you the disclosure widget, ARIA, and keyboard handling for free — no Stimulus
required. See the [accordion recipe](../recipes/accordion.md) for the native pattern.

Reach for this controller when:

- Open/close needs to be triggered from another controller (via Stimulus outlets).
- You need a `disclosure:change` event to drive analytics or other UI off transitions.
- Initial open state is rendered from server data and must survive Turbo morphs cleanly.
- Your design uses non-button triggers or splits the trigger from the content in a way
  `<summary>` cannot represent.

## Why not `dropdown`?

`dropdown` is a menu popover — outside-click and Escape dismiss it, and its ARIA role expects a
menu-style affordance. `disclosure` is **inline collapsible content** — it stays where it is until
the trigger is clicked again, no global event listeners, no positioning. Different ARIA pattern,
different behavior. Use the right tool for the affordance.

## Targets

| Target    | Description                                                                          |
|-----------|--------------------------------------------------------------------------------------|
| `content` | The collapsible panel. Required — without it the controller is a safe no-op.         |
| `trigger` | The toggle button. Optional. When present, receives `aria-expanded="true\|false"`.   |

## Values

| Value  | Type    | Default | Description                                                                            |
|--------|---------|---------|----------------------------------------------------------------------------------------|
| `open` | Boolean | `false` | Current open state. Two-way: the controller writes it back as you toggle.              |

## Actions

| Action   | Description                                              |
|----------|----------------------------------------------------------|
| `toggle` | Flip between open and closed.                            |
| `open`   | Force open. Idempotent — does not re-dispatch if open.   |
| `close`  | Force closed. Idempotent — does not re-dispatch if closed. |

## Events

| Event               | Detail            | Description                                                       |
|---------------------|-------------------|-------------------------------------------------------------------|
| `disclosure:change` | `{ open: bool }`  | Fires on every transition. Does not fire on the initial connect.  |

## Basic usage

```html

<div data-controller="disclosure">
    <button
        type="button"
        data-disclosure-target="trigger"
        data-action="disclosure#toggle"
        aria-expanded="false"
    >Read more</button>
    <div data-disclosure-target="content" hidden>
        <p>The hidden details show up here.</p>
    </div>
</div>
```

`aria-expanded` flips on every toggle. `aria-controls` is not managed by the controller — add it
manually with a matching `id` on the content if you want the full ARIA disclosure pattern.

## Initially open

```html

<div data-controller="disclosure" data-disclosure-open-value="true">
    <button
        type="button"
        data-disclosure-target="trigger"
        data-action="disclosure#toggle"
        aria-expanded="true"
    >Hide details</button>
    <div data-disclosure-target="content">
        ...
    </div>
</div>
```

The initial markup should reflect the value: include `aria-expanded="true"` on the trigger and
omit `hidden` from the content. The controller still re-syncs on `connect()`, so any mismatch is
corrected — keeping the SSR markup honest avoids a flash of incorrect state.

## Programmatic control

Call `open()`, `close()` or `toggle()` from outside — for example, from another controller via an
[outlet](https://stimulus.hotwired.dev/reference/outlets):

```js
// some_controller.js
static outlets = ["disclosure"];

revealHelp() {
    this.disclosureOutlet.open();
}
```

Prefer the methods over writing `this.disclosureOutlet.openValue = true`. The methods sync the DOM
and dispatch synchronously; raw value assignment goes through Stimulus's MutationObserver path
and updates asynchronously.

## Reacting to state changes

Listen for `disclosure:change` to update other UI in response — chevron rotation, analytics, a
parent's open-state indicator:

```html

<div data-controller="disclosure faq-icon"
     data-action="disclosure:change->faq-icon#flip">
    <button type="button" data-disclosure-target="trigger" data-action="disclosure#toggle">
        Question
        <svg data-faq-icon-target="chevron" class="transition-transform">...</svg>
    </button>
    <div data-disclosure-target="content" hidden>...</div>
</div>
```

```js
// resources/js/controllers/faq_icon_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["chevron"];

    flip(event) {
        this.chevronTarget.classList.toggle("rotate-180", event.detail.open);
    }
}
```

## Limitations

- **No transitions out of the box.** Toggling the `hidden` attribute is binary — there is no fade
  or height animation. Drive transitions externally (CSS on the trigger's `aria-expanded`, or a
  companion controller listening to `disclosure:change`).
- **No outside-click or Escape dismissal.** That is the affordance of `dropdown` / `modal`. Disclosure
  is inline content that stays put.
- **No `aria-controls` auto-wiring.** Set it manually if you want the full ARIA pattern.
- **One trigger, one content.** Multiple triggers controlling the same panel — or one trigger
  controlling many panels — are out of scope. Compose with your own controller if needed.

## Composing into an accordion

See the [accordion recipe](../recipes/accordion.md) for the pattern that combines several
disclosures into a single-open or multi-open accordion.
