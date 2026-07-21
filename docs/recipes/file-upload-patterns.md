# File Upload Patterns

Five real-world patterns for the native [`<hw:file-upload>`](../components/file-upload.md) component plus the
[`file-upload`](../controllers/file-upload.md) Stimulus controller.

- [1. Spatie Media Library](#1-spatie-media-library)
- [2. Async thumbnail via broadcast](#2-async-thumbnail-via-broadcast)
- [3. Stream-rendered gallery with server-side EXIF](#3-stream-rendered-gallery-with-server-side-exif)
- [4. Single-file edit form with a stream-replaced card](#4-single-file-edit-form-with-a-stream-replaced-card)
- [5. Rich media library list with rename and reorder](#5-rich-media-library-list-with-rename-and-reorder)

## 1. Spatie Media Library

Pin uploaded files to a temporary collection and claim them on final submit. The endpoint returns the media UUID;
`response-key="uuid"` writes it into the hidden input.

```blade
<hw:file-upload
    name="avatar_uuid"
    url="{{ route('uploads.store') }}"
    response-key="uuid"
    :delete-url="route('uploads.destroy', ':token')"
    :value="$user->avatar_media_uuid"
    accept="image/*"
/>
```

```php
Route::post('/uploads', function (Request $request) {
    $request->validate(['file' => ['required', 'image', 'max:2048']]);

    $media = $request->user()
        ->addMedia($request->file('file'))
        ->toMediaCollection('avatars');

    return response()->json(['uuid' => $media->uuid]);
})->middleware(['auth', 'throttle:20,1'])->name('uploads.store');
```

## 2. Async Thumbnail Via Broadcast

Heavy thumbnail generation moves to a queued job. The upload endpoint returns a Turbo Stream immediately with a pending
card; when processing finishes, your broadcaster replaces that card with the final thumb.

```blade
<hw:file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    multiple
    turbo-stream
    :preview="false"
    :emit-hidden="false"
/>

<ul id="attachments"></ul>
```

```php
Route::post('/uploads', function (Request $request) {
    $request->validate(['file' => ['required', 'file', 'max:51200']]);

    $upload = $request->user()->uploads()->create([
        'path' => $request->file('file')->store('uploads'),
        'original_name' => $request->file('file')->getClientOriginalName(),
    ]);

    GenerateThumbnail::dispatch($upload);

    return turbo_stream()->append('attachments', view('uploads.card', ['upload' => $upload]));
})->name('uploads.store');
```

The server-rendered card carries its own hidden input, so `emit-hidden` stays false.

## 3. Stream-Rendered Gallery With Server-Side EXIF

User drops images; each upload returns a Turbo Stream appending a server-rendered `<li>` with thumbnail, file name and
server-side EXIF metadata.

```blade
<hw:form action="{{ route('gallery.save') }}" method="post">
    <hw:field name="photos" label="Add photos">
        <hw:file-upload
            name="photos"
            url="{{ route('gallery.upload') }}"
            accept="image/*"
            multiple
            turbo-stream
            :preview="false"
            :emit-hidden="false"
        />
    </hw:field>

    <ul id="photo-gallery"></ul>

    <hw:button type="submit">Save gallery</hw:button>
</hw:form>
```

```php
Route::post('/gallery/upload', function (Request $request) {
    $request->validate(['file' => ['required', 'image', 'max:10240']]);

    $photo = $request->user()->photos()->create([
        'path' => $request->file('file')->store('photos', 'public'),
        'original_name' => $request->file('file')->getClientOriginalName(),
    ]);

    return turbo_stream()->append('photo-gallery', view('photos.card', ['photo' => $photo]));
})->name('gallery.upload');
```

The native uploader sends `Accept: text/vnd.turbo-stream.html, application/json`, detects the stream body and calls
`Turbo.renderStreamMessage`.

## 4. Single-File Edit Form With A Stream-Replaced Card

For single-value resources, let the visible server-rendered card carry the hidden input. The upload stream replaces the
whole card so there is only one hidden value at a time.

```blade
<hw:form action="{{ route('profile.update') }}" method="put">
    @include('profile.avatar-card', ['user' => $user])

    <hw:field name="avatar_token" label="Change picture">
        <hw:file-upload
            name="avatar_token"
            url="{{ route('profile.avatar.upload') }}"
            accept="image/*"
            turbo-stream
            :emit-hidden="false"
        />
    </hw:field>

    <hw:button type="submit">Save</hw:button>
</hw:form>
```

```blade
{{-- profile/avatar-card.blade.php --}}
<div id="avatar-card">
    @if ($user->avatar_path)
        <img src="{{ Storage::url($user->avatar_path) }}" alt="Current avatar">
        <input type="hidden" name="avatar_token" value="{{ $user->avatar_token }}">
    @else
        <span>No avatar yet.</span>
    @endif
</div>
```

Rules: do not pass `value` to the uploader, and always return a stream that `replace`s the card.

## 5. Rich Media Library List With Rename And Reorder

Use `<hw:file-upload>` as the upload transport and server-rendered `<hw:attachment>` cards as the rich list. This keeps
rename, reorder and metadata app-owned while the package handles selection, upload progress and Turbo Stream delivery.

```blade
<hw:form action="{{ route('gallery.store') }}">
    <hw:field name="attachments" label="Images">
        <hw:file-upload
            name="attachments"
            url="{{ route('uploads.store') }}"
            multiple
            accept="image/*,application/pdf"
            turbo-stream
            :preview="false"
            :emit-hidden="false"
            :max-size-bytes="10 * 1024 * 1024"
            :messages="['idleMultiple' => 'Drag files or click to add media']"
        />
    </hw:field>

    <hw:attachment.group id="media-list" data-controller="media-list">
        @foreach ($gallery->items as $item)
            @include('gallery.media-card', ['item' => $item])
        @endforeach
    </hw:attachment.group>

    <hw:button type="submit">Save gallery</hw:button>
</hw:form>
```

```blade
{{-- gallery/media-card.blade.php --}}
<hw:attachment id="media-{{ $item->id }}" data-media-card>
    <hw:attachment.media variant="image">
        <img src="{{ $item->thumbnail_url }}" alt="{{ $item->name }}">
    </hw:attachment.media>
    <hw:attachment.content>
        <hw:attachment.title>{{ $item->name }}</hw:attachment.title>
        <hw:attachment.description>{{ strtoupper($item->extension) }} · {{ $item->formatted_size }}</hw:attachment.description>
        <input type="text" name="attachments[][name]" value="{{ $item->name }}" data-app-name>
        <input type="hidden" name="attachments[][token]" value="{{ $item->token }}" data-app-token>
    </hw:attachment.content>
    <hw:attachment.actions>
        <hw:attachment.action data-app-drag aria-label="Reorder {{ $item->name }}">≡</hw:attachment.action>
        <hw:attachment.action
            data-controller="remote-form"
            formaction="{{ route('uploads.destroy', $item) }}"
            formmethod="delete"
            aria-label="Remove {{ $item->name }}"
        >×</hw:attachment.action>
    </hw:attachment.actions>
</hw:attachment>
```

An app-side `media-list` controller can use SortableJS and renumber inputs after reorder:

```js
import { Controller } from "@hotwired/stimulus";
import Sortable from "sortablejs";

export default class extends Controller {
    connect() {
        this.sortable = new Sortable(this.element, {
            handle: "[data-app-drag]",
            animation: 150,
            draggable: "[data-media-card]",
            onEnd: () => this.renumber(),
        });

        this.renumber();
    }

    disconnect() {
        this.sortable?.destroy();
    }

    renumber() {
        this.element.querySelectorAll("[data-media-card]").forEach((card, index) => {
            card.querySelectorAll("[name]").forEach((input) => {
                input.name = input.name.replace(/^attachments\[\d*\]/, `attachments[${index}]`);
            });
        });
    }
}
```

For reload-resumable drafts, see [draft-as-state gallery](draft-as-state-gallery.md).
