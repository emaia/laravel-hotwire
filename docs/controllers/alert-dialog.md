# Alert Dialog

Intercepts clicks, opens an alert dialog, and resumes the captured action only after the user confirms. This is the
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
    <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
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

The controller captures a description of the action, opens the dialog, and resumes it after `alert-dialog#confirm` by
clicking the trigger again. A trigger with a stable `id` is found afresh, so a Turbo morph may replace it while the
dialog is open. For a trigger without an `id`, the controller temporarily retains that exact node and only resumes it
when the same node remains inside the Alert Dialog.

The capture-phase `interceptCapture` action swallows the original click before it reaches the trigger. Listeners on the
resolved replay node and bubbling ancestors reached by it run once — on the confirmed click, not on the one that opened
the dialog. Cancelling, or closing for Turbo cache, clears the pending action and nothing is dispatched downstream.

The trigger is only replayed when its tag, behavioural attributes (`href`, `formaction`, `data-*`, `onclick`, `name`,
`value`, …), resolved destination, native command/popover target, owner form controls, and effective Turbo/Frame context
still match what was captured. If any of them changed, or an ID became ambiguous, confirming closes the dialog without
acting. Give a trigger a stable `id` when a morph may replace it; an id-less replacement deliberately fails closed rather
than relying on DOM position.

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

| Class                                  | Description                                                                |
|----------------------------------------|----------------------------------------------------------------------------|
| `lock-scroll`                          | Applied to `<body>` while the dialog is open when `lock-scroll` is enabled |

## Actions

| Action                      | Description                                                                 |
|-----------------------------|-----------------------------------------------------------------------------|
| `alert-dialog#interceptCapture` | Captures and prevents the action before trigger listeners; wire with `:capture` |
| `alert-dialog#intercept`    | Intercepts a link or submit click, captures its action, and opens the dialog |
| `alert-dialog#confirm`      | Closes the dialog and resumes the action after the close animation          |
| `alert-dialog#cancel`       | Cancels the pending action and closes the dialog                            |
| `alert-dialog#clickOutside` | Cancels when clicking outside the dialog panel                              |
| `alert-dialog#closeForCache` | Clears the pending action and closes synchronously for Turbo cache          |

## Copyable Minimal Markup

```html
<div
    data-controller="alert-dialog"
    data-alert-dialog-lock-scroll-class="overflow-hidden"
    data-action="turbo:before-cache@window->alert-dialog#closeForCache"
>
    <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
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

The controller preserves the link destination and Turbo attributes, so `data-turbo-method` continues to work without
custom integration even if the trigger is replaced while the dialog is open:

```html
<div data-controller="alert-dialog" ...>
    <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
        <a id="delete-post" href="/posts/1" data-turbo-method="delete">Delete post</a>
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
    <div data-action="click->alert-dialog#interceptCapture:capture click->alert-dialog#intercept">
        <button type="submit" form="report-form">Submit report</button>
    </div>

    <!-- modal markup -->
</div>
```

Submitter attributes such as `name`, `value`, `formaction`, `formmethod`, and `data-turbo-frame` are preserved. A form
with a stable `id` is resolved again at confirmation time, so an equivalent external form replaced during a Turbo morph
still submits. Changing the owner form, resolved action URL, non-file control values, file selection metadata, or Turbo
context while the dialog is open makes confirmation fail closed.

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
[`<hw:alert-dialog>`](../components/alert-dialog.md). Use the controller directly when you need custom HTML
structure or custom styling.
