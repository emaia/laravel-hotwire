# File Upload

Native upload controller for `<hw:file-upload>`. It owns file selection, drag/drop, validation, queueing, XHR upload
progress, hidden input lifecycle, optional DELETE-on-remove and Turbo Stream responses.

**Identifier:** `file-upload`  
**Install:** controllers auto-load after `php artisan hotwire:install`; publish only when customising with
`php artisan hotwire:controllers file-upload`.

**npm deps:** none

## Values

| Value             | Type    | Default       | Description                                                                                 |
|-------------------|---------|---------------|---------------------------------------------------------------------------------------------|
| `url`             | String  | required      | Upload endpoint.                                                                            |
| `hiddenName`      | String  | `""`          | Hidden input name appended on success.                                                      |
| `accept`          | String  | `""`          | Native accept list, also checked client-side.                                               |
| `maxSizeBytes`    | Number  | `0`           | Per-file client-side size limit. `0` disables it.                                           |
| `maxFiles`        | Number  | `0`           | Maximum queued files. `0` disables it.                                                      |
| `multiple`        | Boolean | `false`       | Allows multiple files. Single mode keeps a completed upload until its replacement succeeds. |
| `mode`            | String  | `managed`     | `managed` accepts JSON; `turbo-stream` requires a raw Turbo Stream body.                    |
| `outputMode`      | String  | `full`        | Managed output: `full`, `preview`, `hidden` or `none`. Raw streams always resolve to none.  |
| `paramName`       | String  | `file`        | Multipart field name.                                                                       |
| `responseKey`     | String  | `token`       | JSON key used as the hidden input value.                                                    |
| `previewUrlKey`   | String  | `preview_url` | Optional durable image URL key used by the `image` view.                                    |
| `deleteUrl`       | String  | `""`          | DELETE endpoint with one or more `:token` placeholders.                                     |
| `parallelUploads` | Number  | `3`           | Concurrent native XHR uploads.                                                              |
| `view`            | String  | `list`        | `list`, `grid` or single-file `image`.                                                       |
| `messages`        | Object  | `{}`          | Native labels and validation messages.                                                      |

## Targets

| Target         | Required               | Description                                                       |
|----------------|------------------------|-------------------------------------------------------------------|
| `input`        | yes                    | Hidden native `<input type="file">`.                              |
| `dropzone`     | yes                    | Keyboard/click/drag-drop activation surface.                      |
| `feedback`     | optional               | Visible status for custom dropzones and output modes without preview. |
| `imagePreview` | image view only        | Package-owned local/durable replacement image.                    |
| `list`         | list/grid with preview output | Attachment list container.                                  |
| `template`     | list/grid with preview output | Attachment card template cloned per file.                   |
| `announcer`    | optional               | `aria-live` status region.                                        |

## Root State

The controller keeps two aggregate state attributes on its root:

| Attribute           | Values                                | Description                                                                              |
|---------------------|---------------------------------------|------------------------------------------------------------------------------------------|
| `data-loading`      | `true`, `false`                       | True while at least one item is queued or uploading.                                     |
| `data-upload-state` | `idle`, `uploading`, `error`, `done` | Current lifecycle; runtime or server-rendered errors take precedence over other states.  |

An uploader may have `data-loading="true"` and `data-upload-state="error"` at the same time when one item failed while
another remains active. Before Turbo caches the page, transient uploads are removed, loading is reset and custom
dropzone feedback returns to its initial hidden state.

## Actions

| Action                                 | Description                                                    |
|----------------------------------------|----------------------------------------------------------------|
| `openPicker`                           | Opens the native file picker.                                  |
| `select`                               | Queues files from the native input.                            |
| `dragEnter` / `dragOver` / `dragLeave` | Manage drag state on the root.                                 |
| `drop`                                 | Queues dropped files.                                          |
| `clear`                                | Removes all queued, active, failed and completed upload cards. |
| `retry`                                | Retries a retryable failed upload using the original `File`.   |
| `remove`                               | Aborts or removes an upload and cleans up hidden/remote state. |

## Events

| Event                      | Detail                                   | Fires when                                                                                                 |
|----------------------------|------------------------------------------|------------------------------------------------------------------------------------------------------------|
| `file-upload:ready`        | `{}`                                     | Controller connects.                                                                                       |
| `file-upload:added`        | `{ file }`                               | A file enters the queue.                                                                                   |
| `file-upload:progress`     | `{ file, percent, bytes }`               | Native XHR upload progress updates.                                                                        |
| `file-upload:success`      | `{ file, response, value }`              | Upload returns a usable 2xx response. `value` is extracted from `responseKey`; stream success uses `null`. |
| `file-upload:retry`        | `{ file }`                               | A retryable failed upload is queued again.                                                                 |
| `file-upload:error`        | `{ file, message, xhr, text }`           | Client validation fails, the response lacks a required token, network fails or server returns non-2xx.     |
| `file-upload:delete-error` | `{ error, file, response, text, value }` | A remote DELETE request fails or returns non-2xx.                                                          |
| `file-upload:removed`      | `{ file }`                               | User removes a single attachment.                                                                          |
| `file-upload:cleared`      | `{ files, count }`                       | User clears all current attachments; this is aggregate and does not emit per-item removed events.          |

Event names follow the controller identifier when subclassed.

## Response Handling

JSON responses are parsed automatically. Plain strings are treated as the value. Laravel validation JSON uses the first
field error as the user-facing message:

```json
{
    "errors": {
        "file": [
            "The file must be an image."
        ]
    }
}
```

JSON may also contain a `stream` string. It is validated and rendered automatically in every managed view after the
controller commits success/error state. This lets one response provide a hidden token,
durable image URL and server-driven DOM updates. Only strings containing an actual `<turbo-stream>` element are passed
to `Turbo.renderStreamMessage`.

In `image` view, an optional `preview_url` response value is preloaded before replacing the local object URL. Loading
failure keeps the local blob. Upload failure revokes the candidate blob and restores the last completed preview.
`outputMode="hidden"` disables both local and durable image handling while retaining hidden response tokens.

When `mode="turbo-stream"`, successful responses must be raw strings containing an actual `<turbo-stream>` element;
JSON envelopes are rejected as upload errors. Valid streams are passed to `Turbo.renderStreamMessage` on success and
error. Raw stream mode ignores managed outputs; direct controller markup needs only the mode value.

For non-JSON failures, `413 Payload Too Large` uses the `fileTooBig` message and non-2xx HTML error pages fall back to
`uploadFailed` instead of rendering the full response body in the attachment card.

Full HTML documents are also treated as errors when a redirect turns an upload failure into a final `200` response. They
use the actionable `serverRejected` fallback because this commonly happens when PHP rejects a file before Laravel
validation and the application redirects back with a flash error. Turbo Stream uploads prefer JSON in the `Accept` header
while still advertising `text/vnd.turbo-stream.html`. Laravel therefore returns structured validation errors, and
`wantsTurboStream()` continues to recognize successful stream responses.

Network errors (`status === 0`) and `5xx` failures are retryable while the page is alive because the original `File`
stays in memory on the failed item. Validation failures such as `422`, file-size failures such as `413`, and client-side
validation errors do not expose retry.

When generated image attachments or image replacements are previewed, the controller creates local object URLs and
revokes them when an item is removed or when `disconnect()` runs. Before Turbo caches the page, local previews return to
the generic attachment icon or server-rendered image. Durable image URLs remain and are hydrated on reconnect even
without an attachment list. Interrupted or failed items are removed because their in-memory `File` objects cannot
survive a reconnect.

Clear all also removes preserved hidden tokens rendered from `value`/`old()` and announces the number of cleared
entries. Remote DELETE cleanup for completed uploads is capped by `parallelUploads`. Failed cleanup dispatches
`file-upload:delete-error`; non-2xx responses are failures even when `fetch()` resolves normally.

Malformed JSON-like responses are treated as upload errors rather than tokens, so they do not append hidden inputs. In
`multiple` mode, selecting a file that is already queued, uploading or done is ignored.

## CSRF

The controller reads `<meta name="csrf-token">` and sends `X-CSRF-TOKEN` on upload and DELETE requests when present.

## Cleanup

`disconnect()` aborts in-flight native XHR uploads and ignores any late XHR callbacks, so removed or disconnected
uploads cannot append hidden inputs later. On reconnect, transient cards are discarded and completed cards already in
the DOM are hydrated before new IDs are assigned, which avoids stale uploads and ID collisions across Turbo morphs. A
private upload feedback presenter restores morphed targets and is suspended when disconnected so late status writes are
ignored.

## See Also

- [`<hw:file-upload>`](../components/file-upload.md)
- [`Attachment`](../components/attachment.md)
