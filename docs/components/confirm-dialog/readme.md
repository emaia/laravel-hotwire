# Confirm Dialog

Accessible confirmation dialog that intercepts clicks and requires user confirmation before proceeding. Works with links, buttons, form submissions, and Turbo actions.

## Basic usage

```html
<x-hwc-confirm title="Delete item?" message="This action cannot be undone.">
    <x-slot:trigger>
        <button type="button">Delete</button>
    </x-slot:trigger>
</x-hwc-confirm>
```

## With Turbo method

```html
<x-hwc-confirm title="Delete item?" message="This action cannot be undone.">
    <x-slot:trigger>
        <a href="/items/1" data-turbo-method="delete">Delete</a>
    </x-slot:trigger>
</x-hwc-confirm>
```

## With form submit

```html
<form id="report-form" action="/reports" method="POST">
    @csrf
    <!-- form fields -->
</form>

<x-hwc-confirm title="Submit report?" message="This will be sent to the team.">
    <x-slot:trigger>
        <button type="submit" form="report-form">Submit</button>
    </x-slot:trigger>
</x-hwc-confirm>
```

## Danger variant

Use `confirm-class` to style the confirm button for destructive actions:

```html
<x-hwc-confirm
    title="Delete account?"
    message="All your data will be permanently removed."
    confirm-label="Yes, delete"
    confirm-class="bg-red-600 text-white hover:bg-red-700"
>
    <x-slot:trigger>
        <button type="button">Delete account</button>
    </x-slot:trigger>
</x-hwc-confirm>
```

## With custom content

Use the default slot for additional content inside the dialog card:

```html
<x-hwc-confirm title="Archive project?">
    <x-slot:trigger>
        <button type="button">Archive</button>
    </x-slot:trigger>

    <p class="mt-2 text-sm text-gray-600">
        The project will be moved to the archive and hidden from the dashboard.
    </p>
</x-hwc-confirm>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `id` | `string` | `uniqid('confirm-')` | Root element ID |
| `title` | `string` | `''` | Dialog heading |
| `message` | `string` | `''` | Body text below the title |
| `confirm-label` | `string` | `'Confirm'` | Confirm button label |
| `cancel-label` | `string` | `'Cancel'` | Cancel button label |
| `confirm-class` | `string` | `''` | CSS classes for the confirm button. Defaults to `bg-indigo-600 text-white hover:bg-indigo-700` when empty |

## Slots

| Slot | Description |
|------|-------------|
| `trigger` | Element whose click is intercepted to open the dialog |
| `slot` (default) | Additional content rendered inside the dialog card, between the message and the buttons |

## How it works

The controller wraps the `trigger` slot in a click-intercept zone. When the user clicks anything inside, the click is cancelled and the confirmation dialog opens. If the user clicks **Confirm**, the original click is re-fired on the same element (bypassing the intercept). If the user clicks **Cancel** or presses `Escape`, the dialog closes and nothing happens.

The trigger element needs no special attributes — just place it in the `trigger` slot.

## Stimulus Values

Configurable via `data-dialog--confirm-*-value` on the root element:

| Value | Type | Default | Description |
|-------|------|---------|-------------|
| `open-duration` | `Number` | `200` | Opening animation duration (ms) |
| `close-duration` | `Number` | `200` | Closing animation duration (ms) |
| `lock-scroll` | `Boolean` | `true` | Locks body scroll when the dialog is open |
| `close-on-click-outside` | `Boolean` | `true` | Closes when clicking the backdrop |

Example — disable scroll lock and click-outside:

```html
<x-hwc-confirm
    title="Are you sure?"
    data-dialog--confirm-lock-scroll-value="false"
    data-dialog--confirm-close-on-click-outside-value="false"
>
    ...
</x-hwc-confirm>
```

## Actions

| Action | Description |
|--------|-------------|
| `dialog--confirm#intercept` | Intercepts a click and opens the dialog |
| `dialog--confirm#confirm` | Confirms and re-fires the intercepted action |
| `dialog--confirm#cancel` | Cancels and closes the dialog |

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the dialog
- Focus returns to the trigger element on close
- Closes on `Escape` key
- Closes on backdrop click (configurable via `close-on-click-outside`)
- Body scroll is locked while open (configurable via `lock-scroll`)

## Turbo integration

The dialog cancels automatically on `turbo:before-cache`, preventing ghost dialogs when navigating with Turbo Drive.
