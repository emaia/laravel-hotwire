# File

File input with auto-derived `id`/`errorKey` from `name`, ARIA wiring, optional existing file display, and Turbo morph reset integration via the built-in `reset-files` controller.

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
| `class`           | `string`       | `""`            | Merged on `<input>`                                              |
| `wrapperClass`    | `string`       | `""`            | Merged on the wrapper `<div>` when one is present                |

Any other HTML attribute (`accept`, `disabled`, `multiple`, `data-*`, `aria-*`) passes through to the `<input>` element.

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

## Existing file display

When editing a record that already has a file, use `current-url` to show a link to it:

```blade
<x-hwc::file name="avatar" :current-url="$user->avatar_url" />
```

Renders:

```html
<div class="hwc-file">
    <p>Current file: <a href="https://..." target="_blank" rel="noopener">Current file</a></p>
    <input type="file" id="avatar" name="avatar" ... />
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

The wrapper `<div>` gets `data-controller="reset-files" data-reset-on-success="true"`. After a `turbo:morph` event, the file input is cleared automatically.

The `resetOnSuccess` prop requires the `reset-files` controller to be published. Run `hotwire:check` to verify.

## Without wrapper

When neither `current-url` nor `reset-on-success` is set, only the bare `<input type="file">` is rendered — no wrapping `<div>`:

```blade
<x-hwc::file name="avatar" />
```

```html
<input type="file" id="avatar" name="avatar" aria-describedby="avatar-error" />
```

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

The `multiple` attribute passes through to the `<input>`:

```blade
<x-hwc::file name="attachments[]" multiple />
```

Use the array bracket notation (`name="attachments[]"`) so PHP receives all selected files as an array.

## Combining features

```blade
<x-hwc::file name="avatar"
    :current-url="$user->avatar_url"
    current-label="Foto atual"
    accept="image/*"
    reset-on-success
    required />
```

Renders a wrapper with the current file link, `reset-files` controller, and a file input with image-only restriction.

## Custom JavaScript

When using `data-controller` with the file component, the controller is placed on the **wrapper** (when one exists) or on the **input** (when there's no wrapper):

```blade
{{-- No wrapper: controller goes on the input --}}
<x-hwc::file name="avatar" data-controller="my-uploader" />

{{-- With wrapper (current-url or reset-on-success): controller goes on the wrapper --}}
<x-hwc::file name="avatar" current-url="/old.jpg" data-controller="my-uploader" />
```

## Required controllers

`hotwire:check` looks for `reset-files` when you use the `reset-on-success` prop.
