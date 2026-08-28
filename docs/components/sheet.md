# `<hw:sheet>`

Off-canvas sheet panel with backdrop, close button, focus trap, Escape dismissal and click-outside dismissal.

Use `Sheet` for side-panel dialogs. Use [`<hw:drawer>`](./drawer.md) for the Base UI-style drawer primitive.

## Basic Usage

```blade
<hw:sheet side="right">
    <hw:sheet.trigger>
        Open
    </hw:sheet.trigger>

    <hw:sheet.content>
        <hw:sheet.header>
            <hw:sheet.title>Edit profile</hw:sheet.title>
            <hw:sheet.description>Update the account details.</hw:sheet.description>
        </hw:sheet.header>

        <div class="flex-1 overflow-y-auto">
            Content
        </div>

        <hw:sheet.footer>
            <hw:sheet.close>Cancel</hw:sheet.close>
        </hw:sheet.footer>
    </hw:sheet.content>
</hw:sheet>
```

`sheet.content` must render inside the same `sheet` root. The root supplies the frame target, side, backdrop and motion
context used by the dependent content. For Turbo Stream updates, replace the sheet frame or render the owning Sheet root
rather than rendering `sheet.content` by itself. `sheet.trigger` does not consume root context and may be rendered by a
standalone partial that lands inside an existing sheet root.

## Automatic Behavior

The sheet traps focus while open, restores focus to the trigger on close, locks body scroll by default and closes
synchronously before Turbo caches the page. Presence derives completion from actual finite CSS motion and supports rapid
reopen, `motion="none"`, and reduced motion without duration timers.

`sheet.title` and `sheet.description` automatically name and describe the dialog overlay. The frame integration assigns
missing ids and refreshes the references when frame content changes.

Set `aria-label`, `aria-labelledby`, `aria-description`, or `aria-describedby` on `<hw:sheet>` when the application owns
the accessible text. The component routes these attributes to the semantic dialog overlay for explicit and automatic
frame content, and authored values take precedence over generated title and description references.

## Frame Content

Use `frame` when one sheet host in your layout should receive many server-rendered panels:

```blade
{{-- layout --}}
<hw:sheet frame="settings-panel" side="right" view-transition>
    <x-slot:loading_template>
        <hw:sheet.title>Loading settings</hw:sheet.title>
        <div class="p-6">Loading...</div>
    </x-slot:loading_template>
</hw:sheet>
```

```blade
{{-- any page using that layout --}}
<a href="{{ route('settings.edit') }}" data-turbo-frame="settings-panel">
    Settings
</a>
```

Pair the destination view with [`<hw:frame-or-page>`](./frame-or-page.md) so direct navigation renders as a full page
and frame navigation renders only the panel payload:

```blade
<hw:frame-or-page frame="settings-panel" layout="layouts.app">
    <hw:sheet.title>Settings</hw:sheet.title>
    <hw:sheet.description>Update your account settings.</hw:sheet.description>

    <form method="POST" action="{{ route('settings.update') }}">
        {{-- fields --}}
    </form>
</hw:frame-or-page>
```

Include a semantic title in any loading template that opens the sheet. Frame-loaded labels are linked before opening,
and explicit unique label ids are preserved.

The sheet guarantees exactly one frame content host. If the slot has no `<hw:sheet.content>`, it appends an empty host
even alongside a trigger or plain slot content. One content host wraps the frame and uses its slot as fallback content;
more than one content host with `frame` is invalid. Frame objects resolve with `dom_id()`, while null, false, empty, and
whitespace-only values disable the host.

The root owns the matching frame id. Use one `<hw:sheet.content>` for fallback content instead of adding a raw
`<turbo-frame>` with the same id.

`view-transition` mounts `turbo--view-transition` on the internal frame host for both automatic and explicit
`sheet.content` markup. It does nothing without `frame`; unsupported browsers keep the normal Turbo render. Enable it on
the sheet host rather than only on the response's `<hw:frame-or-page>` because Turbo preserves the existing host and does
not copy attributes from the response frame.

When the frame receives content, the sheet opens automatically. A trigger can override the loading state with
`data-loading-template="#template-id"`; otherwise the `loading_template` slot is used.

On successful submit, close the sheet by returning an empty update or replace for the sheet root or frame, or a refresh
stream. Stream rendering waits for the actual exit motion to finish:

```php
return turbo_stream()
    ->refresh(method: 'morph')
    ->update('settings-panel')
    ->toast('success', 'Saved');
```

## Requirements

- No external dependencies.
- Ships with `_composition.js`, `_focus_trap.js`, `_frame_overlay.js`, `_overlay.js`, `_overlay_stack.js`,
  `_presence.js`, and `_top_layer.js` through `drawer`; publishing the `sheet` controller publishes these helpers too.

## Props

| Prop                  | Default                                           | Description                                                    |
|-----------------------|---------------------------------------------------|----------------------------------------------------------------|
| `id`                  | auto                                              | String or model root id; see [stable component ids](../recipes/stable-component-ids.md). |
| `side`                | `right`                                           | `left`, `right`, `top`, or `bottom`.                           |
| `size`                | `75%` for side sheets, `auto` for vertical sheets | CSS length assigned to `--sheet-width` or `--sheet-height`.    |
| `frame`               | `null`                                            | String/object Turbo Frame id for layout-shared, server-loaded content. |
| `backdrop`            | `true`                                            | Render the backdrop and click-outside target.                  |
| `motion`              | `default`                                         | `default` follows CSS motion; `none` disables it.              |
| `lockScroll`          | `true`                                            | Lock body scroll while open.                                   |
| `closeOnEscape`       | `true`                                            | Close when Escape is pressed.                                  |
| `closeOnClickOutside` | `true`                                            | Close when the backdrop is clicked.                            |
| `viewTransition`      | `false`                                           | Animate successive renders inside the frame host.              |
| `aria-label`          | `null`                                            | Authored accessible name routed to the dialog overlay.          |
| `aria-labelledby`     | `null`                                            | Authored accessible name reference routed to the dialog overlay. |
| `aria-description`    | `null`                                            | Authored accessible description routed to the dialog overlay.   |
| `aria-describedby`    | `null`                                            | Authored accessible description reference routed to the dialog overlay. |

## Components

| Component           | Description                                  |
|---------------------|----------------------------------------------|
| `sheet.trigger`     | Button that toggles the sheet.               |
| `sheet.content`     | Overlay, backdrop and sliding panel wrapper. |
| `sheet.header`      | Header region.                               |
| `sheet.title`       | Sheet title.                                 |
| `sheet.description` | Sheet description text.                      |
| `sheet.footer`      | Footer actions region.                       |
| `sheet.close`       | Button that closes the sheet.                |

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="sheet-overlay"`
- `data-slot="sheet-trigger"`
- `data-slot="sheet-backdrop"`
- `data-slot="sheet-content"`
- `data-slot="sheet-close-icon"`
- `data-slot="sheet-header"`
- `data-slot="sheet-title"`
- `data-slot="sheet-description"`
- `data-slot="sheet-footer"`
- `data-slot="sheet-close"`
- `data-slot="sheet"`
