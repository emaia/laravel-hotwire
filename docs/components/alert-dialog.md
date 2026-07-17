# Alert Dialog

Accessible alert dialog that intercepts clicks and requires user confirmation before proceeding. Works with links,
buttons, form submissions, and Turbo actions.

## Basic usage

The default slot **is** the trigger — anything inside the component is wrapped in a click-intercept zone. The action
button uses the `default` variant by default:

```html
<hw:alert-dialog title="Continue?" description="This will proceed.">
    <button type="button">Continue</button>
</hw:alert-dialog>
```

## Destructive action

Use `confirm-variant="destructive"` when the confirmed action is destructive:

```html
<hw:alert-dialog
    title="Delete item?"
    description="This action cannot be undone."
    confirm-label="Delete"
    confirm-variant="destructive"
>
    <button type="button">Delete</button>
</hw:alert-dialog>
```

## With Turbo method

```html
<hw:alert-dialog
    title="Delete item?"
    description="This action cannot be undone."
    confirm-label="Delete"
    confirm-variant="destructive"
>
    <a href="/items/1" data-turbo-method="delete">Delete</a>
</hw:alert-dialog>
```

## Rich body content

When `description` isn't enough — lists of consequences, multiple paragraphs, embedded links — use the `content` slot:

```html
<hw:alert-dialog title="Archive project?" description="This will hide the project from the dashboard.">
    <button type="button">Archive</button>

    <x-slot:content>
        <ul class="mt-2 list-disc pl-5 text-sm text-muted-foreground">
            <li>Existing links keep working.</li>
            <li>Members lose write access.</li>
            <li>Restoring takes one click from the archive view.</li>
        </ul>
    </x-slot:content>
</hw:alert-dialog>
```

The `content` slot renders below `description` and above the action buttons.

## Tweaking behavior

Animation speed, scroll lock, and click-outside behavior are exposed as Blade props — no need to write
`data-*-value` attributes:

```html
<hw:alert-dialog
    title="Are you sure?"
    :open-duration="500"
    :close-duration="100"
    :lock-scroll="false"
    :close-on-click-outside="false"
>
    <button type="button">Proceed</button>
</hw:alert-dialog>
```

## Props

| Prop                     | Type     | Default            | Description                                     |
|--------------------------|----------|--------------------|-------------------------------------------------|
| `id`                     | `string` | `uniqid('alert-')` | Root element ID                                 |
| `title`                  | `string` | `''`               | Dialog heading                                  |
| `description`            | `string` | `''`               | Body text below the title                       |
| `confirm-label`          | `string` | `'Confirm'`        | Action button label                             |
| `cancel-label`           | `string` | `'Cancel'`         | Cancel button label                             |
| `confirm-variant`        | `string` | `'default'`        | Action button variant                           |
| `cancel-variant`         | `string` | `'outline'`        | Cancel button variant                           |
| `confirm-class`          | `string` | `''`               | Extra CSS classes for the action button         |
| `cancel-class`           | `string` | `''`               | Extra CSS classes for the cancel button         |
| `open-duration`          | `int`    | `200`              | Opening animation duration (ms)                 |
| `close-duration`         | `int`    | `200`              | Closing animation duration (ms)                 |
| `lock-scroll`            | `bool`   | `true`             | Locks body scroll when the dialog is open       |
| `close-on-click-outside` | `bool`   | `true`             | Closes when clicking the backdrop               |
| `stimulus`               | `Htmlable\|null` | `null`   | Optional extra Stimulus binding merged into the root element |

Regular `data-controller` / `data-action` attributes and the `stimulus` prop are merged and deduplicated with the
internal `alert-dialog` controller. Component-owned `data-alert-dialog-*` attributes are protected; configure supported
dialog behavior with props instead of overriding those attributes directly.

## Slots

| Slot             | Description                                                          |
|------------------|----------------------------------------------------------------------|
| `slot` (default) | Trigger element whose click is intercepted to open the dialog        |
| `content`        | Optional rich content rendered below `description` and above the buttons |

## How it works

The default slot is wrapped in a click-intercept zone. When the user clicks any element inside, the click is canceled
and the alert dialog opens. If the user clicks **Confirm**, the original click is re-fired on the same element
(bypassing the intercept). If the user clicks **Cancel** or presses `Escape`, the dialog closes and nothing happens.

The trigger element needs no special attributes — place it as the default slot.

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

For fully custom markup — different DOM structure, no Tailwind, or wiring custom buttons inside the dialog — drop down
to the [`alert-dialog` controller](../controllers/alert-dialog.md).
