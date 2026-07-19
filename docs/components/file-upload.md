# File Upload

Native drag-and-drop upload with an attachment queue, progress, optional hidden inputs, DELETE-on-remove and Turbo
Stream response support. The upload endpoint, validation, storage and cleanup stay app-side.

```blade
<hw:form action="{{ route('profile.update') }}" method="put">
    <hw:field name="avatar" label="Profile picture">
        <hw:file-upload url="{{ route('uploads.store') }}" accept="image/*" />
    </hw:field>

    <hw:button type="submit">Save</hw:button>
</hw:form>
```

The controller uploads one `multipart/form-data` request per file using `XMLHttpRequest`, so progress events are real.
Successful JSON responses write a hidden input with `response.token` by default.

## Props

| Prop               | Type           | Default       | Description                                                                                                                                                                                 |
|--------------------|----------------|---------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `url`              | `string`       | required      | Endpoint that accepts each upload request.                                                                                                                                                  |
| `name`             | `string\|null` | `null`        | Hidden input name. With `multiple`, `[]` is appended automatically.                                                                                                                         |
| `value`            | `mixed`        | `null`        | Initial token(s). Overridden by `old($name)` after validation redirect-back.                                                                                                                |
| `id`               | `string\|null` | derived       | Root id. Falls back to `hw-file-upload-{uniqid}` without a name.                                                                                                                            |
| `error-key`        | `string\|null` | derived       | Validation key override.                                                                                                                                                                    |
| `accept`           | `string\|null` | `null`        | Native accept list (`image/*`, `.pdf,.csv`). Values are trimmed/lowercased and also validated before upload.                                                                                 |
| `max-size-bytes`   | `int\|null`    | `null`        | Per-file client-side size limit. Server validation is still required.                                                                                                                       |
| `max-files`        | `int\|null`    | `null`        | Maximum queued files.                                                                                                                                                                       |
| `multiple`         | `bool`         | `false`       | Allows several files and accumulates hidden inputs.                                                                                                                                         |
| `preview`          | `bool`         | `true`        | When false, skips client-side attachment cards. Useful with Turbo Stream galleries.                                                                                                         |
| `emit-hidden`      | `bool`         | `true`        | When false, the controller does not append hidden inputs.                                                                                                                                   |
| `turbo-stream`     | `bool`         | `false`       | Sends a Turbo Stream Accept header and renders stream responses.                                                                                                                            |
| `param-name`       | `string`       | `file`        | Multipart field name for each upload request.                                                                                                                                               |
| `response-key`     | `string`       | `token`       | JSON key used for the hidden input value.                                                                                                                                                   |
| `delete-url`       | `string\|null` | `null`        | DELETE endpoint used when removing an uploaded file. Every `:token` placeholder is URI-encoded.                                                                                              |
| `parallel-uploads` | `int`          | `3`           | Concurrent upload count.                                                                                                                                                                    |
| `clearable`        | `bool\|null`   | `multiple`    | Renders a Clear all action. Defaults to true for `multiple` uploads and false for single uploads; pass false to disable.                                                                     |
| `density`          | `string`       | `default`     | Drop area density: `default` or `compact`.                                                                                                                                                  |
| `view`             | `string`       | `list`        | Attachment view: `list` or `grid`. Grid uses vertical cards and image thumbnails.                                                                                                           |
| `messages`         | `array\|null`  | `null`        | Native labels/errors. Supported keys: `idle`, `idleMultiple`, `hint`, `button`, `uploading`, `uploaded`, `uploadFailed`, `clearAll`, `cleared`, `removed`, `removeFile`, `retry`, `fileTooBig`, `invalidFileType`, `maxFilesExceeded`. |
| `controller`       | `string`       | `file-upload` | Stimulus identifier for subclassing.                                                                                                                                                        |
| `class`            | `string`       | `''`          | Merged on the root.                                                                                                                                                                         |

Any other attributes pass to the root except internal `data-{identifier}-*` values, which are owned by props.

## Single File

```blade
<hw:file-upload name="avatar" url="{{ route('uploads.store') }}" accept="image/*" />
```

```php
Route::post('/uploads', function (Request $request) {
    $file = $request->validate(['file' => ['required', 'image', 'max:2048']])['file'];

    return response()->json(['token' => $file->store('temp-uploads')]);
})->name('uploads.store');
```

## Multiple Files

```blade
<hw:file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    :delete-url="route('uploads.destroy', ':token')"
    multiple
    :max-files="5"
    :max-size-bytes="10 * 1024 * 1024"
    accept=".pdf,.csv,image/*"
/>
```

Hidden inputs render as `attachments[]` per successful file.

Selecting the same file more than once while it is already queued/uploading/done is ignored.

Multiple uploads render a Clear all action by default. It aborts active uploads, removes queued/errored cards, removes hidden
inputs, clears preserved `value`/`old()` tokens and calls `delete-url` for completed remote uploads. Bulk remote deletes
are capped by `parallel-uploads` so clearing a large list does not fan out unlimited DELETE requests.

Clear all emits one aggregate `file-upload:cleared` event and does not emit `file-upload:removed` for every item.

## Compact And Grid Views

Use `density="compact"` when the large drop area competes with surrounding form content:

```blade
<hw:file-upload name="attachments" url="{{ route('uploads.store') }}" multiple density="compact" />
```

Use `view="grid"` for media-heavy uploaders. Image files get a temporary local thumbnail via `URL.createObjectURL`; other
files keep the generic attachment icon. Object URLs are revoked when an item is removed or the controller disconnects.

```blade
<hw:file-upload
    name="photos"
    url="{{ route('uploads.store') }}"
    accept="image/*,application/pdf"
    multiple
    view="grid"
/>
```

Failed `5xx`/network uploads expose a retry action on the card. Validation-style failures (`422`) and file-size failures
(`413`) stay non-retryable so users fix the input instead of resubmitting the same rejected file.

## Edit Forms

`value` pre-populates hidden inputs for existing files. `old($name)` wins after validation redirect-back.

```blade
<hw:file-upload
    name="avatar_token"
    url="{{ route('uploads.store') }}"
    :value="$user->avatar_token"
    accept="image/*"
/>
```

Single mode replaces preserved hidden inputs when a new upload succeeds. Multiple mode keeps preserved inputs and appends
new ones.

## Turbo Streams

Use `turbo-stream` when the server renders the visible attachment/card.

```blade
<hw:file-upload
    name="photos"
    url="{{ route('photos.upload') }}"
    accept="image/*"
    multiple
    turbo-stream
    :preview="false"
    :emit-hidden="false"
/>

<ul id="photo-gallery"></ul>
```

On success or error, a response with an actual `<turbo-stream>` element is passed to `Turbo.renderStreamMessage`. On
stream success, the automatic hidden input is skipped because the server-rendered card should carry it.

## Internal File Input

The native file input uses `name="file"` by default, or your `param-name`, so `file-preserve` and `reset-files` can key
off a normal field name when you deliberately stack those controllers. It is assigned to a non-existent form owner, so
the final form submits hidden upload tokens instead of the selected binary file.

By default the controller clears the file input after selection, which lets the same file be selected again. When
`file-preserve` or `reset-files` is stacked on the same root, the selected value is preserved for those controllers.

`required` is semantic on the uploader root (`aria-required`) rather than native file-input validation, because the file
input is intentionally isolated from the final form. Always enforce required uploads server-side.

## Accessibility

- The dropzone is a real keyboard target with `role="button"`, `tabindex="0"`, Enter and Space activation.
- The hidden file input receives native `id`, `name`, `accept` and `multiple` attributes.
- The attachment container is a `role="list"`; generated attachment cards are `role="listitem"`.
- Errored attachment cards set `aria-invalid="true"` and expose the error description as `role="alert"`.
- An `aria-live="polite"` status region announces upload start, success, failure and removal.
- Progress ticks are not announced to avoid screen-reader noise.

Override the dropzone label with `aria-label`:

```blade
<hw:file-upload url="..." aria-label="Attach signed contract" />
```

## Styling Hooks

- `data-slot="file-upload"`
- `data-slot="file-upload-dropzone"`
- `data-slot="file-upload-actions"`
- `data-slot="file-upload-announcer"`
- `data-density="default|compact"`
- `data-view="list|grid"`
- `data-dragging="true|false"`
- `data-slot="attachment-group"`
- `data-slot="attachment"`
- `data-state="idle|uploading|processing|error|done"`
- `data-slot="attachment-media"`
- `data-file-upload-name`
- `data-file-upload-description`
- `data-file-upload-progress`
- `data-file-upload-clear`
- `data-file-upload-retry`
- `data-file-upload-remove`

The attachment cards use the [`Attachment`](attachment.md) primitive and the package [`Progress`](progress.md) styles.

## Breaking Changes From The Dropzone Wrapper

The uploader is native. The removed Dropzone-specific APIs are not supported:

- `options`
- `preview_template`
- `.dropzone`, `.dz-*` styling hooks
- Dropzone `dict*` message names

Use explicit props, native `messages` keys and `Attachment` styling hooks instead.

## See Also

- [`file-upload` controller](../controllers/file-upload.md)
- [`Attachment`](attachment.md)
- [`File upload patterns`](../recipes/file-upload-patterns.md)
