# Dialog

Accessible modal dialog with backdrop, animations, focus trap and dynamic content support via Turbo.

**Identifier:** `dialog`

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
| `close-on-click-outside` | `Boolean` | `true` | Closes when clicking outside the dialog |
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
| `dialog#open` | Opens the modal |
| `dialog#close` | Closes the modal |
| `dialog#clickOutside` | Closes when clicking outside (use with `click` event on overlay) |
| `dialog#showLoading` | Shows the loading template before a Turbo request |

## Events

| Event | Description |
|-------|-------------|
| `modal:opened` | Fired on the root element after the opening animation completes |
| `modal:closed` | Fired on the root element after the closing animation completes |

## Basic usage

```html
<div
    data-controller="dialog"
    data-dialog-hidden-class="opacity-0 pointer-events-none"
    data-dialog-visible-class="opacity-100"
    data-dialog-backdrop-hidden-class="opacity-0"
    data-dialog-backdrop-visible-class="opacity-50"
    data-dialog-dialog-hidden-class="opacity-0 scale-95"
    data-dialog-dialog-visible-class="opacity-100 scale-100"
    data-dialog-lock-scroll-class="overflow-hidden"
    data-dialog-target="modal"
    hidden
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 bg-black transition-opacity"
        data-dialog-target="backdrop"
        data-action="click->dialog#clickOutside"
    ></div>

    <!-- Dialog -->
    <div class="fixed inset-0 flex items-center justify-center">
        <div
            class="bg-white rounded-lg shadow-xl p-6 transition-all"
            data-dialog-target="dialog"
        >
            <h2>Title</h2>
            <p>Modal content.</p>

            <button type="button" data-action="dialog#close">Close</button>
        </div>
    </div>
</div>

<!-- Trigger -->
<button type="button" data-action="dialog#open">Open modal</button>
```

## With dynamic content via Turbo Frame

The `dynamicContent` target is observed via `MutationObserver`. When content is inserted, the modal opens automatically. When the content is removed, it closes.

```html
<div data-controller="dialog" ...>
    <div class="fixed inset-0 bg-black/50" data-dialog-target="backdrop"></div>

    <div class="fixed inset-0 flex items-center justify-center">
        <div data-dialog-target="dialog" class="bg-white rounded-lg shadow-xl">
            <turbo-frame
                id="modal-content"
                data-dialog-target="dynamicContent"
            ></turbo-frame>
        </div>
    </div>
</div>

<!-- Link that loads content into the frame and opens the modal automatically -->
<a
    href="/items/1/edit"
    data-turbo-frame="modal-content"
    data-action="dialog#showLoading"
>
    Edit
</a>
```

## With loading template

Shown while the Turbo Frame awaits the server response:

```html
<div data-controller="dialog" ...>
    ...
    <template data-dialog-target="loadingTemplate">
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </template>
</div>
```

## Accessibility

- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the modal.
- Focus returns to the element that opened the modal on close.
- Closes on Escape (configurable via `close-on-escape`).

## Ignore outside click

Elements outside the dialog that should not close the modal can use `data-modal-ignore`:

```html
<div data-modal-ignore>
    This element will not close the modal when clicked.
</div>
```

## Listening to events

```javascript
document.querySelector('[data-controller="dialog"]').addEventListener("modal:opened", (event) => {
    console.log("Modal opened", event.detail.controller);
});
```
