# File Upload

Native drag-and-drop upload with attachment list/grid views, single-image replacement, progress, optional hidden inputs,
DELETE-on-remove and Turbo Stream response support. The upload endpoint, validation, storage and cleanup stay app-side.

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

| Prop               | Type           | Default       | Description                                                                                                    |
|--------------------|----------------|---------------|----------------------------------------------------------------------------------------------------------------|
| `url`              | `string`       | required      | Endpoint that accepts each upload request.                                                                     |
| `name`             | `string\|null` | `null`        | Hidden input name. With `multiple`, `[]` is appended automatically.                                            |
| `value`            | `mixed`        | `null`        | Initial token(s). Overridden by `old($name)` after validation redirect-back.                                   |
| `id`               | `string\|null` | derived       | Root id. Falls back to `hw-file-upload-{uniqid}` without a name.                                               |
| `error-key`        | `string\|null` | derived       | Validation key override.                                                                                       |
| `accept`           | `string\|null` | `null`        | Native accept list (`image/*`, `.pdf,.csv`). Defaults to `image/*` for `view="image"`.                         |
| `max-size-bytes`   | `int\|null`    | `null`        | Per-file client-side size limit. Server validation is still required.                                          |
| `max-files`        | `int\|null`    | `null`        | Maximum queued files.                                                                                          |
| `multiple`         | `bool`         | `false`       | Allows several files and accumulates hidden inputs.                                                            |
| `preview`          | `bool\|null`   | contextual    | Defaults true for JSON and false for raw Turbo Stream responses.                                               |
| `emit-hidden`      | `bool\|null`   | contextual    | Defaults true for JSON and false for raw Turbo Stream responses.                                               |
| `turbo-stream`     | `bool`         | `false`       | Negotiates a raw Turbo Stream body and makes the server own rendering/value output.                            |
| `param-name`       | `string`       | `file`        | Multipart field name for each upload request.                                                                  |
| `response-key`     | `string`       | `token`       | JSON key used for the hidden input value.                                                                      |
| `preview-url-key`  | `string`       | `preview_url` | Optional JSON key containing a durable image URL for `view="image"`.                                          |
| `delete-url`       | `string\|null` | `null`        | DELETE endpoint used when removing an uploaded file. Every `:token` placeholder is URI-encoded.                |
| `parallel-uploads` | `int`          | `3`           | Concurrent upload count.                                                                                       |
| `clearable`        | `bool\|null`   | automatic     | Renders a Clear all action. Defaults to true for `multiple` unless both `preview` and `emit-hidden` are false. |
| `density`          | `string`       | `default`     | Drop area density: `default` or `compact`.                                                                     |
| `view`             | `string`       | `list`        | Client presentation: `list`, `grid` or single-file `image`.                                                    |
| `dropzone-variant` | `string`       | `auto`        | `auto`, `default` or `bare`. Custom slots resolve `auto` to `bare`; explicit `bare` requires the named slot.  |
| `messages`         | `array\|null`  | `null`        | Native labels/errors. See [Messages](#messages) for supported keys.                                            |
| `controller`       | `string`       | `file-upload` | Stimulus identifier for subclassing.                                                                           |
| `class`            | `string`       | `''`          | Merged on the root.                                                                                            |

Any other attributes pass to the root except internal `data-{identifier}-*` values, which are owned by props.

## Messages

Pass only the keys you want to override. Unknown keys throw an `InvalidArgumentException` so typos fail before render.

```blade
<hw:file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    multiple
    accept="image/*,.pdf"
    :messages="[
        'idleMultiple' => 'Drop your files',
        'hint' => 'PDF or image files only',
        'uploadFailed' => 'Could not upload this file',
    ]"
/>
```

| Key                | Default message                                                                    | Used for                                                                       |
|--------------------|------------------------------------------------------------------------------------|--------------------------------------------------------------------------------|
| `idle`             | `Choose files`                                                                     | Dropzone title/label for single-file uploads when `button` is not set.         |
| `idleMultiple`     | `Choose files`                                                                     | Dropzone title/label for multiple uploads when `button` is not set.            |
| `hint`             | `Drop a file here or click to choose` / `Drop files here or click to choose`       | Dropzone description; the plural default is used with `multiple`.              |
| `button`           | `Choose files`                                                                     | Explicit dropzone title/label; takes precedence over `idle` values.            |
| `uploading`        | `Uploading`                                                                        | Live announcement when a file starts uploading or is retried.                  |
| `uploaded`         | `Uploaded`                                                                         | Successful card description prefix and live announcement.                      |
| `uploadFailed`     | `Upload failed`                                                                    | Generic error fallback and live announcement prefix.                           |
| `serverRejected`   | `The server rejected this file. Check the file type and server upload-size limit.` | Unexpected `2xx` full-document fallback when no structured error is available. |
| `clearAll`         | `Clear all`                                                                        | Clear action label.                                                            |
| `cleared`          | `Cleared files`                                                                    | Live announcement after clearing current attachments.                          |
| `removed`          | `Removed`                                                                          | Live announcement after removing one attachment.                               |
| `removeFile`       | `Remove`                                                                           | Per-file remove action `aria-label` prefix.                                    |
| `deleteFailed`     | `Failed to remove file`                                                            | Visible feedback and event text when remote cleanup fails.                     |
| `retry`            | `Retry upload`                                                                     | Per-file retry action `aria-label` prefix.                                     |
| `fileTooBig`       | `File is too large`                                                                | Client size validation and `413 Payload Too Large` fallback.                   |
| `invalidFileType`  | `File type is not allowed`                                                         | Client accept-list validation.                                                 |
| `maxFilesExceeded` | `Maximum number of files reached`                                                  | Client max-file-count validation.                                              |

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

In multiple mode, selecting the same file more than once while it is already queued/uploading/done is ignored.

Multiple uploads render a Clear all action by default. It aborts active uploads, removes queued/errored cards, removes
hidden inputs, clears preserved `value`/`old()` tokens and calls `delete-url` for completed remote uploads. Bulk remote
deletes are capped by `parallel-uploads` so clearing a large list does not fan out unlimited DELETE requests.

Clear all emits one aggregate `file-upload:cleared` event and does not emit `file-upload:removed` for every item.

## Compact And Grid Views

Use `density="compact"` when the large drop area competes with surrounding form content:

```blade
<hw:file-upload name="attachments" url="{{ route('uploads.store') }}" multiple density="compact" />
```

Use `view="grid"` for media-heavy uploaders. Image files get a temporary local thumbnail via `URL.createObjectURL`;
other files keep the generic attachment icon. Object URLs are revoked when an item is removed or the controller
disconnects.

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

## Image View

Use `view="image"` for avatar, banner, logo and other browser-renderable image replacement. It defaults to
`accept="image/*"`, supports one file, omits Attachment markup and previews a valid selection immediately:

```blade
<hw:file-upload
    name="avatar_token"
    :value="$user->avatar_token"
    url="{{ route('uploads.store') }}"
    view="image"
>
    <x-slot:dropzone
        class="rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        aria-label="Change profile picture"
    >
        <hw:avatar
            :src="$user->avatar_url"
            :name="$user->name"
            size="lg"
        />
    </x-slot:dropzone>
</hw:file-upload>
```

Named dropzones resolve `dropzone-variant="auto"` to `bare`. The package keeps only a content-sized preview surface and the
interactive cursor; it does not impose dimensions, aspect ratio, border, background, hover, radius, padding, colors,
clipping or state opacity. The Avatar therefore determines the surface size. The selected preview is positioned
absolutely over that surface, so it cannot create another row or expand the uploader. The slot radius lets the preview
inherit the same shape without requiring `overflow-hidden`.

The same structure follows a horizontal or responsive banner:

```blade
<hw:file-upload
    name="banner_token"
    :value="$user->banner_token"
    url="{{ route('uploads.banner') }}"
    view="image"
>
    <x-slot:dropzone
        class="w-full rounded-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        aria-label="Change profile banner"
    >
        <img
            src="{{ $user->banner_url }}"
            alt=""
            class="aspect-[3/1] w-full rounded-xl object-cover"
        >
    </x-slot:dropzone>
</hw:file-upload>
```

Here the slot supplies the full width and its image supplies the `3:1` aspect ratio. The temporary and durable previews
fill that resulting surface with `object-cover`; no square or circular assumption is built into `view="image"`.

The selected image is displayed through `URL.createObjectURL` while the request runs. A failed upload revokes that URL
and restores the last confirmed image. Single-file token replacement remains transactional: the previous hidden value
is removed only after a usable success response.

Return `preview_url` with the token to replace the temporary blob with a durable URL:

```php
$path = $request->file('file')->store('temporary-avatars');

return response()->json([
    'token' => $path,
    'preview_url' => Storage::url($path),
], 201);
```

The controller preloads the durable image before swapping it into the dropzone. If it cannot load, the valid local blob
stays visible. Change the response key with `preview-url-key="cdn_url"` when needed.

Without a durable URL, the local preview lasts for the current page session and returns to the server-rendered slot
before Turbo caches the page. Set `:preview="false"` when a Turbo Stream or app controller owns all visible rendering.

`view="image"` rejects `multiple` and explicit `clearable=true`. Non-image replacements should use `list`, `grid` or a
server-rendered Turbo Stream composition.

## Custom Dropzone

Use the named `dropzone` slot to replace the default picker content while retaining the package-owned native input,
keyboard support, drag/drop actions, upload queue and response handling:

```blade
<hw:file-upload
    name="document_token"
    url="{{ route('uploads.store') }}"
>
    <x-slot:dropzone
        class="rounded-xl border p-4"
        aria-label="Choose a document"
    >
        Choose a document
    </x-slot:dropzone>
</hw:file-upload>
```

`dropzone-variant="auto"` resolves to `default` without a named slot and `bare` with one. This makes the standard Empty
State a complete styled picker while custom content owns its presentation. Set `dropzone-variant="default"` when the slot
only replaces the copy or icon and should keep the package visual shell, hover, focus and invalid states:

```blade
<hw:file-upload url="{{ route('uploads.store') }}" dropzone-variant="default">
    <x-slot:dropzone>Drop the signed contract here</x-slot:dropzone>
</hw:file-upload>
```

Set `dropzone-variant="bare"` explicitly on a custom slot when you prefer not to rely on the automatic resolution. Bare
surfaces retain their semantic attributes and publish `data-dragging`, `data-loading`, `data-upload-state` and
`aria-invalid`; style those hooks on the slot when custom drag, loading, focus or error treatment is required.

Slot classes, ARIA attributes and `data-action` tokens merge onto the interactive dropzone. Custom `aria-describedby`
IDs are appended to the component's validation ID. The component preserves required validation ARIA, `role="button"`,
`tabindex="0"`, `data-slot` and its internal Stimulus target, so slot attributes cannot replace them. A custom
`data-action` augments rather than replaces picker, keyboard and drag/drop actions.

The component renders a sibling `data-slot="file-upload-feedback"` element for custom dropzones. In list and grid views
it is hidden while idle, then automatically shows upload, success and error messages. Image view reserves the visible
feedback line for errors; use the root state attributes for custom uploading/success treatment. The native input remains
package-owned; attachment list/template markup is included only when `preview=true`.

Outside `view="image"`, the custom slot controls presentation only. Use `file-upload:success` or a Turbo Stream when
app-owned markup should react to the upload.

The root exposes stable lifecycle attributes for custom styling and integrations:

| Attribute           | Values                                | Meaning                                                                                  |
|---------------------|---------------------------------------|------------------------------------------------------------------------------------------|
| `data-loading`      | `true`, `false`                       | True while at least one file is queued or uploading.                                     |
| `data-upload-state` | `idle`, `uploading`, `error`, `done` | Aggregate lifecycle. Errors take precedence over pending and completed upload states.    |

When no server-rendered validation error remains, clearing the uploader returns it to `idle` and hides custom feedback.
`turbo:before-cache` also removes transient uploads and resets custom feedback so cached markup does not restore a stale
loading state.

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

Single mode keeps the previous completed upload while a replacement is validating or uploading, then replaces it only
after the new upload succeeds. Multiple mode keeps preserved inputs and appends new ones.

## Hybrid JSON

Any JSON response can include an optional `stream` string alongside its normal token and image URL. This works in every
view and does not require the `turbo-stream` prop:

```php
return response()->json([
    'token' => $path,
    'preview_url' => Storage::url($path),
    'stream' => turbo_stream()
        ->flash('success', 'Upload completed')
        ->render(),
], 201);
```

The controller first commits its normal hidden input, current local preview, state and public event, then passes only the
actual `<turbo-stream>` elements to `Turbo.renderStreamMessage`. Durable `preview_url` promotion may complete afterward
because image loading is asynchronous. The same optional key is processed after normalizing a non-2xx error. Invalid
strings and non-string values are ignored. Call `render()` or `toHtml()` on the stream builder before placing it in JSON;
serializing the builder object does not produce the expected HTML.

## Raw Turbo Streams

Use `turbo-stream` when the endpoint returns a raw Turbo Stream body and the server renders the visible attachment/card
and any form value:

```blade
<hw:file-upload
    name="photos"
    url="{{ route('photos.upload') }}"
    accept="image/*"
    multiple
    turbo-stream
/>

<ul id="photo-gallery"></ul>
```

`preview` and `emit-hidden` default to false in this protocol, so no client Attachment list/template or automatic hidden
input is produced. Explicit `preview=true` or `emit-hidden=true` is rejected because it would mix client and server
ownership. On success or error, a body with an actual `<turbo-stream>` element is passed to
`Turbo.renderStreamMessage`; the server-rendered output must carry any value needed by a later form submission.
An explicit `value` or matching `old()` value still renders its preserved hidden input for edit and validation
round-trips; `emit-hidden=false` only prevents the upload response from creating a new one.

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
- Generated image previews are decorative; the dropzone's accessible label describes the replacement action.
- The focusable dropzone references its live feedback with `aria-describedby`, so runtime errors remain available after
  refocus.
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
- `data-file-upload-dropzone-variant="default|bare"`
- `data-slot="file-upload-feedback"`
- `data-slot="file-upload-actions"`
- `data-slot="file-upload-announcer"`
- `data-density="default|compact"`
- `data-view="list|grid|image"`
- `data-dragging="true|false"`
- `data-loading="true|false"`
- `data-upload-state="idle|uploading|error|done"`
- `data-slot="attachment-group"`
- `data-slot="file-upload-image-base"`
- `data-slot="file-upload-image-preview"`
- `data-slot="attachment"`
- `data-state="idle|uploading|processing|error|done"`
- `data-slot="attachment-media"`
- `data-file-upload-name`
- `data-file-upload-description`
- `data-file-upload-progress`
- `data-file-upload-clear`
- `data-file-upload-retry`
- `data-file-upload-remove`

Outside image view, `:preview="false"` makes upload progress and errors replace the dropzone description so
server-rendered Turbo Stream workflows still have visible feedback. Image view displays the error line and exposes
uploading through `data-loading` and `data-upload-state`. A `413 Payload Too Large` response commonly means PHP's
`upload_max_filesize` or `post_max_size` is lower than `max-size-bytes`; align those server limits with the component prop.

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
