# File upload patterns

Five real-world patterns for the [`<x-hwc::file-upload>`](../components/file-upload.md) component
plus the [`file-upload`](../controllers/file-upload.md) Stimulus controller, ordered from simplest
to most stream-driven. Each example assumes the package's defaults — `@deltablot/dropzone` is
installed, `dropzone.css` is bundled, and the upload endpoint lives in your app.

- [1. Spatie Media Library](#1-spatie-media-library)
- [2. Async thumbnail via broadcast](#2-async-thumbnail-via-broadcast)
- [3. Stream-rendered gallery with server-side EXIF](#3-stream-rendered-gallery-with-server-side-exif)
- [4. Single-file edit form with a stream-replaced card (avatar pattern)](#4-single-file-edit-form-with-a-stream-replaced-card-avatar-pattern)
- [5. Rich media library list with rename and reorder](#5-rich-media-library-list-with-rename-and-reorder)

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

## 5. Rich media library list with rename and reorder

The UI from Dropzone's [Bootstrap demo](https://www.dropzone.dev/bootstrap.html) and Spatie media
library: a vertical list of cards, each with a drag handle, thumbnail, file metadata (type, size,
download link), an **editable name input**, and a remove button. Files can be **dragged to reorder**;
the server receives them as an ordered array with `token` + `name` per entry.

The package already has every primitive needed: `<x-slot:preview_template>` for the card markup,
`:emit-hidden="false"` to let the slot own the hidden inputs, `:messages` for a custom empty state,
and a subclass for the SortableJS + metadata wiring. No new package code required.

### Blade

```blade
<x-hwc::form action="{{ route('gallery.store') }}">
    <x-hwc::field name="attachments" label="Images">
        <x-hwc::file-upload
            controller="media-upload"
            name="attachments"
            url="{{ route('uploads.store') }}"
            multiple
            accept="image/*,application/pdf"
            :emit-hidden="false"
            :max-size-bytes="10 * 1024 * 1024"
            :messages="['default' => '<span class=\'text-2xl mr-2\'>+</span> Drag files or click to set media']"
            class="hwc-media-list"
        >
            <x-slot:preview_template>
                <div class="dz-preview dz-file-preview flex items-center gap-4 p-3 border-b bg-white">
                    <button type="button" data-app-drag class="cursor-move text-gray-400" tabindex="-1" aria-label="Reorder">≡</button>
                    <img data-dz-thumbnail class="w-16 h-16 object-cover rounded bg-gray-100">
                    <div class="w-32 shrink-0">
                        <div class="text-xs text-gray-500 uppercase" data-app-type>—</div>
                        <div class="text-xs text-gray-500" data-dz-size></div>
                        <a hidden data-app-download href="#" target="_blank" class="text-xs text-blue-600 underline">Download</a>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500">Name</label>
                        <input type="text" name="attachments[][name]" data-app-name class="block w-full rounded border-gray-300">
                    </div>
                    <button type="button" data-dz-remove class="text-gray-400 hover:text-red-500" aria-label="Remove">×</button>
                    <input type="hidden" name="attachments[][token]" data-app-token>
                    <div data-dz-errormessage class="text-red-500 text-xs basis-full"></div>
                    <div data-dz-uploadprogress class="absolute bottom-0 left-0 h-1 bg-blue-500" style="width:0"></div>
                </div>
            </x-slot:preview_template>
        </x-hwc::file-upload>
    </x-hwc::field>

    <button type="submit">Save gallery</button>
</x-hwc::form>
```

The slot is a normal Blade fragment — Tailwind classes get JIT-scanned as usual. The two
input names use empty `[]` brackets as placeholders; the subclass renumbers them to explicit
indices (`attachments[0][token]`, `attachments[1][token]`, …) on every add/remove/reorder so
the server receives a clean ordered array.

### CSS overrides (neutralize Dropzone's grid layout)

Dropzone's default `.dz-preview` is `display: inline-block` with `margin: 16px` — fights the
vertical list. A targeted reset in your app stylesheet:

```css
.hwc-media-list .dz-preview {
    display: flex;
    margin: 0;
    min-height: 0;
    position: relative;
}
.hwc-media-list .dz-preview .dz-image,
.hwc-media-list .dz-preview .dz-details,
.hwc-media-list .dz-preview .dz-progress,
.hwc-media-list .dz-preview .dz-success-mark,
.hwc-media-list .dz-preview .dz-error-mark {
    all: unset;
}
```

### Stimulus subclass

```js
// resources/js/controllers/media_upload_controller.js
import FileUploadController from "@hotwire/file_upload_controller.js";
import Sortable from "sortablejs";

export default class extends FileUploadController {
    afterInit() {
        this.dropzone.on("addedfile", (file) => this.populateMetadata(file));
        this.dropzone.on("success", (file, response) => this.stampToken(file, response));
        this.dropzone.on("removedfile", () => this.renumber());

        this.sortable = new Sortable(this.element, {
            handle: "[data-app-drag]",
            animation: 150,
            draggable: ".dz-preview",
            onEnd: () => this.renumber(),
        });
    }

    disconnect() {
        this.sortable?.destroy();
        super.disconnect();
    }

    populateMetadata(file) {
        const preview = file.previewElement;
        if (!preview) return;
        const nameInput = preview.querySelector("[data-app-name]");
        if (nameInput && !nameInput.value) nameInput.value = file.name;
        const typeSpan = preview.querySelector("[data-app-type]");
        if (typeSpan) typeSpan.textContent = this.formatType(file.type);
        this.renumber();
    }

    stampToken(file, response) {
        const value = this.extractValue(response);
        const preview = file.previewElement;
        const tokenInput = preview?.querySelector("[data-app-token]");
        if (tokenInput && value != null) tokenInput.value = value;
        const downloadLink = preview?.querySelector("[data-app-download]");
        if (downloadLink && response?.download_url) {
            downloadLink.href = response.download_url;
            downloadLink.hidden = false;
        }
    }

    renumber() {
        this.element.querySelectorAll(".dz-preview").forEach((preview, index) => {
            preview.querySelectorAll("[name]").forEach((input) => {
                input.name = input.name.replace(/^attachments\[\d*\]/, `attachments[${index}]`);
            });
        });
    }

    formatType(mime) {
        const map = {
            "image/jpeg": "JPEG", "image/png": "PNG", "image/webp": "WebP",
            "image/gif": "GIF", "application/pdf": "PDF",
        };
        return map[mime] ?? mime?.split("/")[1]?.toUpperCase() ?? "FILE";
    }
}
```

Install the SortableJS dep app-side: `bun add sortablejs` (or `npm install sortablejs`).
The subclass file lives in your app's controllers folder — `hotwire:make-controller` can
scaffold it, then you swap the base class to extend the published `FileUploadController`.

### Server-side controller

```php
public function store(Request $request)
{
    $data = $request->validate([
        'attachments' => ['array', 'min:1'],
        'attachments.*.token' => ['required', 'string'],
        'attachments.*.name' => ['required', 'string', 'max:255'],
    ]);

    foreach ($data['attachments'] as $position => $entry) {
        $finalPath = Storage::move(
            $entry['token'],
            'gallery/' . basename($entry['token'])
        );
        Gallery::create([
            'user_id' => $request->user()->id,
            'path' => $finalPath,
            'name' => $entry['name'],
            'position' => $position,
        ]);
    }

    return redirect()->route('gallery.show');
}
```

Validation errors come back as `attachments.0.name`, `attachments.1.token`, etc. Aggregate
them under the field with `<x-hwc::error name="attachments" />` (matches `attachments.*`), or
render per-card with explicit `error-key`.

### Edit forms — pre-existing media

For an edit form where the gallery already has items, render those server-side as
`.dz-preview` siblings inside the file-upload component before Dropzone instantiates. The
subclass treats them identically to newly-uploaded files — Sortable lifts them, renumber
indexes them:

```blade
<x-hwc::file-upload controller="media-upload" name="attachments" url="..." multiple :emit-hidden="false">
    @foreach ($gallery->items as $item)
        <div class="dz-preview dz-file-preview flex items-center gap-4 p-3 border-b bg-white">
            <button type="button" data-app-drag class="cursor-move text-gray-400">≡</button>
            <img src="{{ $item->thumbnail_url }}" class="w-16 h-16 object-cover rounded">
            <div class="w-32 shrink-0">
                <div class="text-xs text-gray-500 uppercase">{{ strtoupper($item->extension) }}</div>
                <div class="text-xs text-gray-500">{{ $item->formatted_size }}</div>
                <a href="{{ $item->download_url }}" class="text-xs text-blue-600 underline">Download</a>
            </div>
            <div class="flex-1">
                <input type="text" name="attachments[][name]" value="{{ $item->name }}" data-app-name class="block w-full rounded border-gray-300">
            </div>
            <button type="button" data-dz-remove class="text-gray-400 hover:text-red-500">×</button>
            <input type="hidden" name="attachments[][token]" value="{{ $item->token }}" data-app-token>
        </div>
    @endforeach

    <x-slot:preview_template>
        {{-- same card markup as the create form --}}
    </x-slot:preview_template>
</x-hwc::file-upload>
```

The default slot of `<x-hwc::file-upload>` is rendered inside the wrapper — the loop above
goes there, before the `<x-slot:preview_template>` named slot. On submit, both pre-existing
and newly-uploaded cards send `attachments[N][token]` and `attachments[N][name]` in the same
shape; the server treats them uniformly.

### When this pattern fits

- **Multi-file uploads** where the user needs to **rename** files before they're persisted (e.g.
  uploaded filenames like `IMG_2034.jpeg` need human-readable display names).
- **Ordered collections** where position matters (gallery, slideshow, document set).
- **Cases where a single submit transaction** owns the whole list — for individually editable
  records after persist, prefer a separate edit-each-item flow.

For unordered single-token storage, use [#1 Spatie Media Library](#1-spatie-media-library)
instead — simpler shape and no client-side state.

**Need the draft to survive page reloads or cross-device sessions?** See the
[draft-as-state gallery recipe](draft-as-state-gallery.md) — same UI, but each rename,
reorder and removal hits the server immediately and persists to a `pending_*` table.
Trades chatter for resumability and per-action validation.
