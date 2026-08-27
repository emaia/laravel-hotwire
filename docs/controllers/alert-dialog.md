# Alert Dialog

Intercepts clicks, opens an alert dialog, and re-fires the original action only after the user confirms. This is the
low-level Stimulus controller used by [`<hw:alert-dialog>`](../components/alert-dialog.md).

**Identifier:** `alert-dialog`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers alert-dialog`.

## Requirements

- No external dependencies.

Escape cancellation and focus trapping are suspended during IME composition.

## Basic Usage

```html
<div
    data-controller="alert-dialog"
    data-alert-dialog-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->alert-dialog#closeForCache"
>
    <div data-action="click->alert-dialog#intercept">
        <button type="button">Continue</button>
    </div>

    <div
        data-alert-dialog-target="modal"
        data-state="closed"
        data-motion="default"
        data-action="click->alert-dialog#clickOutside"
        hidden
        inert
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirmation-title"
    >
        <div data-alert-dialog-target="backdrop"></div>

        <div data-alert-dialog-target="dialog">
            <h2 id="confirmation-title">Are you sure?</h2>

            <button type="button" data-action="alert-dialog#cancel">Cancel</button>
            <button type="button" data-action="alert-dialog#confirm">Confirm</button>
        </div>
    </div>
</div>
```

The controller stores the clicked element, opens the dialog, traps focus, and only calls `element.click()` again after
`alert-dialog#confirm`. Canceling or closing for Turbo cache clears the pending action without re-firing it.

## Targets

| Target     | Description                                                      |
|------------|------------------------------------------------------------------|
| `modal`    | Overlay element shown and hidden by the controller               |
| `backdrop` | Background layer animated separately from the dialog             |
| `dialog`   | Visible dialog panel used for click-outside and focus trap logic |

## Values

| Value                    | Type      | Default | Description                                               |
|--------------------------|-----------|---------|-----------------------------------------------------------|
| `lock-scroll`            | `Boolean` | `true`  | Adds and removes the configured body scroll-lock class    |
| `close-on-click-outside` | `Boolean` | `true`  | Cancels the dialog when the user clicks outside the panel |

## Stimulus Classes

| Class         | Description                                                                |
|---------------|----------------------------------------------------------------------------|
| `lock-scroll` | Applied to `<body>` while the dialog is open when `lock-scroll` is enabled |

## Actions

| Action                       | Description                                                                 |
|------------------------------|-----------------------------------------------------------------------------|
| `alert-dialog#intercept`     | Intercepts a click, stores the original element, and opens the dialog       |
| `alert-dialog#confirm`       | Closes the dialog and re-fires the original click after the close animation |
| `alert-dialog#cancel`        | Cancels the pending action and closes the dialog                            |
| `alert-dialog#clickOutside`  | Cancels when clicking outside the dialog panel                              |
| `alert-dialog#closeForCache` | Clears the pending action and closes synchronously for Turbo cache          |

## Copyable Minimal Markup

```html
<div
    data-controller="alert-dialog"
    data-alert-dialog-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->alert-dialog#closeForCache"
>
    <div data-action="click->alert-dialog#intercept">
        <button type="button">Continue</button>
    </div>

    <div
        data-alert-dialog-target="modal"
        data-state="closed"
        data-motion="default"
        data-action="click->alert-dialog#clickOutside"
        hidden inert
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirmation-title"
    >
        <div data-alert-dialog-target="backdrop"></div>

        <div data-alert-dialog-target="dialog">
            <h2 id="confirmation-title">Are you sure?</h2>

            <button type="button" data-action="alert-dialog#cancel">Cancel</button>
            <button type="button" data-action="alert-dialog#confirm">Confirm</button>
        </div>
    </div>
</div>
```

The modal target starts closed, hidden and inert so the dialog never flashes before Stimulus connects.

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
- `role="alertdialog"`, `aria-modal="true"`, and an accessible name should be applied to the visible overlay container.

## Turbo integration

Cancel the dialog on `turbo:before-cache` to avoid restoring an open modal from Turbo Drive cache:

```html

<div
    data-controller="alert-dialog"
    data-action="turbo:before-cache@window->alert-dialog#closeForCache"
>
    ...
</div>
```

## Use the Blade component when possible

If you want the full markup, default classes, labels, and slots already wired, use
[`<hw:alert-dialog>`](../components/alert-dialog.md). Use the controller directly when you need custom HTML structure or
custom styling.
