# File Preserve

Captures and restores file input selection across Turbo morphs and frame navigations, so the user's file choice survives validation errors and page re-renders.

**Identifier:** `file-preserve`
**Install:** `php artisan hotwire:controllers file-preserve`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:before-render`/`turbo:render` for Drive and page-morph renders,
  `turbo:before-frame-render`/`turbo:frame-render` for Turbo Frames).

## How it works

1. **On `turbo:submit-end`**: arms the controller if *this* element's form was the one submitted.
2. **Before the render** (`turbo:before-render`/`turbo:before-frame-render`): while armed, captures the `FileList`
   from the file input(s) into a stash keyed by input name. The controller may be mounted on the `<input>` itself
   (how `<hw:file>` uses it) or on a wrapper containing one or more file inputs.
3. **After the render** (`turbo:render`/`turbo:frame-render`): if the re-rendered form has a field marked
   `aria-invalid="true"` (a failed/validation submit), restores the stashed files to the matching inputs; otherwise
   the stash for those inputs is discarded (so files are NOT carried over after a successful submit).
4. **On disconnect** (frame replacement): if still armed, the stash is populated so the incoming controller instance
   can restore it.

The `aria-invalid="true"` marker is the signal for "the submit failed". `<hw:file>` renders it automatically on
invalid fields; standalone usage must render it too, or files will never be restored. Each instance only stashes and
restores its own inputs, so multiple file fields on the same page are preserved independently.

## Usage

Meant to be used by `<hw:file>`, which mounts this controller directly on the `<input>`. For standalone use, mount it on the input:

```html
<input type="file" name="avatar" data-controller="file-preserve" />
```

Or on a wrapper, when several inputs should share one instance:

```html
<div data-controller="file-preserve">
    <input type="file" name="avatar" />
    <input type="file" name="document" />
</div>
```

## When to use

- Forms inside Turbo Frames where validation errors re-render the frame (the file selection is lost without this controller).
- Any form that uses `turbo:morph` for re-rendering after validation.
- Upload forms where the user expects their file selection to be preserved.
