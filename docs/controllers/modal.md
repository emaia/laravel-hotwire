# Modal

Accessible modal with backdrop, animations, focus trap and dynamic content support via Turbo.

**Identifier:** `modal`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers modal`.

## Requirements

- No external dependencies.
- Turbo (optional, for dynamic content via Turbo Frame).

Escape dismissal and focus trapping are suspended during IME composition, so canceling a candidate does not close the
modal or move focus.

## Basic Usage

```html
<div
    data-controller="modal"
    data-modal-lock-scroll-class="overflow-hidden"
    data-modal-initial-focus-value="auto"
    data-action="turbo:before-cache@window->modal#closeForCache"
>
    <button type="button" data-action="modal#open">Open modal</button>

    <div data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
        <div
            data-modal-target="backdrop"
            data-action="click->modal#clickOutside"
        ></div>

        <div data-modal-target="dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
            <h2 id="modal-title">Title</h2>
            <p>Modal content.</p>

            <button type="button" data-action="modal#close">Close</button>
        </div>
    </div>
</div>
```

Stimulus actions are delegated by ancestry, so the trigger must live inside the `[data-controller="modal"]` element. The
controller opens and closes the overlay, traps focus, returns focus to the opener, locks body scroll when configured, and
closes synchronously before Turbo caches the page.

## Targets

| Target            | Description                                     |
|-------------------|-------------------------------------------------|
| `modal`           | Modal root element (overlay)                    |
| `backdrop`        | Dark background layer                           |
| `dialog`          | Dialog box (visible content)                    |
| `dynamicContent`  | Container observed for content loaded via Turbo |
| `loadingTemplate` | Template shown while dynamic content loads      |

## Values

| Value                    | Type      | Default | Description                                     |
|--------------------------|-----------|---------|-------------------------------------------------|
| `lock-scroll`            | `Boolean` | `true`  | Locks body scroll when open                     |
| `close-on-escape`        | `Boolean` | `true`  | Closes on Escape key                            |
| `close-on-click-outside` | `Boolean` | `true`  | Closes when clicking outside the modal          |
| `initial-focus`          | `String`  | `auto`  | `auto`, `dialog`, `first-focusable`, or `none` |

`auto` focuses an eligible `[autofocus]` element and otherwise the semantic dialog surface. `dialog` skips
`[autofocus]`; `first-focusable` selects the first eligible control; `none` leaves focus where it is until Tab enters the
trap. Invalid values behave as `auto`. Keep the dialog surface programmatically focusable with `tabindex="-1"`.
If `autofocus` is also mounted inside the overlay, whichever controller focuses last wins: `initial-focus` runs when
the modal opens, while `autofocus` runs on connect and affected `turbo:frame-load` events.

## Stimulus Classes

| Class                                  | Description                                      |
|----------------------------------------|--------------------------------------------------|
| `lock-scroll`                          | Applied to `<body>` when `lock-scroll` is `true` |

## Actions

| Action                | Description                                                      |
|-----------------------|------------------------------------------------------------------|
| `modal#open`          | Opens the modal                                                  |
| `modal#close`         | Closes the modal                                                 |
| `modal#clickOutside`  | Closes when clicking outside (use with `click` event on overlay) |
| `modal#closeForCache` | Closes immediately for `turbo:before-cache`                      |

## Events

| Event          | Description                                                     |
|----------------|-----------------------------------------------------------------|
| `modal:opened` | Fired on the root element after the opening animation completes |
| `modal:closed` | Fired on the root element after the closing animation completes |

## Copyable Styled Example

```html
<div
    data-controller="modal"
    data-modal-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->modal#closeForCache"
>
    <!-- Trigger -->
    <button type="button" data-action="modal#open">Open modal</button>

    <div data-modal-target="modal" data-state="closed" data-motion="default" hidden inert>
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black transition-opacity"
            data-modal-target="backdrop"
            data-action="click->modal#clickOutside"
        ></div>

        <!-- Dialog -->
        <div class="fixed inset-0 flex items-center justify-center">
            <div
                class="bg-white rounded-lg shadow-xl p-6 transition-all"
                data-modal-target="dialog"
                role="dialog"
                aria-modal="true"
                aria-labelledby="styled-modal-title"
            >
                <h2 id="styled-modal-title">Title</h2>
                <p>Modal content.</p>

                <button type="button" data-action="modal#close">Close</button>
            </div>
        </div>
    </div>
</div>
```

Style closed and open visuals from the overlay state, scoped to direct children so nested modals keep independent motion:

```css
[data-modal-target="modal"][data-state="closed"] > [data-modal-target="dialog"] { opacity: 0; scale: .95; }
[data-modal-target="modal"][data-state="open"] > [data-modal-target="dialog"] { opacity: 1; scale: 1; }
```

Presence waits for actual finite CSS motion on the backdrop and dialog. Never set `display: none` in the closed-state
rule; Presence owns `hidden` and keeps exit content rendered but inert until motion settles.

The Blade component handles trigger ancestry for you via the `trigger` slot. Controller values such as
`data-modal-close-on-escape-value` belong on the controller root. Put `aria-label` or `aria-labelledby` directly on the
element carrying `role="dialog"` when writing controller markup manually.

## With dynamic content via Turbo Frame

The `dynamicContent` target is observed via `MutationObserver`. When content is inserted, the modal opens automatically. Return an empty `update` or `replace` stream for the modal root or frame id, or a `refresh` stream, to close it after a successful action.

```html
<div data-controller="modal" ...>
    <div class="fixed inset-0 bg-black/50" data-modal-target="backdrop"></div>

    <div class="fixed inset-0 flex items-center justify-center">
        <div data-modal-target="dialog" class="bg-white rounded-lg shadow-xl">
            <turbo-frame
                id="modal-content"
                data-modal-target="dynamicContent"
            ></turbo-frame>
        </div>
    </div>
</div>

<!-- Link that loads content into the frame and opens the modal automatically -->
<a href="/items/1/edit" data-turbo-frame="modal-content">
    Edit
</a>
```

The controller listens globally for clicks on `a[data-turbo-frame="<dynamicContent id>"]` and
injects the resolved loading template — works whether the link is inside the modal element or far
away in a shared layout.

For Blade modal triggers, an anchor inside `<hw:modal frame="modal-content">` inherits that frame target. Its normal
Turbo navigation continues; the overlay opens when the loading template or response reaches the frame. A local anchor
trigger without `data-turbo-frame` cancels its `href` navigation and opens the existing modal content immediately.
Disabled anchor triggers have no `href` or frame metadata.

## Loading template

The `loadingTemplate` target defines what fills the dynamic content while the Turbo Frame request
is in flight.

### Lifecycle

1. User clicks `<a data-turbo-frame="<frame id>">` — anywhere on the page.
2. Turbo dispatches `turbo:before-fetch-request` on the matching frame just before the network call.
3. The controller catches that event, resolves a template and injects it into the `dynamicContent`
   target synchronously.
4. The content observer sees the inserted markup and opens the modal.
5. The frame response arrives → its content replaces the template.

Synchronous injection on `turbo:before-fetch-request` means there is no timing race with the
response: by the time the request is in flight, the loading state is already on screen. Cached or
preview responses that Turbo serves without a fetch never reach this event, so the modal opens
straight to the final content without flashing the template. If no template resolves at all, the
controller shows no loading state and waits for the real frame content.

### Default template (target)

Use a `<template data-modal-target="loadingTemplate">` for the default loading state shared by every
trigger:

```html
<div data-controller="modal" ...>
    <turbo-frame id="modal-content" data-modal-target="dynamicContent"></turbo-frame>

    <template data-modal-target="loadingTemplate">
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </template>
</div>
```

### Per-link template (`data-loading-template`)

A trigger can point to its own template via `data-loading-template="<selector>"`. Resolution order
is: per-link template → modal's `loadingTemplate` target → no loading template:

```html
<a href="/posts/1/edit"
   data-turbo-frame="modal-content"
   data-loading-template="#form-skeleton">
    Edit
</a>

<template id="form-skeleton">
    <div class="space-y-3 p-6">
        <div class="h-6 w-1/3 animate-pulse rounded bg-gray-200"></div>
        <div class="h-32 w-full animate-pulse rounded bg-gray-200"></div>
    </div>
</template>
```

The selector is passed verbatim to `document.querySelector` — any valid CSS selector works.

## Accessibility

- Initial focus follows `initial-focus` once per opening; reconnects and nested-overlay resume do not repeat it.
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the modal.
- Focus returns to the element that opened the modal on close.
- Closes on Escape (configurable via `close-on-escape`).

## Ignore outside click

Elements outside the modal that should not close it can use `data-modal-ignore`:

```html
<div data-modal-ignore>
    This element will not close the modal when clicked.
</div>
```

## Listening to events

```javascript
document.querySelector('[data-controller="modal"]').addEventListener("modal:opened", (event) => {
    console.log("Modal opened", event.detail.controller);
});
```
