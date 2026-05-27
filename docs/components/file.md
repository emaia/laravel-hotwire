# File

File input with auto-derived `id`/`errorKey` from `name`, ARIA wiring, optional existing file display, file selection preservation across Turbo morphs via `file-preserve`, and optional reset after successful upload via `reset-files`.

## Quick example

```blade
<x-hwc::file name="avatar" />
```

Renders an `<input type="file">` with:

- `id="avatar"`, `name="avatar"`
- `aria-describedby="avatar-error"` (always set, for stable screen-reader binding)
- `aria-invalid="true"` and `data-invalid` when `$errors->has('avatar')`
- `aria-required="true"` and `required` when `required` is present

## Props

| Prop              | Type           | Default         | Description                                                       |
|-------------------|----------------|-----------------|-------------------------------------------------------------------|
| `name`            | `string\|null` | —               | Pass-through. Drives `id` and `errorKey` if those aren't set       |
| `id`              | `string\|null` | derived from `name` | Override the auto-derived id                                  |
| `errorKey`        | `string\|null` | derived from `name` | Override for arrays where HTML `name` ≠ validation key        |
| `currentUrl`      | `string\|null` | `null`          | URL of the existing file — renders a link to it                  |
| `currentLabel`    | `string\|null` | `"Current file"` | Custom label for the current file link                          |
| `resetOnSuccess`  | `bool`         | `false`         | Activates the `reset-files` controller to clear the input after a successful `turbo:morph` |
| `multiple`        | `bool`         | `false`         | Allows selecting several files; appends `[]` to `name` so PHP receives an array (see [Multiple files](#multiple-files)) |
| `class`           | `string`       | `""`            | Merged on `<input>`                                              |
| `wrapperClass`    | `string`       | `""`            | Merged on the wrapper `<div>` (forces the wrapper to render — see [Wrapper](#wrapper)) |

Any other HTML attribute (`accept`, `disabled`, `data-*`, `aria-*`) passes through to the `<input>` element.

## No `old()` repopulation

File inputs **cannot** be pre-filled by the browser for security reasons. Unlike `<x-hwc::input>`, this component does not merge `old()` values — `value` attributes have no effect on `<input type="file">`. Validation errors still show normally via `<x-hwc::error>`.

```blade
{{-- Works: shows validation error for the 'avatar' field --}}
<x-hwc::file name="avatar" />

{{-- Does NOT work: file inputs ignore value attributes --}}
<x-hwc::file name="avatar" value="some-file.jpg" />
```

## Auto-derivation

Same convention as `<x-hwc::input>`:

```blade
<x-hwc::file name="variables[0][name]" />
{{-- id="variables-0-name", aria-describedby="variables-0-name-error", errorKey="variables.0.name" --}}
```

Use `error-key` when the HTML name and the validation key diverge:

```blade
<x-hwc::file name="payload[doc]" error-key="user.document" />
```

## Wrapper

By default the component renders a **bare `<input>`** with the `file-preserve` controller (and `reset-files` when enabled) mounted directly on it:

```html
<input type="file" id="avatar" name="avatar" data-controller="file-preserve" aria-describedby="avatar-error" />
```

A wrapping `<div class="hwc-file">` is added **only when needed** — when `current-url` is set (to hold the link) or when you pass `wrapper-class`. The controllers always live on the `<input>`, never on the wrapper, so a custom `data-controller` you pass lands where an uploader controller actually wants to be:

```blade
<x-hwc::file name="avatar" data-controller="my-uploader" />
{{-- <input ... data-controller="my-uploader file-preserve" /> --}}
```

## Existing file display

When editing a record that already has a file, use `current-url` to show a link to it. This is one of the cases that renders the wrapper:

```blade
<x-hwc::file name="avatar" :current-url="$user->avatar_url" />
```

Renders:

```html
<div class="hwc-file">
    <p>Current file: <a href="https://..." target="_blank" rel="noopener">Current file</a></p>
    <input type="file" id="avatar" name="avatar" data-controller="file-preserve" ... />
</div>
```

Customize the link text with `current-label`:

```blade
<x-hwc::file name="avatar"
    :current-url="$user->avatar_url"
    current-label="Foto atual" />
```

## Turbo morph reset

When a file upload form stays on screen after a successful response (common inside Turbo Frames), the file input retains the previously selected file — even after a DOM morph. Use `reset-on-success` to clear it automatically:

```blade
<turbo-frame id="content" src="/uploads/create">
    <x-hwc::form action="/uploads" method="post" enctype="multipart/form-data">
        <x-hwc::file name="document" reset-on-success />
        {{-- ... --}}
        <button type="submit">Upload</button>
    </x-hwc::form>
</turbo-frame>
```

The `<input>` gets `data-controller="file-preserve reset-files" data-reset-on-success="true"`. After a `turbo:morph` event, the file input is cleared automatically.

The `resetOnSuccess` prop requires the `reset-files` controller to be published. The `file-preserve` controller is always mounted on the input — publish it too so `hotwire:check` passes.

## File preservation across Turbo morphs

The `file-preserve` controller is always active on the `<input>`. It captures and restores file input selections across Turbo morphs and frame navigations, so the user's file choice survives validation errors and page re-renders:

- On `turbo:submit-end`: arms the controller if the form was submitted.
- Before `turbo:before-render` / `turbo:before-frame-render`: captures the `FileList`.
- After `turbo:render` / `turbo:frame-render`: if the re-rendered form has `aria-invalid="true"` (validation failure), restores the files. Otherwise, the stash is discarded (files are not carried over after a successful submit).

Because preservation hinges on the field being marked invalid, multi-file validation needs the `cover.*` error detection described under [Multiple files](#multiple-files) — otherwise a per-file failure would look like a success and the selection would be dropped.

## Inheriting from `<x-hwc::field>`

```blade
<x-hwc::field name="avatar" label="Photo" required>
    <x-hwc::file :current-url="$user->avatar_url" />
</x-hwc::field>
```

`name`, `id`, `errorKey`, and `required` are inherited via `@aware`. The field auto-renders `<x-hwc::label>` and `<x-hwc::error>`. The ARIA contract is maintained — the input's `aria-describedby` always matches the error element.

## Accepting file types

```blade
{{-- Only images --}}
<x-hwc::file name="avatar" accept="image/*" />

{{-- Only PDFs and DOCs --}}
<x-hwc::file name="document" accept=".pdf,.doc,.docx" />

{{-- Multiple types --}}
<x-hwc::file name="attachment" accept=".pdf,image/*,.zip" />
```

## Multiple files

Use the `multiple` prop to let the user select several files at once:

```blade
<x-hwc::file name="cover" multiple />
```

The prop appends `[]` to the HTML `name` for you (`name="cover[]"`), so PHP receives the selection as an **array** — without it, the browser only submits a single file and an `array` validation rule fails with *"must be an array"*. The derived `id` and error key stay bracket-free (`id="cover"`, key `cover`), so they match your validation rules. Passing `name="cover[]"` yourself also works (the brackets aren't doubled).

Validate with per-file rules:

```php
$request->validate([
    'cover'   => ['required', 'array'],
    'cover.*' => ['image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
]);
```

When a per-file rule fails, Laravel reports the error under a sub-key (`cover.0`, `cover.1`, …). The component detects errors on both `cover` **and** `cover.*`, so the field still gets `aria-invalid`/`data-invalid` and — crucially — `file-preserve` recognizes the failed submit and **keeps the selected files** across the re-render.

## Combining features

```blade
<x-hwc::file name="avatar"
    :current-url="$user->avatar_url"
    current-label="Foto atual"
    accept="image/*"
    reset-on-success
    required />
```

Renders the wrapper (for the current-file link) around an `<input>` carrying the `file-preserve` and `reset-files` controllers and an image-only restriction.

## Custom JavaScript

A `data-controller` you pass is merged onto the **`<input>`** alongside `file-preserve` (and `reset-files` when enabled) — that's where an uploader controller wants to operate:

```blade
<x-hwc::file name="avatar" data-controller="my-uploader" />
{{-- <input ... data-controller="my-uploader file-preserve" /> --}}

<x-hwc::file name="avatar" reset-on-success data-controller="my-uploader" />
{{-- <input ... data-controller="my-uploader file-preserve reset-files" /> --}}
```

## Required controllers

`hotwire:check` looks for `file-preserve` (always used by this component) and `reset-files` (when `reset-on-success` is enabled). Both must be published for `hotwire:check` to pass.
