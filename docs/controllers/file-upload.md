# File Upload

Native upload controller for `<hw:file-upload>`. It owns file selection, drag/drop, validation, queueing, XHR upload
progress, hidden input lifecycle, optional DELETE-on-remove and Turbo Stream responses.

**Identifier:** `file-upload`  
**Install:** controllers auto-load after `php artisan hotwire:install`; publish only when customising with `php artisan hotwire:controllers file-upload`.
**npm deps:** none

## Values

| Value             | Type    | Default  | Description                                                                                 |
|-------------------|---------|----------|---------------------------------------------------------------------------------------------|
| `url`             | String  | required | Upload endpoint.                                                                            |
| `hiddenName`      | String  | `""`     | Hidden input name appended on success.                                                      |
| `accept`          | String  | `""`     | Native accept list, also checked client-side.                                               |
| `maxSizeBytes`    | Number  | `0`      | Per-file client-side size limit. `0` disables it.                                           |
| `maxFiles`        | Number  | `0`      | Maximum queued files. `0` disables it.                                                      |
| `multiple`        | Boolean | `false`  | Allows multiple files. Single mode replaces current local items before queueing a new file. |
| `preview`         | Boolean | `true`   | Renders client-side attachment cards when true.                                             |
| `emitHidden`      | Boolean | `true`   | Appends a hidden input on success when a value is extracted.                                |
| `paramName`       | String  | `file`   | Multipart field name.                                                                       |
| `responseKey`     | String  | `token`  | JSON key used as the hidden input value.                                                    |
| `deleteUrl`       | String  | `""`     | DELETE endpoint with one or more `:token` placeholders.                                     |
| `parallelUploads` | Number  | `3`      | Concurrent native XHR uploads.                                                              |
| `turboStream`     | Boolean | `false`  | Sends Turbo Stream Accept header and renders stream bodies.                                 |
| `view`            | String  | `list`   | `list` or `grid`. Grid marks generated cards vertical and enables image thumbnails.         |
| `messages`        | Object  | `{}`     | Native labels and validation messages.                                                      |

## Targets

| Target      | Required                | Description                                  |
|-------------|-------------------------|----------------------------------------------|
| `input`     | yes                     | Hidden native `<input type="file">`.         |
| `dropzone`  | yes                     | Keyboard/click/drag-drop activation surface. |
| `list`      | yes                     | Attachment list container.                   |
| `template`  | yes when `preview=true` | Attachment card template cloned per file.    |
| `announcer` | optional                | `aria-live` status region.                   |

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

| Event                  | Detail                         | Fires when                                                                                        |
|------------------------|--------------------------------|---------------------------------------------------------------------------------------------------|
| `file-upload:ready`    | `{}`                           | Controller connects.                                                                              |
| `file-upload:added`    | `{ file }`                     | A file enters the queue.                                                                          |
| `file-upload:progress` | `{ file, percent, bytes }`     | Native XHR upload progress updates.                                                               |
| `file-upload:success`  | `{ file, response, value }`    | Upload returns 2xx. `value` is extracted from `responseKey`; stream success uses `null`.          |
| `file-upload:retry`    | `{ file }`                     | A retryable failed upload is queued again.                                                        |
| `file-upload:error`    | `{ file, message, xhr, text }` | Client validation fails, network fails or server returns non-2xx.                                 |
| `file-upload:removed`  | `{ file }`                     | User removes a single attachment.                                                                 |
| `file-upload:cleared`  | `{ files, count }`             | User clears all current attachments; this is aggregate and does not emit per-item removed events. |

Event names follow the controller identifier when subclassed.

## Response Handling

JSON responses are parsed automatically. Plain strings are treated as the value. Laravel validation JSON uses the first
field error as the user-facing message:

```json
{ "errors": { "file": ["The file must be an image."] } }
```

When `turboStream` is true, string responses are parsed and only bodies with an actual `<turbo-stream>` element are
passed to `Turbo.renderStreamMessage` on success and error.

For non-JSON failures, `413 Payload Too Large` uses the `fileTooBig` message and HTML error pages fall back to the
generic `uploadFailed` message instead of rendering the full response body in the attachment card.

Network errors (`status === 0`) and `5xx` failures are retryable while the page is alive because the original `File` stays in memory on
the failed item. Validation failures such as `422`, file-size failures such as `413`, and client-side validation errors
do not expose retry.

When generated image attachments are previewed, the controller creates local object URLs and revokes them when an item is
removed or when `disconnect()` runs.

Clear all also removes preserved hidden tokens rendered from `value`/`old()` and announces the number of cleared entries.
Remote DELETE cleanup for completed uploads is capped by `parallelUploads`.

Malformed JSON-like responses are not treated as upload tokens, so they do not append hidden inputs. In `multiple` mode,
selecting a file that is already queued, uploading or done is ignored.

## CSRF

The controller reads `<meta name="csrf-token">` and sends `X-CSRF-TOKEN` on upload and DELETE requests when present.

## Cleanup

`disconnect()` aborts in-flight native XHR uploads and ignores any late XHR callbacks, so removed or disconnected
uploads cannot append hidden inputs later. On reconnect, completed cards already in the DOM are hydrated before new IDs
are assigned, which avoids ID collisions across Turbo morphs.

## See Also

- [`<hw:file-upload>`](../components/file-upload.md)
- [`Attachment`](../components/attachment.md)
