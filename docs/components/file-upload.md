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

The Dropzone library only renders its visible affordance (the dashed border, "Drop files here to
upload" message, thumbnail layout) when:

1. The host `<div>` carries the `dropzone` class — the component does this for you (it always
   emits `class="hwc-file-upload dropzone"`).
2. `@deltablot/dropzone/dist/dropzone.css` is bundled into the page.

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

To customise the visual without touching the package's CSS, override `.hwc-file-upload`, `.dz-*`
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
| `class`            | `string`         | `''`             | Merged on the wrapper (after the baseline `hwc-file-upload dropzone`)                                    |
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

### 1. Spatie Media Library

Pin uploaded files to a temporary collection and claim them on the final submit. The endpoint
returns the media UUID; `response-key="uuid"` writes that into the hidden input.

```blade
<x-hwc::file-upload
    name="avatar_uuid"
    url="{{ route('uploads.store') }}"
    response-key="uuid"
    :delete-url="route('uploads.destroy', ':token')"
    :value="$user->avatar_media_uuid"
    accept="image/*"
/>
```

```php
Route::post('/uploads', function (Request $r) {
    $r->validate(['file' => 'required|image|max:2048']);

    $media = $r->user()
        ->addMedia($r->file('file'))
        ->toMediaCollection('avatars');

    return response()->json(['uuid' => $media->uuid]);
})->middleware(['auth', 'throttle:20,1'])->name('uploads.store');

Route::delete('/uploads/{media}', fn (Media $media) => tap($media)->delete())
    ->middleware('auth')
    ->name('uploads.destroy');

// app/Http/Controllers/ProfileController.php
public function update(UpdateProfileRequest $r)
{
    $media = Media::where('uuid', $r->validated('avatar_uuid'))->firstOrFail();
    $media->forgetCustomProperty('temporary');
    $r->user()->media()->save($media);
}
```

A scheduled job sweeps unclaimed media older than N hours from the `avatars` collection. With
`:value`, edit forms work transparently: the UUID is pre-loaded; a redirect-back from a sibling
field's validation failure keeps the user's avatar selection.

### 2. Async thumbnail via broadcast

Heavy thumbnail generation moves to a queued job. The endpoint returns a Turbo Stream immediately
with a "pending" card; when the job finishes, your broadcaster delivers a second stream that
replaces the pending card with the final thumb.

```blade
<x-hwc::file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    multiple
    :turbo-stream="true"
    :preview="false"
    :emit-hidden="false"
/>

<ul id="attachments"></ul>
```

```php
Route::post('/uploads', function (Request $r) {
    $r->validate(['file' => 'required|file|max:51200']);

    $upload = $r->user()->uploads()->create(['path' => $r->file('file')->store('uploads')]);
    GenerateThumbnail::dispatch($upload);

    return turbo_stream()->append('attachments', view('uploads.card', ['upload' => $upload]));
});
```

```php
// GenerateThumbnail job
public function handle(): void
{
    $thumb = Image::load(Storage::path($this->upload->path))->fit(200, 200)->save();
    $this->upload->update(['thumbnail_path' => $thumb]);

    // Broadcast a Turbo Stream that replaces the pending card. Wire this through your
    // broadcasting setup — Reverb/Pusher with Laravel Echo, Mercure, or any SSE bridge.
    // The payload is a `<turbo-stream action="replace" target="upload-{id}">...</turbo-stream>`
    // string that Turbo applies on receipt.
    broadcast(new UploadProcessed($this->upload));
}
```

```blade
{{-- resources/views/uploads/card.blade.php — same partial for "pending" and "ready" states --}}
<li id="{{ dom_id($upload) }}">
    @if ($upload->thumbnail_path)
        <img data-controller="lazy-image" data-lazy-image-src-value="{{ Storage::url($upload->thumbnail_path) }}" alt="">
    @else
        <x-hwc::spinner /> Processing…
    @endif
    <input type="hidden" name="attachments[]" value="{{ $upload->id }}">
</li>
```

The hidden input lives inside the card from the moment it's appended — the form already has the
reference even while the thumbnail is still rendering. `lazy-image` on the final thumb defers the
GET until the card enters the viewport.

### 3. Stream-rendered gallery with server-side EXIF

User drops images; each upload returns a Turbo Stream that appends a server-rendered `<li>` card
with the thumbnail, file name, and EXIF metadata (camera/date) the server extracted. Removal is
also stream-driven via the package's `remote-form` controller — no app-side glue.

```blade
{{-- gallery.blade.php --}}
<x-hwc::form action="{{ route('gallery.save') }}" method="post">
    <x-hwc::field name="photos" label="Add photos">
        <x-hwc::file-upload
            name="photos"
            url="{{ route('gallery.upload') }}"
            accept="image/*"
            multiple
            :turbo-stream="true"
            :preview="false"
            :emit-hidden="false"
        />
    </x-hwc::field>

    <ul id="photo-gallery" class="gallery"></ul>

    <button type="submit">Save gallery</button>
</x-hwc::form>
```

```php
// routes/web.php
Route::post('/gallery/upload', function (Request $r) {
    $r->validate(['file' => 'required|image|max:10240']);

    $photo = $r->user()->photos()->create([
        'path' => $r->file('file')->store('photos', 'public'),
        'original_name' => $r->file('file')->getClientOriginalName(),
        'exif' => collect(@exif_read_data($r->file('file')->getPathname()))
            ->only(['Make', 'Model', 'DateTimeOriginal'])
            ->filter()
            ->all(),
    ]);

    return turbo_stream()->append('photo-gallery', view('photos.card', ['photo' => $photo]));
})->middleware('auth')->name('gallery.upload');

Route::delete('/gallery/{photo}', function (Photo $photo) {
    $photo->delete();
    return turbo_stream()->remove(dom_id($photo));
})->name('gallery.destroy');
```

```blade
{{-- resources/views/photos/card.blade.php --}}
<li id="{{ dom_id($photo) }}" class="photo-card">
    <img
        data-controller="lazy-image"
        data-lazy-image-src-value="{{ Storage::url($photo->path) }}"
        alt="{{ $photo->original_name }}"
        loading="lazy" width="160" height="160"
    >
    <div class="photo-card__meta">
        <strong>{{ Str::limit($photo->original_name, 24) }}</strong>
        @if ($make = $photo->exif['Make'] ?? null)
            <small>{{ $make }} {{ $photo->exif['Model'] ?? '' }}</small>
        @endif
        @if ($shot = $photo->exif['DateTimeOriginal'] ?? null)
            <small>{{ $shot }}</small>
        @endif
    </div>

    {{-- The card carries its own hidden input — the file-upload component skips emitting one
         because `:emit-hidden="false"`. --}}
    <input type="hidden" name="photos[]" value="{{ $photo->id }}">

    {{-- Stream-driven removal: DELETE returns <turbo-stream action="remove"> which makes the
         <li> (and its hidden) disappear. --}}
    <button type="button"
            data-controller="remote-form"
            formaction="{{ route('gallery.destroy', $photo) }}"
            formmethod="delete"
            aria-label="Remove {{ $photo->original_name }}">×</button>
</li>
```

End-to-end flow:

1. User drops 3 photos → Dropzone fires 3 parallel XHRs (capped by `parallelUploads`).
2. Each XHR carries `Accept: text/vnd.turbo-stream.html, application/json`.
3. Server validates, persists, reads EXIF, creates the DB record. Responds with a Turbo Stream
   appending the card into `#photo-gallery`.
4. Controller detects the stream body and calls `Turbo.renderStreamMessage`. The `<li>` with
   `<img>` + EXIF + hidden + remove button slides into the page.
5. `lazy-image` defers the GET of each thumb until the card enters the viewport — a 20-photo
   batch doesn't fire 20 image GETs at once.
6. Parent form submit collects every `photos[]` hidden carried by every card → app persists the
   final association.
7. Per-card remove buttons fire DELETE via `remote-form`; server responds with another stream
   (`<turbo-stream action="remove" target="photo_42">`) → card and its hidden disappear.

Compose with `lazy-image`, `remote-form`, `confirm-dialog`, and any other Stimulus controller you
need on the card partial. Zero JS glue beyond what the package already ships.

## See also

- [File upload controller](../controllers/file-upload.md) — values, actions, events, subclass hooks
- [`<x-hwc::file>`](file.md) — the simpler input variant for forms that don't need previews or
  progress
