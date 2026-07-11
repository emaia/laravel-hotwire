# `<hw:sheet>`

Off-canvas sheet panel with backdrop, close button, focus trap, Escape dismissal and click-outside dismissal.

Use `Sheet` for side-panel dialogs. Use [`<hw:drawer>`](./drawer.md) for the Base UI-style drawer primitive.

## Usage

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

## Props

| Prop | Default | Description |
|------|---------|-------------|
| `id` | auto | Root element id. |
| `side` | `right` | `left`, `right`, `top`, or `bottom`. |
| `size` | `75%` for side sheets, `auto` for vertical sheets | CSS length assigned to `--sheet-width` or `--sheet-height`. |
| `frame` | `null` | Turbo Frame id for layout-shared, server-loaded sheet content. |
| `backdrop` | `true` | Render the backdrop and click-outside target. |
| `openDuration` | `300` | Open transition duration in milliseconds. |
| `closeDuration` | `300` | Close transition duration in milliseconds. |
| `lockScroll` | `true` | Lock body scroll while open. |
| `closeOnEscape` | `true` | Close when Escape is pressed. |
| `closeOnClickOutside` | `true` | Close when the backdrop is clicked. |

## Components

| Component | Description |
|-----------|-------------|
| `sheet.trigger` | Button that toggles the sheet. |
| `sheet.content` | Overlay, backdrop and sliding panel wrapper. |
| `sheet.header` | Header region. |
| `sheet.title` | Sheet title. |
| `sheet.description` | Sheet description text. |
| `sheet.footer` | Footer actions region. |
| `sheet.close` | Button that closes the sheet. |

## Behavior

The sheet traps focus while open, restores focus to the trigger on close, locks body scroll by default and closes before Turbo caches the page.

## Frame Content

Use `frame` when one sheet host in your layout should receive many server-rendered panels:

```blade
{{-- layout --}}
<hw:sheet frame="settings-panel" side="right">
    <x-slot:loading_template>
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

Pair the destination view with [`<hw:frame-or-page>`](./frame-or-page.md) so direct navigation renders as a full page and frame navigation renders only the panel payload:

```blade
<hw:frame-or-page frame="settings-panel" layout="layouts.app">
    <form method="POST" action="{{ route('settings.update') }}">
        {{-- fields --}}
    </form>
</hw:frame-or-page>
```

When the frame receives content, the sheet opens automatically. A trigger can override the loading state with `data-loading-template="#template-id"`; otherwise the `loading_template` slot is used.

On successful submit, close the sheet by returning an empty update or replace for the sheet root or frame, or a refresh stream. Stream rendering waits for the close animation to finish:

```php
return turbo_stream()
    ->refresh(method: 'morph')
    ->update('settings-panel')
    ->flash('success', 'Saved');
```
