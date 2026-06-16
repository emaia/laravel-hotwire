# File upload

Wrapper around [Dropzone](https://github.com/NicolasCARPi/dropzone) 7.x (the actively maintained
`@deltablot/dropzone` fork). Instantiates Dropzone on the controller element, wires the 5 lifecycle
events into Stimulus dispatches, optionally appends a hidden input on success, fires DELETE on
removal, writes upload progress to an `aria-live` status region and exposes `defaultOptions()` /
`afterInit()` hooks for subclasses.

**Identifier:** `file-upload`  
**Install:** `php artisan hotwire:controllers file-upload`  
**npm dep:** `@deltablot/dropzone ^7.4.0`

## Requirements

- `@deltablot/dropzone ^7.4.0` (`hotwire:check` reports it as required when the controller is in use)

The upstream `dropzone` package on npm is stuck at a 2021 beta — this controller wraps the
maintained fork. The fork's API is API-compatible for everything the wrapper uses
(`acceptedFiles`, `maxFilesize`, `maxFiles`, `paramName`, `parallelUploads`, `uploadMultiple`,
`previewsContainer`, `headers`, plus the `addedfile` / `uploadprogress` / `success` / `error` /
`removedfile` events).

## Values

| Value             | Type    | Default       | Description                                                                                              |
|-------------------|---------|---------------|----------------------------------------------------------------------------------------------------------|
| `url`             | String  | *(required)*  | Endpoint that accepts a `multipart/form-data` POST per file and returns JSON                              |
| `hiddenName`      | String  | `""`          | `name` attribute used when appending the hidden input on success. The Blade component computes this from `name` (+ `[]` when `multiple`); when wiring the controller manually, set it explicitly |
| `accept`          | String  | `""`          | MIME pattern or extension list (`"image/*"`, `".pdf,.csv"`) — forwarded to Dropzone's `acceptedFiles`     |
| `maxSizeBytes`    | Number  | `0`           | Per-file size limit. Converted to MB before reaching Dropzone (`maxFilesize`). `0` disables the limit    |
| `maxFiles`        | Number  | `0`           | Maximum number of files the queue accepts. `0` disables the cap                                          |
| `multiple`        | Boolean | `false`       | Multi-file selection. Mirrored from the Blade component; not consumed directly by the controller         |
| `preview`         | Boolean | `true`        | When `false`, Dropzone runs with `previewsContainer: false` — no client-side preview list                 |
| `emitHidden`      | Boolean | `true`        | When `false`, the controller does not append a hidden input on success                                   |
| `paramName`       | String  | `"file"`      | Multipart field name used in each XHR                                                                    |
| `responseKey`     | String  | `"token"`     | Key read from the JSON response to extract the value written into the hidden input                        |
| `deleteUrl`       | String  | `""`          | DELETE endpoint hit when a queued file is removed. `:token` is substituted with the extracted value      |
| `parallelUploads` | Number  | `3`           | Concurrent XHRs in the queue                                                                             |
| `turboStream`     | Boolean | `false`       | When `true`, sends `Accept: text/vnd.turbo-stream.html, application/json` on every upload XHR; on success/error, if the response body looks like a Turbo Stream, hands it to `Turbo.renderStreamMessage` and skips the automatic hidden input on success |
| `options`         | Object  | `{}`          | Extra Dropzone options, JSON-encoded into the value. Spread over the wrapper's per-prop defaults; subclass `defaultOptions()` still takes precedence. Use for `dict*` localization, `thumbnailMethod`, `resizeQuality`, etc. The Blade component populates this from the `:options` + `:messages` props |

## Targets

| Target      | Required | Description                                                                                  |
|-------------|----------|----------------------------------------------------------------------------------------------|
| `announcer` | optional | `aria-live="polite"` region. Controller writes upload milestones to its `textContent`        |

## Actions

| Action       | Description                                                                                  |
|--------------|----------------------------------------------------------------------------------------------|
| `openPicker` | Calls `preventDefault()` on the triggering event (so Space does not scroll) and clicks Dropzone's hidden file input. Wired via `data-action="keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker"` |

## Events

| Event                  | Detail                          | Fires when                                                                          |
|------------------------|---------------------------------|-------------------------------------------------------------------------------------|
| `file-upload:ready`    | `{}`                            | Dropzone is instantiated and event handlers are wired                                |
| `file-upload:added`    | `{ file }`                      | A file is added to the queue (drag-drop, picker or programmatic)                     |
| `file-upload:progress` | `{ file, percent, bytes }`      | XHR upload progress tick                                                             |
| `file-upload:success`  | `{ file, response, value }`     | Endpoint returned 2xx. `value` is the extracted result of `responseKey` lookup. `null` when a Turbo Stream was rendered (the server-rendered card owns the hidden input) |
| `file-upload:error`    | `{ file, message, xhr, text }`  | Endpoint returned non-2xx or network error. `text` is a normalised user-facing string (handles Laravel's `{ message }` and `{ errors: { field: [...] } }` JSON shapes; falls back to `"Upload failed"`) |
| `file-upload:removed`  | `{ file }`                      | File is removed from the queue (UI button, programmatic `removeFile`, or abort)      |

Event names use the controller identifier. When subclassed via `controller="my-upload"`, the
dispatched names become `my-upload:added`, etc.

## Basic usage (raw, without the Blade component)

```html
<div data-controller="file-upload"
     data-action="keydown.enter->file-upload#openPicker keydown.space->file-upload#openPicker"
     data-file-upload-url-value="/uploads"
     data-file-upload-hidden-name-value="avatar"
     tabindex="0"
     role="button"
     aria-label="Choose files">
    <div role="status" aria-live="polite" data-file-upload-target="announcer"></div>
</div>
```

The `<x-hwc::file-upload>` Blade component handles the boilerplate (id/errorKey derivation,
keyboard wiring, announcer, attribute filtering); reach for raw HTML only when the component's
props are too restrictive.

## Value extraction (`responseKey`)

On `success`, the controller calls `extractValue(response)`:

- `null` → `null` (no hidden appended)
- plain `string` → used as-is
- object → returns `response[responseKey]` or `null` if missing

Three patterns that flow naturally:

```js
// 1. Plain token endpoint — default
{ "token": "01HQVZ…" }                             // responseKey="token"

// 2. Spatie media library — UUID is the canonical reference
{ "uuid": "9b…" }                                  // responseKey="uuid"

// 3. Direct S3 (presigned URL upload) — public URL is what the form persists
{ "url": "https://cdn.example.com/uploads/…" }     // responseKey="url"
```

## Hidden input append / remove

On `success`, when `emitHidden` is true and the extracted value is non-null, the controller
appends `<input type="hidden" name="{hiddenName}" value="{value}" data-hw-upload>` to the
controller element. The input is keyed to the file via a `WeakMap`, so on `removedfile` the right
input is removed without needing identifiers in the DOM.

When `hiddenName` is empty the append is skipped — useful when the server-rendered card embeds its
own hidden input (see the [stream-rendered recipe](../components/file-upload.md#3-stream-rendered-card-turbo-streams-mode)).

## DELETE on remove

When `deleteUrl` is set and the removed file has a recorded value, the controller fires:

```
DELETE {deleteUrl with :token substituted}
  X-CSRF-TOKEN: {value from <meta name="csrf-token">}
```

The placeholder `:token` is URI-encoded. Failures are logged to `console.error` and do not block
the rest of the flow — the hidden input is removed regardless. When `deleteUrl` is empty, removal
is local only (the queue drops the file and the hidden input goes away, the server is never told).

## Announcer messages

When the `announcer` target is present, the controller writes:

| Event        | Message               |
|--------------|-----------------------|
| `addedfile`  | `Uploading {name}`    |
| `success`    | `Uploaded {name}`     |
| `error`      | `Upload failed: {msg}` |
| `removedfile`| `Removed {name}`      |

Per-tick `uploadprogress` is intentionally not announced — screen readers would read it on every
update, which is noise. To override the messages, subclass and override `announce(message)` or
override the individual event handlers via `defaultOptions()`/`afterInit()`.

## CSRF

The controller reads `<meta name="csrf-token">` at construction time and forwards the token to
Dropzone via the `headers` option (`X-CSRF-TOKEN`). The same header is sent on the DELETE request.
Apps without the meta tag (public forms behind explicit middleware overrides) skip the header.

## Extending via subclass

The `defaultOptions()` and `afterInit()` hooks mirror the chart/map pattern:

```js
// resources/js/controllers/medialibrary_upload_controller.js
import FileUploadController from "./file_upload_controller.js";

export default class extends FileUploadController {
    defaultOptions() {
        return {
            // Spatie Media Library's preview wants the original filename in the UI
            renameFile: (file) => `${Date.now()}-${file.name}`,
            // Custom thumbnail template
            previewTemplate: document.querySelector("#media-preview-template").innerHTML,
        };
    }

    afterInit() {
        // Custom Dropzone events not covered by the base dispatches
        this.dropzone.on("thumbnail", (file, dataUrl) => {
            this.dispatch("thumbnail", { detail: { file, dataUrl } });
        });
    }
}
```

Mount it via the Blade component's `controller=` prop:

```blade
<x-hwc::file-upload controller="medialibrary-upload" name="avatar_uuid" url="..." response-key="uuid" />
```

All `data-*-value` and `data-*-target` attributes follow the new identifier (`data-medialibrary-upload-url-value`, etc.).

## Cleanup

`disconnect()` destroys the Dropzone instance, aborting any in-flight XHRs and removing the
preview DOM. The `WeakMap` keyed by file is dropped along with the controller. Re-connecting (e.g.
after a Turbo morph) starts fresh — the previous queue does not survive.

## See also

- [`<x-hwc::file-upload>`](../components/file-upload.md) — Blade props, field composition, recipes
- [`<x-hwc::file>`](../components/file.md) / [`file-preserve`](file-preserve.md) — the simpler
  input variant for forms that don't need previews or progress
