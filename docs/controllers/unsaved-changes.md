# Unsaved Changes

Warns the user when attempting to leave a page with unsaved form changes. Integrates with Turbo Drive to intercept
in-app navigations.

**Identifier:** `unsaved-changes`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers unsaved-changes`.

## Requirements

- No external dependencies.
- Turbo (`turbo:before-visit`, `turbo:submit-start` and `turbo:submit-end` events).

## Basic Usage

```html
<form
    data-controller="unsaved-changes"
    action="/items/1"
    method="post"
>
    @csrf
    @method('PUT')

    <input type="text" name="title" value="{{ $item->title }}" />
    <textarea name="description">{{ $item->description }}</textarea>

    <button type="submit">Save</button>
</form>
```

If the user changes any field and tries to navigate away through a Turbo link, a browser confirmation dialog is shown.

Navigation is allowed without an alert only when Turbo actually starts submitting the form (`turbo:submit-start`),
whether triggered by a button, Enter or `requestSubmit()`. A click blocked by browser validation, or a prevented
native `submit`, does not disable the warning.

If the submission ends without success (`turbo:submit-end`), including server validation or network errors, the
warning is rearmed for fields that still differ from their defaults. Successful submissions keep navigation allowed.

## Ignoring specific fields

Fields that should not trigger the alert (e.g., hidden fields updated dynamically) can be marked with
`data-ignore-unsaved-change`:

```html
<form data-controller="unsaved-changes" action="/items/1" method="post">
    @csrf
    @method('PUT')

    <!-- This field changes without user interaction, ignore it -->
    <input type="hidden" name="last_tab" data-ignore-unsaved-change />

    <input type="text" name="title" value="{{ $item->title }}" />

    <button type="submit">Save</button>
</form>
```

## Monitored fields

The controller detects changes in:

| Element                                           | Check                                            |
|---------------------------------------------------|--------------------------------------------------|
| `<input type="text">`, `<textarea>`               | `value !== defaultValue`                         |
| `<input type="checkbox">`, `<input type="radio">` | `checked !== defaultChecked`                     |
| `<select>`                                        | `selectedIndex` differs from default             |
| `<select multiple>`                               | Any `option.selected !== option.defaultSelected` |

## How it works

The controller attaches listeners on `connect()` and removes them on `disconnect()`, without modifying the form's
or submit buttons' `data-action`:

1. Listens to `turbo:before-visit` on window to intercept Turbo Drive navigations.
2. Listens to `turbo:submit-start` on its own form to allow submission navigation.
3. Listens to `turbo:submit-end` on its own form to rearm the warning when `detail.success` is falsy.
4. On navigation, compares the current field state with their default values.
5. If there are differences and navigation is not allowed, shows `window.confirm()`.

The current controller handles Turbo Drive navigation. Browser tab close and reload handling are outside this
controller's documented behavior.

## Turbo morph support

When a morph preserves the form element, its listeners remain attached even if the server HTML rewrites
`data-action`. This also applies to Frame or Stream updates that retain the form.

Change detection still compares each field with its current defaults; this controller does not prevent a render
from removing fields or resetting their values/defaults. Use [Morph Guard](turbo/morph-guard.md) when an editor needs
to be protected from an outer page morph.
