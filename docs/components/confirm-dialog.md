# Confirm Dialog

Accessible confirmation dialog that intercepts clicks and requires user confirmation before proceeding. Works with
links, buttons, form submissions, and Turbo actions.

## Basic usage

The default slot **is** the trigger — anything you put inside the component will be wrapped in a
click-intercept zone. The confirm button is styled red by default since most confirmation prompts
guard destructive actions:

```html
<x-hwc::confirm-dialog title="Delete item?" message="This action cannot be undone.">
    <button type="button">Delete</button>
</x-hwc::confirm-dialog>
```

## With Turbo method

```html
<x-hwc::confirm-dialog title="Delete item?" message="This action cannot be undone.">
    <a href="/items/1" data-turbo-method="delete">Delete</a>
</x-hwc::confirm-dialog>
```

## Non-destructive variant

For confirmations that aren't destructive (submitting a report, sending an invite), override
`confirm-class` to a primary color. `cancel-class` is also available for tighter visual pairing:

```html
<form id="report-form" action="/reports" method="POST">
    @csrf
    <!-- form fields -->
</form>

<x-hwc::confirm-dialog
    title="Submit report?"
    message="This will be sent to the team."
    confirm-label="Submit"
    confirm-class="bg-indigo-600 text-white hover:bg-indigo-700"
>
    <button type="submit" form="report-form">Submit</button>
</x-hwc::confirm-dialog>
```

## Rich body content

When `message` isn't enough — lists of consequences, multiple paragraphs, embedded links — use the
`body` slot:

```html
<x-hwc::confirm-dialog title="Archive project?" message="This will hide the project from the dashboard.">
    <button type="button">Archive</button>

    <x-slot:body>
        <ul class="mt-2 list-disc pl-5 text-sm text-gray-600">
            <li>Existing links keep working.</li>
            <li>Members lose write access.</li>
            <li>Restoring takes one click from the archive view.</li>
        </ul>
    </x-slot:body>
</x-hwc::confirm-dialog>
```

The `body` slot renders below `message` and above the action buttons.

## Tweaking behavior

Animation speed, scroll lock, and click-outside behavior are exposed as Blade props — no need to
write `data-*-value` attributes:

```html
<x-hwc::confirm-dialog
    title="Are you sure?"
    :open-duration="500"
    :close-duration="100"
    :lock-scroll="false"
    :close-on-click-outside="false"
>
    <button type="button">Proceed</button>
</x-hwc::confirm-dialog>
```

## Props

| Prop                      | Type     | Default              | Description                                                                                               |
|---------------------------|----------|----------------------|-----------------------------------------------------------------------------------------------------------|
| `id`                      | `string` | `uniqid('confirm-')` | Root element ID                                                                                           |
| `title`                   | `string` | `''`                 | Dialog heading                                                                                            |
| `message`                 | `string` | `''`                 | Body text below the title                                                                                 |
| `confirm-label`           | `string` | `'Confirm'`          | Confirm button label                                                                                      |
| `cancel-label`            | `string` | `'Cancel'`           | Cancel button label                                                                                       |
| `confirm-class`           | `string` | `''`                 | CSS classes for the confirm button. Defaults to `bg-red-600 text-white hover:bg-red-700` when empty       |
| `cancel-class`            | `string` | `''`                 | CSS classes for the cancel button. Defaults to `border border-gray-300 text-gray-700 hover:bg-gray-50` when empty |
| `open-duration`           | `int`    | `200`                | Opening animation duration (ms)                                                                           |
| `close-duration`          | `int`    | `200`                | Closing animation duration (ms)                                                                           |
| `lock-scroll`             | `bool`   | `true`               | Locks body scroll when the dialog is open                                                                 |
| `close-on-click-outside`  | `bool`   | `true`               | Closes when clicking the backdrop                                                                         |

## Slots

| Slot             | Description                                                                       |
|------------------|-----------------------------------------------------------------------------------|
| `slot` (default) | Trigger element whose click is intercepted to open the dialog                     |
| `body`           | Optional rich content rendered below `message` and above the buttons              |

## How it works

The default slot is wrapped in a click-intercept zone. When the user clicks any element inside, the click is
cancelled and the confirmation dialog opens. If the user clicks **Confirm**, the original click is re-fired on the same
element (bypassing the intercept). If the user clicks **Cancel** or presses `Escape`, the dialog closes and nothing
happens.

The trigger element needs no special attributes — just place it as the default slot.

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the dialog
- Focus returns to the trigger element on close
- Closes on `Escape` key
- Closes on backdrop click (configurable via `close-on-click-outside`)
- Body scroll is locked while open (configurable via `lock-scroll`)

## Turbo integration

The dialog cancels automatically on `turbo:before-cache`, preventing ghost dialogs when navigating with Turbo Drive.

## Need more control?

For fully custom markup — different DOM structure, no Tailwind, or wiring custom buttons inside the
dialog — drop down to the [`confirm-dialog` controller](../../controllers/confirm-dialog.md).
