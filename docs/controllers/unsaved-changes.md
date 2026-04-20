# Unsaved Changes

Warns the user when attempting to leave a page with unsaved form changes. Integrates with Turbo Drive to intercept
in-app navigations.

**Identifier:** `unsaved-changes`

## Requirements

- No external dependencies.
- Turbo (`turbo:before-visit` and `turbo:render` events).

## Basic usage

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

When the submit button is clicked, navigation is allowed without an alert.

## Ignoring specific fields

Fields that should not trigger the alert (e.g., hidden fields updated dynamically) can be marked with `data-ignore-unsaved-change`:

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

| Element | Check |
|---------|-------|
| `<input type="text">`, `<textarea>` | `value !== defaultValue` |
| `<input type="checkbox">`, `<input type="radio">` | `checked !== defaultChecked` |
| `<select>` | `selectedIndex` differs from default |
| `<select multiple>` | Any `option.selected !== option.defaultSelected` |

## How it works

The controller automatically sets up the required `data-action` on `connect()`:

1. Listens to `turbo:before-visit` on window to intercept Turbo Drive navigations.
2. Listens to form submit to set `allow = true` before submission.
3. On navigation, compares the current field state with their default values.
4. If there are differences and `allow` is `false`, shows `window.confirm()`.

The current controller handles Turbo Drive navigation. Browser tab close and reload handling are outside this
controller's documented behavior.
