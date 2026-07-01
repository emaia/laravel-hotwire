# Alert Dialog

Intercepts clicks, opens an alert dialog, and re-fires the original action only after the user confirms. This is the
low-level Stimulus controller used by [`<x-hwc::alert-dialog>`](../components/alert-dialog.md).

**Identifier:** `alert-dialog`  
**Install:** `php artisan hotwire:controllers alert-dialog`

## Requirements

- No external dependencies.

## Targets

| Target     | Description                                                      |
|------------|------------------------------------------------------------------|
| `modal`    | Overlay element shown and hidden by the controller               |
| `backdrop` | Background layer animated separately from the dialog             |
| `dialog`   | Visible dialog panel used for click-outside and focus trap logic |

## Stimulus Values

| Value                    | Type      | Default | Description                                               |
|--------------------------|-----------|---------|-----------------------------------------------------------|
| `open-duration`          | `Number`  | `200`   | Opening animation duration in milliseconds                |
| `close-duration`         | `Number`  | `200`   | Closing animation duration in milliseconds                |
| `lock-scroll`            | `Boolean` | `true`  | Adds and removes the configured body scroll-lock class    |
| `close-on-click-outside` | `Boolean` | `true`  | Cancels the dialog when the user clicks outside the panel |

## Stimulus Classes

| Class                                  | Description                                                                |
|----------------------------------------|----------------------------------------------------------------------------|
| `hidden` / `visible`                   | Applied to the `modal` target during close/open                            |
| `backdrop-hidden` / `backdrop-visible` | Applied to the `backdrop` target                                           |
| `dialog-hidden` / `dialog-visible`     | Applied to the `dialog` target                                             |
| `lock-scroll`                          | Applied to `<body>` while the dialog is open when `lock-scroll` is enabled |

## Actions

| Action                      | Description                                                                 |
|-----------------------------|-----------------------------------------------------------------------------|
| `alert-dialog#intercept`    | Intercepts a click, stores the original element, and opens the dialog       |
| `alert-dialog#confirm`      | Closes the dialog and re-fires the original click after the close animation |
| `alert-dialog#cancel`       | Cancels the pending action and closes the dialog                            |
| `alert-dialog#clickOutside` | Cancels when clicking outside the dialog panel                              |

## Basic usage

```html
<div
    data-controller="alert-dialog"
    data-alert-dialog-hidden-class="opacity-0 pointer-events-none"
    data-alert-dialog-visible-class="opacity-100 pointer-events-auto"
    data-alert-dialog-backdrop-hidden-class="opacity-0"
    data-alert-dialog-backdrop-visible-class="opacity-100"
    data-alert-dialog-dialog-hidden-class="scale-90 opacity-0"
    data-alert-dialog-dialog-visible-class="scale-100 opacity-100"
    data-alert-dialog-lock-scroll-class="overflow-hidden"
>
    <div data-action="click->alert-dialog#intercept">
        <button type="button">Continue</button>
    </div>

    <div
        data-alert-dialog-target="modal"
        data-action="click->alert-dialog#clickOutside"
        hidden
    >
        <div data-alert-dialog-target="backdrop"></div>

        <div data-alert-dialog-target="dialog">
            <p>Are you sure?</p>

            <button type="button" data-action="alert-dialog#cancel">Cancel</button>
            <button type="button" data-action="alert-dialog#confirm">Confirm</button>
        </div>
    </div>
</div>
```

The controller stores the clicked element, opens the dialog, and only calls `element.click()` again after
`alert-dialog#confirm`.

## With a Turbo method link

Because the controller re-fires the original click, it works with Turbo links and `data-turbo-method` without custom
integration:

```html
<div data-controller="alert-dialog" ...>
    <div data-action="click->alert-dialog#intercept">
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

<div data-controller="alert-dialog" ...>
    <div data-action="click->alert-dialog#intercept">
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
    data-controller="alert-dialog"
    data-action="turbo:before-cache@window->alert-dialog#cancel"
>
    ...
</div>
```

## Use the Blade component when possible

If you want the full markup, default classes, labels, and slots already wired, use
[`<x-hwc::alert-dialog>`](../components/alert-dialog.md). Use the controller directly when you need custom HTML
structure or custom styling.
