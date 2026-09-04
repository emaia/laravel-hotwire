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
    data-alert-dialog-initial-focus-value="auto"
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
        tabindex="-1"
    >
        <div data-alert-dialog-target="backdrop"></div>

        <div data-alert-dialog-target="dialog">
            <h2 id="confirmation-title">Are you sure?</h2>

            <button type="button" data-alert-dialog-target="cancel" data-action="alert-dialog#cancel">Cancel</button>
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
acting and dispatches `alert-dialog:dropped`. Give a trigger a stable `id` when a morph may replace it; an id-less
replacement deliberately fails closed rather than relying on DOM position.

## Shared Mode

Set `data-alert-dialog-shared-value="true"` and place the capture and bubble actions on an element wrapping the collection
inside the controller root to reuse one dialog. Only descendants marked with `data-alert-dialog-trigger` are
intercepted. The nearest shared Alert Dialog ancestor owns a marker, and the first pending action wins until it is
confirmed or cancelled.

Shared triggers may override the rendered plain text and button variants with:

- `data-alert-dialog-title`
- `data-alert-dialog-description`
- `data-alert-dialog-confirm-label`
- `data-alert-dialog-cancel-label`
- `data-alert-dialog-confirm-variant`
- `data-alert-dialog-cancel-variant`

Text is replaced only for attributes present on the active trigger. Without an override, authored child markup inside
the target is preserved and restored after later overridden actions.

Add the `title`, `description`, `confirm`, and `cancel` targets to the corresponding shared elements. An empty description
hides its target and removes `aria-describedby` until the host defaults are restored. The Blade
`<hw:alert-dialog.host>` and `<hw:alert-dialog.trigger>` components wire this mode automatically.

If both the host title and a trigger title are empty, shared mode uses `Confirm action` as the visible accessible
fallback. An authored non-empty `aria-label` on the modal takes precedence instead; the empty title stays hidden and
`aria-labelledby` is removed while that action is pending.

## Targets

| Target        | Description                                                            |
|---------------|------------------------------------------------------------------------|
| `modal`       | Semantic overlay shown/hidden by the controller and updated for ARIA    |
| `backdrop`    | Background layer animated separately from the dialog                   |
| `dialog`      | Visible dialog panel used for click-outside and focus trap logic         |
| `title`       | Shared-mode heading whose text can vary by trigger                      |
| `description` | Shared-mode description whose text can vary by trigger                  |
| `cancel`      | Cancel button used by `auto` focus and varied by shared-mode triggers   |
| `confirm`     | Shared-mode confirm button whose label and variant can vary             |

## Values

| Value                    | Type      | Default | Description                                               |
|--------------------------|-----------|---------|-----------------------------------------------------------|
| `lock-scroll`            | `Boolean` | `true`  | Adds and removes the configured body scroll-lock class    |
| `close-on-click-outside` | `Boolean` | `true`  | Cancels the dialog when the user clicks outside the panel |
| `shared`                 | `Boolean` | `false` | Intercepts only marked descendants and applies overrides  |
| `initial-focus`          | `String`  | `auto`  | `auto`, `dialog`, `first-focusable`, or `none`            |

`auto` focuses an eligible `[autofocus]` element and otherwise the `cancel` target. `dialog` focuses the semantic
`alertdialog` surface; `first-focusable` selects the first eligible control; `none` leaves focus where it is until Tab
enters the trap. Invalid values behave as `auto`. Keep the modal target programmatically focusable with `tabindex="-1"`.
Initial focus runs once per opening and is not repeated by reconnects or nested-overlay resume.
If `autofocus` is also mounted inside the overlay, whichever controller focuses last wins: `initial-focus` runs when
the dialog opens, while `autofocus` runs on connect and affected `turbo:frame-load` events.

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

## Events

| Event                    | Detail                | Description                                                   |
|--------------------------|-----------------------|---------------------------------------------------------------|
| `alert-dialog:dropped`   | `{ kind, triggerId }` | Confirmation closed, but the guarded action was not replayed |

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

            <button type="button" data-alert-dialog-target="cancel" data-action="alert-dialog#cancel">Cancel</button>
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

- Initial focus follows `initial-focus` once per opening.
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
