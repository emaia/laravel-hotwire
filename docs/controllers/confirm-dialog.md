# Confirm Dialog

Intercepts clicks, opens a confirmation modal, and re-fires the original action only after the user confirms. This is
the low-level Stimulus controller used by [`<x-hwc::confirm-dialog>`](../components/confirm-dialog.md).

**Identifier:** `confirm-dialog`  
**Install:** `php artisan hotwire:controllers confirm-dialog`

## Requirements

- No external dependencies.

## Targets

| Target | Description |
|--------|-------------|
| `modal` | Overlay element shown and hidden by the controller |
| `backdrop` | Background layer animated separately from the dialog |
| `dialog` | Visible dialog panel used for click-outside and focus trap logic |

## Stimulus Values

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `open-duration` | `Number` | `200` | Opening animation duration in milliseconds |
| `close-duration` | `Number` | `200` | Closing animation duration in milliseconds |
| `lock-scroll` | `Boolean` | `true` | Adds and removes the configured body scroll-lock class |
| `close-on-click-outside` | `Boolean` | `true` | Cancels the dialog when the user clicks outside the panel |

## Stimulus Classes

| Class | Description |
|-------|-------------|
| `hidden` / `visible` | Applied to the `modal` target during close/open |
| `backdrop-hidden` / `backdrop-visible` | Applied to the `backdrop` target |
| `dialog-hidden` / `dialog-visible` | Applied to the `dialog` target |
| `lock-scroll` | Applied to `<body>` while the dialog is open when `lock-scroll` is enabled |

## Actions

| Action | Description |
|--------|-------------|
| `confirm-dialog#intercept` | Intercepts a click, stores the original element, and opens the dialog |
| `confirm-dialog#confirm` | Closes the dialog and re-fires the original click after the close animation |
| `confirm-dialog#cancel` | Cancels the pending action and closes the dialog |
| `confirm-dialog#clickOutside` | Cancels when clicking outside the dialog panel |

## Basic usage

```html
<div
    data-controller="confirm-dialog"
    data-confirm-dialog-hidden-class="opacity-0 pointer-events-none"
    data-confirm-dialog-visible-class="opacity-100 pointer-events-auto"
    data-confirm-dialog-backdrop-hidden-class="opacity-0"
    data-confirm-dialog-backdrop-visible-class="opacity-100"
    data-confirm-dialog-dialog-hidden-class="scale-90 opacity-0"
    data-confirm-dialog-dialog-visible-class="scale-100 opacity-100"
    data-confirm-dialog-lock-scroll-class="overflow-hidden"
>
    <div data-action="click->confirm-dialog#intercept">
        <button type="button">Delete</button>
    </div>

    <div
        data-confirm-dialog-target="modal"
        data-action="click->confirm-dialog#clickOutside"
        hidden
    >
        <div data-confirm-dialog-target="backdrop"></div>

        <div data-confirm-dialog-target="dialog">
            <p>Are you sure?</p>

            <button type="button" data-action="confirm-dialog#cancel">Cancel</button>
            <button type="button" data-action="confirm-dialog#confirm">Confirm</button>
        </div>
    </div>
</div>
```

The controller stores the clicked element, opens the dialog, and only calls `element.click()` again after
`confirm-dialog#confirm`.

## With a Turbo method link

Because the controller re-fires the original click, it works with Turbo links and `data-turbo-method` without custom
integration:

```html
<div data-controller="confirm-dialog" ...>
    <div data-action="click->confirm-dialog#intercept">
        <a href="/posts/1" data-turbo-method="delete">Delete post</a>
    </div>

    <!-- modal markup -->
</div>
```

## With a form submit button

```html
<form id="report-form" action="/reports" method="POST">
    <!-- fields -->
</form>

<div data-controller="confirm-dialog" ...>
    <div data-action="click->confirm-dialog#intercept">
        <button type="submit" form="report-form">Submit report</button>
    </div>

    <!-- modal markup -->
</div>
```

## Accessibility

- Focus is trapped within the dialog while it is open.
- Focus returns to the intercepted trigger element when the dialog closes.
- Pressing `Escape` cancels the dialog.
- `role="dialog"` and `aria-modal="true"` should be applied to the visible overlay container.

## Turbo integration

Cancel the dialog on `turbo:before-cache` to avoid restoring an open modal from Turbo Drive cache:

```html
<div
    data-controller="confirm-dialog"
    data-action="turbo:before-cache@window->confirm-dialog#cancel"
>
    ...
</div>
```

## Use the Blade component when possible

If you want the full markup, default classes, labels, and slots already wired, use
[`<x-hwc::confirm-dialog>`](../components/confirm-dialog.md). Use the controller directly when you need custom
HTML structure or custom styling.
