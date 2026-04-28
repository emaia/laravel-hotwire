# Reset Files

Clears file inputs (`<input type="file">`) automatically after a successful Turbo morph.

**Identifier:** `reset-files`  
**Install:** `php artisan hotwire:controllers reset-files`

## Requirements

- No external dependencies.
- Turbo 8+ (`turbo:morph` event).

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
- Forms inside Turbo Frames that are updated via morph.
- Any scenario where `turbo:morph` is used and file inputs should be reset.
