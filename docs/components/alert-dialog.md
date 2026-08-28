# Alert Dialog

Accessible alert dialog that intercepts clicks and requires user confirmation before proceeding. Works with links,
buttons, form submissions, and Turbo actions.

## Basic Usage

The default slot **is** the trigger — anything inside the component is wrapped in a click-intercept zone. The action
button uses the `default` variant by default:

```blade
<hw:alert-dialog title="Continue?" description="This will proceed.">
    <button type="button">Continue</button>
</hw:alert-dialog>
```

## Destructive Action

Use `confirm-variant="destructive"` when the confirmed action is destructive:

```blade
<hw:alert-dialog
    title="Delete item?"
    description="This action cannot be undone."
    confirm-label="Delete"
    confirm-variant="destructive"
>
    <button type="button">Delete</button>
</hw:alert-dialog>
```

## With Turbo Method

```blade
<hw:alert-dialog
    title="Delete item?"
    description="This action cannot be undone."
    confirm-label="Delete"
    confirm-variant="destructive"
>
    <a href="/items/1" data-turbo-method="delete">Delete</a>
</hw:alert-dialog>
```

## Rich Body Content

When `description` isn't enough — lists of consequences, multiple paragraphs, embedded links — use the `content` slot:

```blade
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

## Automatic Behavior

The default slot is wrapped in a click-intercept zone. When the user clicks any element inside, the click is canceled
and the alert dialog opens. If the user clicks **Confirm**, a captured link or submit action resumes through a transient
unwired element after the actual exit motion settles. Listeners on those triggers therefore observe the user's original
click only once, and replacing the trigger or its external form while the dialog is open does not discard the action. If
the user clicks **Cancel** or presses `Escape`, the dialog closes and nothing happens. Rapid reopen cancels stale close
completion.

Generic `type="button"` actions are deferred in capture phase and run once after confirmation. Give these buttons a
stable `id` when a Turbo morph may replace them while the dialog is open; unlike links and submits, an arbitrary
JavaScript action cannot be reconstructed safely from attributes alone.

The trigger element needs no special attributes — place it as the default slot.

## Tweaking Behavior

Motion, scroll lock, and click-outside behavior are exposed as Blade props — no need to write `data-*-value` attributes:

```blade
<hw:alert-dialog
    title="Are you sure?"
    motion="none"
    :lock-scroll="false"
    :close-on-click-outside="false"
>
    <button type="button">Proceed</button>
</hw:alert-dialog>
```

## Turbo Integration

The dialog closes synchronously on `turbo:before-cache`, preventing ghost dialogs when navigating with Turbo Drive.

## Accessibility

- `role="alertdialog"` and `aria-modal="true"` on the overlay
- `title` and `description` receive stable ids and automatically name and describe the alert dialog
- Focus trap: Tab/Shift+Tab cycle through focusable elements inside the dialog
- Focus returns to the trigger element on close
- Closes on `Escape` key
- Closes on backdrop click (configurable via `close-on-click-outside`)
- Body scroll is locked while open (configurable via `lock-scroll`)

## Requirements

- No external dependencies.
- Ships with `_action_replay.js`, `_composition.js`, `_focus_trap.js`, `_overlay.js`, `_overlay_stack.js`, `_presence.js`, and
  `_top_layer.js`; publishing the `alert-dialog` controller publishes these helpers too.

## Props

| Prop                     | Type             | Default            | Description                                                  |
|--------------------------|------------------|--------------------|--------------------------------------------------------------|
| `id`                     | `string\|object` | generated          | Root id. Pass a model for a [stable cross-request id](../recipes/stable-component-ids.md). |
| `title`                  | `string`         | `''`               | Dialog heading                                               |
| `description`            | `string`         | `''`               | Body text below the title                                    |
| `confirm-label`          | `string`         | `'Confirm'`        | Action button label                                          |
| `cancel-label`           | `string`         | `'Cancel'`         | Cancel button label                                          |
| `confirm-variant`        | `string`         | `'default'`        | Action button variant                                        |
| `cancel-variant`         | `string`         | `'outline'`        | Cancel button variant                                        |
| `confirm-class`          | `string`         | `''`               | Extra CSS classes for the action button                      |
| `cancel-class`           | `string`         | `''`               | Extra CSS classes for the cancel button                      |
| `motion`                 | `string`         | `'default'`        | `default` follows CSS motion; `none` disables it             |
| `lock-scroll`            | `bool`           | `true`             | Locks body scroll when the dialog is open                    |
| `close-on-click-outside` | `bool`           | `true`             | Closes when clicking the backdrop                            |
| `stimulus`               | `Htmlable\|null` | `null`             | Optional extra Stimulus binding merged into the root element |

Regular `data-controller` / `data-action` attributes and the `stimulus` prop are merged and deduplicated with the
internal `alert-dialog` controller. Component-owned `data-alert-dialog-*` attributes are protected; configure supported
dialog behavior with props instead of overriding those attributes directly.

## Slots

| Slot             | Description                                                              |
|------------------|--------------------------------------------------------------------------|
| `slot` (default) | Trigger element whose click is intercepted to open the dialog            |
| `content`        | Optional rich content rendered below `description` and above the buttons |

The `content` slot may use `alert-dialog.title` and `alert-dialog.description` when rich markup is required instead of
the string props. These subcomponents automatically name and describe the alert dialog.

## Need more control?

For fully custom markup — different DOM structure, no Tailwind, or wiring custom buttons inside the dialog — drop down
to the [`alert-dialog` controller](../controllers/alert-dialog.md).

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="alert-dialog-overlay"`
- `data-slot="alert-dialog-backdrop"`
- `data-slot="alert-dialog-panel"`
- `data-slot="alert-dialog-header"`
- `data-slot="alert-dialog-title"`
- `data-slot="alert-dialog-description"`
- `data-slot="alert-dialog-body"`
- `data-slot="alert-dialog-footer"`
- `data-slot="alert-dialog-cancel"`
- `data-slot="alert-dialog-action"`
- `data-slot="alert-dialog"`
- `data-slot="alert-dialog-trigger"`
