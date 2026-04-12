# Modal

Accessible modal with backdrop, animations, focus trap and Turbo integration.

## Basic usage

```html

<x-hwc::modal>
    <x-slot:trigger>
        <button data-action="dialog--modal#open" type="button">Open modal</button>
    </x-slot:trigger>

    <div class="p-6">
        <h2>Title</h2>
        <p>Modal content.</p>
    </div>
</x-hwc::modal>
```

## With close button

The X button is shown by default (`close-button` is `true`). To hide it:

```html

<x-hwc::modal :close-button="false">
    <x-slot:trigger>
        <button data-action="dialog--modal#open" type="button">Open</button>
    </x-slot:trigger>

    <p class="p-6">Modal without X button.</p>
</x-hwc::modal>
```

## Props

| Prop                   | Type     | Default            | Description                                     |
|------------------------|----------|--------------------|-------------------------------------------------|
| `id`                   | `string` | `uniqid('modal-')` | Root element ID                                 |
| `allow-small-width`    | `bool`   | `false`            | Allows width smaller than 50% on `md+` screens  |
| `allow-full-width`     | `bool`   | `true`             | Allows full width (no `max-w-[50%]`)            |
| `class`                | `string` | `''`               | Additional CSS classes on the content container |
| `close-button`         | `bool`   | `true`             | Shows X button to close                         |
| `fixed-top`            | `bool`   | `false`            | Pins the modal to the top with a margin         |
| `prevent-reopen-delay` | `int`    | `1000`             | Delay (ms) before allowing reopen after closing |

## Slots

| Slot               | Description                                |
|--------------------|--------------------------------------------|
| `trigger`          | Element that triggers the modal opening    |
| `slot` (default)   | Main modal content                         |
| `loading_template` | Template shown while dynamic content loads |

## Dynamic content with Turbo Frames

The modal supports content loaded via Turbo Frame. Use the `dynamicContent` target so the controller observes changes
and opens/closes automatically:

```html

<x-hwc::modal>
    <x-slot:trigger>
        <a
            href="/items/1/edit"
            data-action="dialog--modal#showLoading"
            data-turbo-frame="dialog-modal-content"
        >
            Edit
        </a>
    </x-slot:trigger>

    <turbo-frame id="dialog-modal-content" data-dialog--modal-target="dynamicContent">
    </turbo-frame>

    <x-slot:loading_template>
        <div class="flex items-center justify-center p-12">
            <span>Loading...</span>
        </div>
    </x-slot:loading_template>
</x-hwc::modal>
```

When the Turbo Frame receives content, the modal opens automatically. When the content is removed, it closes.

## Stimulus Values

Configurable via `data-dialog--modal-*-value` on the root element:

| Value                    | Type      | Default | Description                              |
|--------------------------|-----------|---------|------------------------------------------|
| `open-duration`          | `Number`  | `300`   | Opening animation duration (ms)          |
| `close-duration`         | `Number`  | `300`   | Closing animation duration (ms)          |
| `lock-scroll`            | `Boolean` | `true`  | Locks body scroll when open              |
| `close-on-escape`        | `Boolean` | `true`  | Closes on Escape key                     |
| `close-on-click-outside` | `Boolean` | `true`  | Closes when clicking outside the dialog  |
| `prevent-reopen-delay`   | `Number`  | `300`   | Anti-bounce delay in the controller (ms) |

## Actions

| Action                      | Description                                                |
|-----------------------------|------------------------------------------------------------|
| `dialog--modal#open`        | Opens the modal                                            |
| `dialog--modal#close`       | Closes the modal                                           |
| `dialog--modal#showLoading` | Shows the loading template while awaiting a Turbo response |

## Events

| Event          | Description                                 |
|----------------|---------------------------------------------|
| `modal:opened` | Fired after the opening animation completes |
| `modal:closed` | Fired after the closing animation completes |

```javascript
element.addEventListener("modal:opened", (event) => {
    console.log("Modal opened", event.detail.controller);
});
```

## Accessibility

- `role="dialog"` and `aria-modal="true"` on the overlay
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the modal
- Focus returns to the element that triggered the modal on close
- Closes on Escape (configurable)

## Ignore outside click

Elements outside the dialog that should not close the modal can use `data-modal-ignore`:

```html

<div data-modal-ignore>
    This dropdown will not close the modal when clicked.
</div>
```

## Turbo integration

The modal closes automatically on `turbo:before-cache`, preventing ghost modals when navigating with Turbo Drive.
