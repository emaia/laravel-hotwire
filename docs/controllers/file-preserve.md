# File Preserve

Captures and restores file input selection across Turbo morphs and frame navigations, so the user's file choice survives validation errors and page re-renders.

**Identifier:** `file-preserve`
**Install:** `php artisan hotwire:controllers file-preserve`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:morph`, `turbo:before-morph`, `turbo:frame-render`, `turbo:before-frame-render` events).

## How it works

1. **Before morph/frame render**: captures `FileList` from all `<input type="file">` inside the element.
2. **After morph/frame render**: restores the captured `FileList` to the matching inputs.
3. **On a successful submit**: the stash is cleared (so files are NOT carried over after a successful redirect).
4. **On disconnect** (frame replacement): the stash is populated so the new controller instance can restore it.

## Usage

Meant to be used by `<x-hwc::file>`, which auto-includes this controller. For standalone use:

```html
<div data-controller="file-preserve">
    <input type="file" name="avatar" />
</div>
```

## When to use

- Forms inside Turbo Frames where validation errors re-render the frame (the file selection is lost without this controller).
- Any form that uses `turbo:morph` for re-rendering after validation.
- Upload forms where the user expects their file selection to be preserved.
