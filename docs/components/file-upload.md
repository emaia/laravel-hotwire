# File upload

Server-rendered Blade wrapper around the [`file-upload`](../controllers/file-upload.md) Stimulus
controller. Renders a `<div>` mounted on the controller with drag-and-drop, multi-file queue,
client-side preview and progress provided by [Dropzone](https://github.com/NicolasCARPi/dropzone)
(the actively maintained 7.x fork). Upload endpoint, validation, storage and cleanup are entirely
**app-side** — the wrapper handles the browser UX and the form integration.

## Quick example

```blade
<x-hwc::form action="{{ route('profile.update') }}" method="put">
    <x-hwc::field name="avatar">
        <x-hwc::label>Profile picture</x-hwc::label>
        <x-hwc::file-upload url="{{ route('uploads.store') }}" accept="image/*" />
        <x-hwc::error />
    </x-hwc::field>

    <button type="submit">Save</button>
</x-hwc::form>
```

The endpoint receives a single `multipart/form-data` request per file under the field name `file`
(override via `param-name`). It must return JSON; the wrapper reads `response.token` by default
(override via `response-key`) and appends `<input type="hidden" name="avatar" value="{token}">` to
the parent form so the next submission carries the reference. See the [recipes](#recipes) for
medialibrary, async thumbnails and Turbo-Stream-rendered cards.

## Props

| Prop               | Type             | Default          | Description                                                                                              |
|--------------------|------------------|------------------|----------------------------------------------------------------------------------------------------------|
| `url`              | `string`         | *(required)*     | Endpoint that accepts a `multipart/form-data` POST per file and returns JSON. The component throws `InvalidArgumentException` when missing |
| `name`             | `string\|null`   | `null`           | Form field name carried in the hidden input. With `multiple`, `[]` is appended automatically. Also drives `id`, `errorKey` and the `aria-describedby` link |
| `id`               | `string\|null`   | `null`           | Overrides the auto-derived id (`FieldKey::toId($name)`). Falls back to `hwc-file-upload-{uniqid}` when name is absent |
| `errorKey`         | `string\|null`   | `null`           | Overrides the auto-derived error key. Useful when validation errors live under a different path than the field name |
| `accept`           | `string\|null`   | `null`           | MIME pattern or extension list (`"image/*"`, `".pdf,.csv"`) — forwarded to Dropzone's `acceptedFiles`     |
| `maxSizeBytes`     | `int\|null`      | `null`           | Per-file size limit. Converted to MB before reaching Dropzone (`maxFilesize`)                            |
| `maxFiles`         | `int\|null`      | `null`           | Maximum number of files the queue accepts                                                                |
| `multiple`         | `bool`           | `false`          | Enables multi-file selection. Hidden input name becomes `name[]`                                         |
| `preview`          | `bool`           | `true`           | When `false`, suppresses Dropzone's preview list (`previewsContainer: false`). Pair with Turbo Streams for server-rendered cards |
| `emitHidden`       | `bool`           | `true`           | When `false`, the controller does not append the hidden input on success — the server-rendered card embeds it instead |
| `paramName`        | `string`         | `'file'`         | Multipart field name used in each XHR — matches `$request->file('file')` server-side                     |
| `responseKey`      | `string`         | `'token'`        | Key read from the JSON response to populate the hidden input value. Use `'uuid'` for Spatie media, `'url'` for direct-to-S3, etc. |
| `deleteUrl`        | `string\|null`   | `null`           | DELETE endpoint hit when a queued file is removed. `:token` is substituted with the extracted value      |
| `parallelUploads`  | `int`            | `3`              | Concurrent XHRs in the queue                                                                             |
| `class`            | `string`         | `''`             | Merged on the wrapper                                                                                    |
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
// routes/web.php
Route::post('/uploads', function (Request $request) {
    $file = $request->validate(['file' => 'required|image|max:2048'])['file'];
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

## Field composition

Like every form input in the package, `file-upload` participates in `<x-hwc::field>` and reads
`name`/`required`/`errorKey` via `@aware`:

```blade
<x-hwc::field name="cover" error-key="media.cover" required>
    <x-hwc::label />
    <x-hwc::file-upload url="{{ route('uploads.store') }}" accept="image/*" />
    <x-hwc::description>PNG, JPG, WebP up to 5MB.</x-hwc::description>
    <x-hwc::error />
</x-hwc::field>
```

Validation errors on `media.cover` (or any sub-key like `media.cover.0` for multi-file rules)
automatically mark the wrapper `aria-invalid="true"` and render the `<x-hwc::error>` block.

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
`<x-hwc::error name="..." />` directly under the file-upload to render the message. For multi-file
rules (`attachments.*`), any sub-key error marks the wrapper invalid.

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
    accept="image/*"
/>
```

```php
// routes/web.php
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

A scheduled job sweeps unclaimed media older than N hours from the `avatars` collection.

### 2. Async thumbnail via broadcast

Heavy thumbnail generation moves to a queued job. The endpoint returns immediately with a
"pending" card so the user keeps moving; when the job finishes, your broadcaster delivers a Turbo
Stream that swaps the pending card for the final thumb. The wrapper itself is unchanged — only the
endpoint and the job differ from recipe 3.

```blade
<x-hwc::file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    multiple
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

### 3. Stream-rendered card (Turbo Streams mode)

When the visual is fully server-owned — branded preview, edit/reorder controls, badges — turn off
the client preview entirely and let the endpoint reply with a Turbo Stream that appends the card:

```blade
<x-hwc::file-upload
    name="photos"
    url="{{ route('photos.store') }}"
    multiple
    accept="image/*"
    :preview="false"
    :emit-hidden="false"
/>

<ul id="photo-list"></ul>
```

```php
Route::post('/photos', function (Request $r) {
    $photo = $r->user()->photos()->create([
        'path' => $r->file('file')->store('photos'),
    ]);

    return turbo_stream()->append('photo-list', view('photos.card', ['photo' => $photo]));
});
```

The endpoint negotiates `Accept` — when the browser asks for `text/vnd.turbo-stream.html` (which
Turbo always does for non-GET requests on a Turbo page), the stream is applied and the card
appears. The card itself owns the hidden input and the remove button:

```blade
{{-- resources/views/photos/card.blade.php --}}
<li id="{{ dom_id($photo) }}">
    <img data-controller="lazy-image" data-lazy-image-src-value="{{ $photo->thumb_url }}" alt="">
    <input type="hidden" name="photos[]" value="{{ $photo->id }}">
    <button
        type="button"
        data-controller="remote-form"
        formaction="{{ route('photos.destroy', $photo) }}"
        formmethod="delete"
    >Remove</button>
</li>
```

`emit-hidden="false"` keeps the wrapper out of the hidden-input business; `preview="false"` keeps
Dropzone's thumbnail DOM out of the picture. Everything visible is server-rendered Blade.

## See also

- [File upload controller](../controllers/file-upload.md) — values, actions, events, subclass hooks
- [`<x-hwc::file>`](file.md) — the simpler input variant for forms that don't need previews or
  progress
