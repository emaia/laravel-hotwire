# File upload

**[Dropzone](https://github.com/NicolasCARPi/dropzone) wrapper** — drag-and-drop, multi-file queue,
client-side preview and progress, with the upload endpoint, validation, storage and cleanup all
**app-side**. Renders a `<div>` mounted on the [`file-upload`](../controllers/file-upload.md)
Stimulus controller. Pairs with `<x-hwc::field>`, `<x-hwc::error>`, and Laravel's `old()` /
validation redirect-back out of the box, and ships native Turbo Stream response support so
server-rendered cards just work.

## Quick example

```blade
<x-hwc::form action="{{ route('profile.update') }}" method="put">
    <x-hwc::field name="avatar" label="Profile picture">
        <x-hwc::file-upload url="{{ route('uploads.store') }}" accept="image/*" />
    </x-hwc::field>

    <button type="submit">Save</button>
</x-hwc::form>
```

`<x-hwc::field>` renders the label and auto-emits the `<x-hwc::error>` block under the slot, so a
field-wrapped file-upload reads as a single block.

The endpoint receives one `multipart/form-data` request per file under the field name `file`
(override via `param-name`). It returns JSON by default; the controller reads `response.token`
(override via `response-key`) and appends `<input type="hidden" name="avatar" value="{token}">` to
the parent form so the next submission carries the reference.

See the [recipes](#recipes) for Spatie Media Library, async thumbnails with broadcast, and a
fully stream-rendered gallery with server-side EXIF.

## Setup — the Dropzone CSS import

The component renders the upload host as `<div data-slot="file-upload" class="dropzone">` and the controller imports Dropzone's CSS.
`data-slot="file-upload"` is the package styling contract; `dropzone` is the third-party class Dropzone itself expects for its default message, preview and state CSS.

The visible affordance depends on `@deltablot/dropzone/dist/dropzone.css` reaching the bundle.

The controller imports the CSS at the top:

```js
import "@deltablot/dropzone/dist/dropzone.css";
```

If the component renders but **nothing is visible** (no border, no message, 0-height div), the
import isn't reaching your bundle. Three things to check, in order:

1. Run `bun install` (or `npm install`) — `hotwire:check --fix` adds `@deltablot/dropzone` to
   `package.json` but doesn't run install for you.
2. Restart `vite dev` — the dev server caches the import graph; a freshly-published controller
   may need a kick to be picked up.
3. Open DevTools → Network and confirm `dropzone.css` (or its content as an inline `<style>` in
   dev mode) appears. If it doesn't, your bundler isn't processing the CSS import — check your
   Vite config or open an issue.

To customise the visual without touching the package's CSS, target `[data-slot="file-upload"]`, `.dropzone` and `.dz-*`
selectors in your app stylesheet (loaded after the controller's import, so cascade wins). For a
full takeover, delete the `// @hotwire-package` marker from the published controller file — the
package will leave your customised version alone on subsequent `hotwire:controllers --force` runs.

## Props

| Prop               | Type             | Default          | Description                                                                                              |
|--------------------|------------------|------------------|----------------------------------------------------------------------------------------------------------|
| `url`              | `string`         | *(required)*     | Endpoint that accepts a `multipart/form-data` POST per file and returns JSON (or a Turbo Stream — see below). Throws `InvalidArgumentException` when missing |
| `name`             | `string\|null`   | `null`           | Form field name carried in the hidden input. With `multiple`, `[]` is appended automatically. Also drives `id`, `errorKey` and the `aria-describedby` link |
| `value`            | `mixed`          | `null`           | Initial value(s) for the field. String token in single mode, array of tokens in multi. Overridden by `old()` after a validation-failure redirect-back |
| `id`               | `string\|null`   | `null`           | Overrides the auto-derived id (`FieldKey::toId($name)`). Falls back to `hwc-file-upload-{uniqid}` when name is absent |
| `errorKey`         | `string\|null`   | `null`           | Overrides the auto-derived error key. Use when validation errors live under a different path than the field name |
| `accept`           | `string\|null`   | `null`           | MIME pattern or extension list (`"image/*"`, `".pdf,.csv"`) — forwarded to Dropzone's `acceptedFiles`     |
| `maxSizeBytes`     | `int\|null`      | `null`           | Per-file size limit. Converted to MB before reaching Dropzone (`maxFilesize`)                            |
| `maxFiles`         | `int\|null`      | `null`           | Maximum number of files the queue accepts                                                                |
| `multiple`         | `bool`           | `false`          | Enables multi-file selection. Hidden input name becomes `name[]`                                         |
| `preview`          | `bool`           | `true`           | When `false`, suppresses Dropzone's preview list (`previewsContainer: false`). Pair with Turbo Streams for server-rendered cards |
| `emitHidden`       | `bool`           | `true`           | When `false`, the controller does not append a hidden input on success — the server-rendered card embeds it instead |
| `turboStream`      | `bool`           | `false`          | When `true`, sends `Accept: text/vnd.turbo-stream.html, application/json` on the upload XHR; if the response is a `<turbo-stream>` it's applied via `Turbo.renderStreamMessage`. See [Turbo Streams](#turbo-streams) |
| `paramName`        | `string`         | `'file'`         | Multipart field name used in each XHR — matches `$request->file('file')` server-side                     |
| `responseKey`      | `string`         | `'token'`        | Key read from the JSON response to populate the hidden input value. Use `'uuid'` for Spatie media, `'url'` for direct-to-S3, etc. |
| `deleteUrl`        | `string\|null`   | `null`           | DELETE endpoint hit when a queued file is removed. `:token` is substituted with the extracted value      |
| `parallelUploads`  | `int`            | `3`              | Concurrent XHRs in the queue                                                                             |
| `messages`         | `array\|null`    | `null`           | Localized strings for Dropzone's built-in UI. Short keys (`default`, `fileTooBig`, …) map to `dict*` options. See [Messages and i18n](#messages-and-i18n) |
| `options`          | `array\|null`    | `null`           | Escape hatch — any extra Dropzone configuration option, JSON-encoded into a data-value. Overrides per-prop defaults; subclass `defaultOptions()` still wins. See [Options escape hatch](#options-escape-hatch) |
| `class`            | `string`         | `''`             | Merged on the wrapper                                                                                   |
| `controller`       | `string`         | `'file-upload'`  | Stimulus identifier — swap for a subclass (e.g. `controller="my-upload"`)                                |

Any other HTML attribute (`aria-label`, `data-*`, etc.) passes through to the wrapper. Internal
attributes (`data-{identifier}-*-value`, `data-controller`, `data-action`) are merged in PHP and
the user-provided values are deliberately filtered to prevent conflicts. Expose configuration via
the props above instead.

## Single file

```blade
<x-hwc::file-upload name="avatar" url="{{ route('uploads.store') }}" accept="image/*" />
```

```php
Route::post('/uploads', function (Request $r) {
    $file = $r->validate(['file' => 'required|image|max:2048'])['file'];
    $path = $file->store('temp-uploads');

    return response()->json(['token' => $path]);
})->middleware(['auth', 'throttle:20,1'])->name('uploads.store');
```

The submit handler later resolves the token (read the temp path, move to permanent storage).

## Multiple files

```blade
<x-hwc::file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    :delete-url="route('uploads.destroy', ':token')"
    multiple
    :max-files="5"
    :max-size-bytes="10 * 1024 * 1024"
    accept=".pdf,.csv,image/*"
/>
```

Hidden inputs render as `attachments[]` per uploaded file. The `:token` placeholder in `delete-url`
is substituted with the extracted response value when the user removes a queued file.

## Edit forms and validation redirect-back

The `value` prop pre-populates the hidden input(s) so existing data is carried into the form. On
a validation-failure redirect-back, Laravel's `old($name)` automatically takes precedence — the
user's most recent upload reference is preserved without re-upload.

```blade
{{-- Editing a user that already has an avatar token --}}
<x-hwc::field name="avatar_token" label="Profile picture">
    <x-hwc::file-upload
        url="{{ route('uploads.store') }}"
        :value="$user->avatar_token"
        accept="image/*"
    />
</x-hwc::field>

{{-- Editing a post that already has attachments --}}
<x-hwc::field name="attachments" label="Attachments">
    <x-hwc::file-upload
        url="{{ route('uploads.store') }}"
        :value="$post->attachment_tokens"
        multiple
    />
</x-hwc::field>
```

The view emits one `<input type="hidden" name="..." value="..." data-hw-upload-preserved>` per
existing value, **before** Dropzone mounts. On the next form submit (even without the user touching
the upload area) the existing tokens go through unchanged.

When the user does upload a new file, the controller's behaviour depends on the mode:

- **Single mode**: the preserved hidden is removed before the new one is appended. Only one
  token at a time.
- **Multi mode**: preserved hiddens stay; the new upload adds another. The list of attachments
  accumulates.
- **`emit-hidden="false"`**: the controller never touches hiddens — the server-rendered card
  owns the lifecycle (typical when pairing with `:turbo-stream="true"`, see the gallery recipe).

**Known v1 limitation — visual gap**: pre-existing files don't render in Dropzone's preview queue
on initial load. The data is preserved on the form and re-submit works without re-upload, but the
drop area shows the empty "Drop files here" state. To show name/thumbnail/EXIF of a pre-existing
file in the queue requires `name`/`size` metadata in the response shape and a separate prop —
deferred to a future release. For now: either accept the empty-queue UX, or use the
[stream-rendered gallery pattern](#3-stream-rendered-gallery-with-server-side-exif) where the
visible state lives in a separate server-rendered list, not in the Dropzone area.

## Turbo Streams

Set `:turbo-stream="true"` to have the controller negotiate Turbo Stream responses end-to-end:

- Sends `Accept: text/vnd.turbo-stream.html, application/json` on every upload XHR
- On `success` (any 2xx response): if the body contains `<turbo-stream`, hands it to
  `Turbo.renderStreamMessage` and skips the automatic hidden input — the server-rendered card is
  expected to carry the hidden internally. Falls back to JSON parsing when the response isn't a
  stream
- On `error` (non-2xx): if the response body looks like a stream, renders it too — useful for
  rendering inline error messages via a stream targeting an `<errors-region>` element. The
  controller still announces the error and dispatches `file-upload:error` so app listeners stay
  informed

When `Turbo` isn't loaded globally (no `@hotwired/turbo` import on the page), stream rendering is
skipped silently and the controller falls back to the JSON path. No throws.

See the [stream-rendered gallery recipe](#3-stream-rendered-gallery-with-server-side-exif) for a
full end-to-end example.

## Keyboard accessibility

The wrapper is a focusable button widget:

- `tabindex="0"` puts it in the tab order
- `role="button"` and a default `aria-label="Choose files"` announce intent to screen readers
- `keydown.enter` and `keydown.space` are wired to the controller's `openPicker` action, which
  clicks Dropzone's hidden file input

Override the label when context demands a specific call-to-action:

```blade
<x-hwc::file-upload url="..." aria-label="Attach signed contract" />
```

## Validation feedback

On error the wrapper emits `aria-invalid="true"` plus `data-invalid` for CSS hooks. Compose with
`<x-hwc::error name="..." />` directly under the file-upload (or rely on `<x-hwc::field>` to render
it) to show the message. For multi-file rules (`attachments.*`), any sub-key error marks the
wrapper invalid.

Server-side errors returned per-upload (a 422 with `{ message, errors: { file: [...] } }`) are
normalised by the controller: the announcer, the thumb's error tooltip, and the
`file-upload:error` event detail all carry a readable string instead of `[object Object]`.

## Screen reader announcements

The view always renders an `aria-live="polite"` status region the controller writes to at upload
milestones (`Uploading X`, `Uploaded X`, `Upload failed: …`, `Removed X`). Per-tick progress is
intentionally not announced — too noisy.

## Messages and i18n

Dropzone ships built-in English strings ("Drop files here to upload", "File is too big",
etc.). To localize them — or just to reword — pass a `:messages` array. Short keys map to
Dropzone's `dict*` options under the hood, so the array travels straight from `lang/*` to
the rendered widget.

```blade
<x-hwc::file-upload
    name="avatar"
    url="{{ route('uploads.store') }}"
    :messages="__('hotwire.file_upload')"
/>
```

```php
// lang/pt_BR/hotwire.php
return [
    'file_upload' => [
        'default' => 'Arraste arquivos aqui ou clique para selecionar',
        'fileTooBig' => 'Arquivo muito grande ({{filesize}}MiB). Máximo: {{maxFilesize}}MiB.',
        'invalidFileType' => 'Tipo de arquivo não permitido.',
        'maxFilesExceeded' => 'Você atingiu o limite de arquivos.',
        'removeFile' => 'Remover arquivo',
    ],
];
```

| Short key                  | Maps to                          |
|----------------------------|----------------------------------|
| `default`                  | `dictDefaultMessage`             |
| `fallback`                 | `dictFallbackMessage`            |
| `fallbackText`             | `dictFallbackText`               |
| `fileTooBig`               | `dictFileTooBig`                 |
| `invalidFileType`          | `dictInvalidFileType`            |
| `responseError`            | `dictResponseError`              |
| `cancelUpload`             | `dictCancelUpload`               |
| `cancelUploadConfirmation` | `dictCancelUploadConfirmation`   |
| `uploadCanceled`           | `dictUploadCanceled`             |
| `removeFile`               | `dictRemoveFile`                 |
| `removeFileConfirmation`   | `dictRemoveFileConfirmation`     |
| `maxFilesExceeded`         | `dictMaxFilesExceeded`           |
| `fileSizeUnits`            | `dictFileSizeUnits`              |

Unknown keys throw an `InvalidArgumentException` at construction so typos surface early.
For dict options not in the table above (Dropzone may add new ones), use `:options` —
which accepts the raw `dict*` form directly.

## Options escape hatch

Most Dropzone configuration is already covered by named props (`accept`, `maxSizeBytes`,
`parallelUploads`, etc.). For the rest — `thumbnailMethod`, `resizeQuality`,
`createImageThumbnails`, custom `headers`, etc. — pass an `:options` array:

```blade
<x-hwc::file-upload
    name="cover"
    url="{{ route('uploads.store') }}"
    :options="[
        'thumbnailMethod' => 'contain',
        'resizeQuality' => 0.9,
        'createImageThumbnails' => true,
    ]"
/>
```

The array is JSON-encoded into a single `data-{identifier}-options-value` attribute and
spread over the wrapper's defaults in the controller. Precedence, lowest to highest:

1. **Base defaults** (`paramName: 'file'`, `parallelUploads: 3`, etc.)
2. **`<x-slot:preview_template>`** — sets `previewTemplate` from your Blade markup (see
   [Custom preview template](#custom-preview-template))
3. **`:options`** — wins over base defaults and the slot, so you can override
   `parallelUploads`, `previewTemplate`, etc.
4. **Subclass `defaultOptions()`** — wins over everything, because subclass code is
   explicit user intent

`:options` and `:messages` share the same JSON bag. When both touch the same key (e.g.
`:messages="['default' => 'A']"` and `:options="['dictDefaultMessage' => 'B']"`), the
`:options` value wins — it's the lower-level escape hatch.

When a customization needs **JavaScript** (`accept`, `transformFile`, paste-from-clipboard,
custom thumbnails, swapping the upload protocol entirely), reach for a [subclass](#controller-swap--subclass-extensibility)
instead. Rule of thumb: `:options` is for values; subclass is for behavior.

## Custom preview template

To replace Dropzone's default thumbnail layout with your own HTML — different markup,
Tailwind classes, an extra "uploaded by X" line — pass a `preview_template` slot:

```blade
<x-hwc::file-upload name="cover" url="{{ route('uploads.store') }}" accept="image/*">
    <x-slot:preview_template>
        <div class="dz-preview dz-file-preview rounded-lg border p-3 inline-block mr-2">
            <div class="relative w-32 h-32">
                <img data-dz-thumbnail class="w-full h-full object-cover rounded">
                <button type="button"
                        data-dz-remove
                        class="absolute -top-2 -right-2 bg-white border rounded-full w-6 h-6">
                    ×
                </button>
            </div>
            <div class="mt-2 text-sm truncate" data-dz-name></div>
            <div class="text-xs text-gray-500" data-dz-size></div>
            <div class="h-1 mt-1 bg-gray-200 rounded">
                <div class="h-full bg-blue-500 rounded" data-dz-uploadprogress style="width:0%"></div>
            </div>
            <div class="text-xs text-red-600 mt-1" data-dz-errormessage></div>
        </div>
    </x-slot:preview_template>
</x-hwc::file-upload>
```

The component renders the slot as a `<template data-{identifier}-target="previewTemplate">`
inside the wrapper. The controller reads its `innerHTML` at construction and passes the
string to Dropzone's `previewTemplate` option — so Dropzone clones your markup per file
and binds the `data-dz-*` selectors as usual.

Required `data-dz-*` hooks (Dropzone targets them by selector to wire per-file state):

| Selector              | Purpose                                                                  |
|-----------------------|--------------------------------------------------------------------------|
| `data-dz-thumbnail`   | `<img>` that receives the generated thumbnail as `src`                   |
| `data-dz-name`        | Container for the filename                                               |
| `data-dz-size`        | Container for the formatted file size                                    |
| `data-dz-uploadprogress` | Element whose `width` is updated as the XHR progresses                |
| `data-dz-errormessage`| Container for per-file error text                                        |
| `data-dz-remove`      | Trigger that removes the file from the queue when clicked (only emitted if you want a remove button) |

**`<template>` content is inert.** Native HTML `<template>` lives in a `DocumentFragment`,
so its children don't match `document.querySelectorAll(...)`, don't render, and can't be
focused. Tailwind still scans the Blade view for class strings, so utilities you write
inside the slot are picked up by JIT as long as the file is on Tailwind's `content` list.

**Interaction with `:preview="false"`.** The slot only takes effect when previews are
enabled (the default). Passing both the slot and `:preview="false"` is contradictory —
the slot wins and previews stay on. Use `:preview="false"` when you want **no** client
preview at all (typically paired with `:turbo-stream="true"` and server-rendered cards).

**When subclassing,** the target prefix follows the swapped identifier: `controller="my-upload"`
emits `<template data-my-upload-target="previewTemplate">`. Subclass `defaultOptions()` can
still override `previewTemplate` if it returns one — subclass code wins over the slot.

## Controller swap — subclass extensibility

Mirroring `<x-hwc::chart>` and `<x-hwc::map>`, override `controller=` to mount a Stimulus subclass.
Every `data-*-value` and `data-*-target` follows the new identifier automatically:

```blade
<x-hwc::file-upload controller="medialibrary-upload" name="avatar" url="..." />
```

Renders `data-controller="medialibrary-upload" data-medialibrary-upload-url-value="..."` etc. See
the [controller doc](../controllers/file-upload.md#extending-via-subclass) for `defaultOptions()`
and `afterInit()` hooks.

## Combining with other behavior

`data-controller` and `data-action` pass through, merged with the file-upload identifier and the
keydown wiring:

```blade
<x-hwc::file-upload
    url="..."
    data-controller="analytics-track"
    data-action="file-upload:success->gallery#refresh"
/>
```

Renders the wrapper with both controllers active and the gallery refresh action prepended to the
keyboard bindings.

## Recipes

Real-world patterns covering Spatie Media Library, async thumbnails via broadcast, a
stream-rendered gallery with server-side EXIF, and a single-file edit form with stream-replaced
cards live in their own page: see **[File upload patterns](../recipes/file-upload-patterns.md)**.

Quick chooser:

| Pattern | When |
|---|---|
| [Spatie Media Library](../recipes/file-upload-patterns.md#1-spatie-media-library) | App already uses spatie/media-library; want UUIDs not tokens; simple JSON response |
| [Async thumbnail via broadcast](../recipes/file-upload-patterns.md#2-async-thumbnail-via-broadcast) | Heavy server-side processing (transcoding, conversion); response is immediate, broadcast updates later |
| [Stream-rendered gallery with EXIF](../recipes/file-upload-patterns.md#3-stream-rendered-gallery-with-server-side-exif) | Multi-file upload with server-rendered cards (thumbnails, metadata, remove buttons) |
| [Single-file edit form (avatar pattern)](../recipes/file-upload-patterns.md#4-single-file-edit-form-with-a-stream-replaced-card-avatar-pattern) | Single-value resource (avatar, cover, signature) with Turbo Stream UX |
| [Rich media library list with rename and reorder](../recipes/file-upload-patterns.md#5-rich-media-library-list-with-rename-and-reorder) | Vertical list of cards with editable names, drag-to-reorder, file metadata — like the Dropzone Bootstrap demo or Spatie media library UI |

## See also

- [File upload controller](../controllers/file-upload.md) — values, actions, events, subclass hooks
- [`<x-hwc::file>`](file.md) — the simpler input variant for forms that don't need previews or
  progress
