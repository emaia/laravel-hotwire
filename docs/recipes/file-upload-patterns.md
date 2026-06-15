# File upload patterns

Four real-world patterns for the [`<x-hwc::file-upload>`](../components/file-upload.md) component
plus the [`file-upload`](../controllers/file-upload.md) Stimulus controller, ordered from simplest
to most stream-driven. Each example assumes the package's defaults — `@deltablot/dropzone` is
installed, `dropzone.css` is bundled, and the upload endpoint lives in your app.

- [1. Spatie Media Library](#1-spatie-media-library)
- [2. Async thumbnail via broadcast](#2-async-thumbnail-via-broadcast)
- [3. Stream-rendered gallery with server-side EXIF](#3-stream-rendered-gallery-with-server-side-exif)
- [4. Single-file edit form with a stream-replaced card (avatar pattern)](#4-single-file-edit-form-with-a-stream-replaced-card-avatar-pattern)

## 1. Spatie Media Library

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

## 2. Async thumbnail via broadcast

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

## 3. Stream-rendered gallery with server-side EXIF

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

## 4. Single-file edit form with a stream-replaced card (avatar pattern)

Single mode + `:turbo-stream="true"` needs slightly different lifecycle than the multi-file
gallery: there can only be **one** hidden carrying the value at a time. Letting both the
component's preserved hidden (from `:value`/`old()`) and the stream-rendered card emit a hidden
would put two `<input name="avatar_token">` in the form — Laravel takes the last one but it's
confusing.

The clean pattern: **the visible card carries the hidden, the file-upload doesn't.** The stream
replaces the whole card on each upload, so there's only ever one hidden in the form. Don't pass
`:value` — the server-rendered card is the source of truth.

```blade
{{-- profile/edit.blade.php --}}
<x-hwc::form action="{{ route('profile.update') }}" method="put">
    {{-- The current state is server-rendered. The hidden lives inside. --}}
    @include('profile.avatar-card', ['user' => $user])

    <x-hwc::field name="avatar_token" label="Change picture">
        <x-hwc::file-upload
            name="avatar_token"
            url="{{ route('profile.avatar.upload') }}"
            accept="image/*"
            :turbo-stream="true"
            :emit-hidden="false"
        />
    </x-hwc::field>

    <button type="submit">Save</button>
</x-hwc::form>
```

```blade
{{-- resources/views/profile/avatar-card.blade.php --}}
<div id="avatar-card" class="avatar-card">
    @if ($user->avatar_path)
        <img src="{{ Storage::url($user->avatar_path) }}" alt="Current avatar" width="120" height="120">
        <input type="hidden" name="avatar_token" value="{{ $user->avatar_token }}">
        <button type="button"
                data-controller="remote-form"
                formaction="{{ route('profile.avatar.destroy') }}"
                formmethod="delete"
                aria-label="Remove avatar">Remove</button>
    @else
        <span class="avatar-card__empty">No avatar yet — drop one below.</span>
    @endif
</div>
```

```php
// routes/web.php
Route::post('/profile/avatar', function (Request $r) {
    $r->validate(['file' => 'required|image|max:2048']);

    $token = $r->file('file')->store('temp-avatars');
    $r->user()->update(['avatar_token' => $token, 'avatar_path' => $token]);

    // Replace the whole card — the old hidden goes with the old <div>, the new hidden ships inside the new <div>.
    return turbo_stream()->replace('avatar-card', view('profile.avatar-card', ['user' => $r->user()]));
})->middleware('auth')->name('profile.avatar.upload');

Route::delete('/profile/avatar', function (Request $r) {
    $r->user()->update(['avatar_token' => null, 'avatar_path' => null]);

    // Re-render the empty state.
    return turbo_stream()->replace('avatar-card', view('profile.avatar-card', ['user' => $r->user()]));
})->middleware('auth')->name('profile.avatar.destroy');
```

End-to-end:

1. User loads the page → server renders `#avatar-card` with the current photo + hidden. The
   file-upload sits next to it as the "change" affordance, no `:value`, no auto-hidden.
2. User drops a new image → stream comes back with the new `#avatar-card` markup. Turbo's
   `replace` swaps the entire `<div>`. The previous hidden and image are gone; the new hidden
   inside the replacement carries forward.
3. On form submit, exactly one `avatar_token` hidden ships in the payload.
4. Clicking "Remove" fires DELETE; server replaces `#avatar-card` with the empty state — no
   hidden in the form, the avatar field submits as null.

The pattern generalises to any single-value resource (cover image, signature, certificate, …)
that you want to manage with stream-driven UX. The two rules:

- **Don't pass `:value`** — the visible card holds the canonical hidden
- **Always `replace`, never `append`** — keeps the markup count at one
