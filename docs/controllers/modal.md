# Modal

Accessible modal with backdrop, animations, focus trap and dynamic content support via Turbo.

**Identifier:** `modal`  
**Install:** `php artisan hotwire:controllers modal`

## Requirements

- No external dependencies.
- Turbo (optional, for dynamic content via Turbo Frame).

## Targets

| Target | Description |
|--------|-------------|
| `modal` | Modal root element (overlay) |
| `backdrop` | Dark background layer |
| `dialog` | Dialog box (visible content) |
| `dynamicContent` | Container observed for content loaded via Turbo |
| `loadingTemplate` | Template shown while dynamic content loads |

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `open-duration` | `Number` | `300` | Opening animation duration (ms) |
| `close-duration` | `Number` | `300` | Closing animation duration (ms) |
| `lock-scroll` | `Boolean` | `true` | Locks body scroll when open |
| `close-on-escape` | `Boolean` | `true` | Closes on Escape key |
| `close-on-click-outside` | `Boolean` | `true` | Closes when clicking outside the modal |
| `prevent-reopen-delay` | `Number` | `300` | Anti-bounce delay between close and reopen (ms) |

## Stimulus Classes

| Class | Description |
|-------|-------------|
| `hidden` / `visible` | Applied to the `modal` target on open/close |
| `backdrop-hidden` / `backdrop-visible` | Applied to the `backdrop` target |
| `dialog-hidden` / `dialog-visible` | Applied to the `dialog` target |
| `lock-scroll` | Applied to `<body>` when `lock-scroll` is `true` |

## Actions

| Action | Description |
|--------|-------------|
| `modal#open` | Opens the modal |
| `modal#close` | Closes the modal |
| `modal#clickOutside` | Closes when clicking outside (use with `click` event on overlay) |
| `modal#showLoading` | Shows the loading template before a Turbo request |

## Events

| Event | Description |
|-------|-------------|
| `modal:opened` | Fired on the root element after the opening animation completes |
| `modal:closed` | Fired on the root element after the closing animation completes |

## Basic usage

```html
<div
    data-controller="modal"
    data-modal-hidden-class="opacity-0 pointer-events-none"
    data-modal-visible-class="opacity-100"
    data-modal-backdrop-hidden-class="opacity-0"
    data-modal-backdrop-visible-class="opacity-50"
    data-modal-dialog-hidden-class="opacity-0 scale-95"
    data-modal-dialog-visible-class="opacity-100 scale-100"
    data-modal-lock-scroll-class="overflow-hidden"
>
    <!-- Trigger -->
    <button type="button" data-action="modal#open">Open modal</button>

    <div data-modal-target="modal" hidden>
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
            >
                <h2>Title</h2>
                <p>Modal content.</p>

                <button type="button" data-action="modal#close">Close</button>
            </div>
        </div>
    </div>
</div>
```

Stimulus actions are delegated by ancestry — the trigger must live inside the
`[data-controller="modal"]` element so the click reaches the controller. The Blade component
handles this for you via the `trigger` slot. Root attributes like `data-modal-close-on-escape-value`
or `aria-labelledby` belong on that same controller element.

## With dynamic content via Turbo Frame

The `dynamicContent` target is observed via `MutationObserver`. When content is inserted, the modal opens automatically. When the content is removed, it closes.

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
automatically calls `showLoading` — works whether the link is inside the modal element or far away
in a shared layout.

## Loading template

The `loadingTemplate` target defines what fills the dynamic content while the Turbo Frame request
is in flight.

### Lifecycle

1. User clicks `<a data-turbo-frame="<frame id>">` — anywhere on the page.
2. The controller resolves a template and injects it into the `dynamicContent` target.
3. The content observer sees the inserted markup and opens the modal.
4. The frame response arrives → its content replaces the template.

The injection is deferred to the next tick. If `turbo:before-fetch-response` fires first (very fast
responses), the controller skips the injection — the modal opens straight to the final content
without flashing the template. If no template resolves at all, the controller shows no loading
state and waits for the real frame content.

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
