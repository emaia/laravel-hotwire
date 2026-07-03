# Reset Files

Clears file inputs (`<input type="file">`) automatically after a successful submit re-renders the form.

**Identifier:** `reset-files`  
**Install:** `php artisan hotwire:controllers reset-files`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:render` for Drive/page-morph renders, `turbo:frame-render` for Turbo Frames).

## How it works

1. On `turbo:submit-end`, records whether the submit of *this* element's form succeeded (HTTP 2xx/3xx).
2. On the following render (`turbo:render` or `turbo:frame-render`), if the submit succeeded **and** the
   re-rendered form has no field marked `aria-invalid="true"`, the file input(s) are cleared. The controller may
   be mounted on the file `<input>` itself (how `<hw:file>` uses it), on the `<form>`, or on a wrapper.

The two-step success check matters: a `200` response that re-renders the form with validation errors reports
`success` on `turbo:submit-end`, so the `aria-invalid` guard is what actually distinguishes success from a failed
validation. `<hw:file>` renders `aria-invalid="true"` on invalid fields automatically; standalone usage must do
the same for the guard to work.

## Usage

Add the controller to the form and mark it with `data-reset-on-success="true"`:

```html
<form
    data-controller="reset-files"
    data-reset-on-success="true"
    action="/uploads"
    method="post"
    enctype="multipart/form-data"
>
    @csrf
    <input type="file" name="avatar" />
    <input type="file" name="document" />

    <button type="submit">Upload</button>
</form>
```

After the server responds successfully and Turbo applies the morph, file inputs are cleared automatically. This avoids the issue of `file` inputs retaining a reference to the previous file even after a DOM morph.

## When to use

- Upload forms that remain on screen after submission (no redirect).
- Forms inside Turbo Frames that re-render after a successful submit.
- Any scenario where the form is re-rendered (Drive morph or frame render) and file inputs should be reset.
