# File Upload Patterns

Five real-world patterns for the native [`<hw:file-upload>`](../components/file-upload.md) component plus the
[`file-upload`](../controllers/file-upload.md) Stimulus controller.

- [1. MediaMan gallery with lazy thumbnails](#1-mediaman-gallery-with-lazy-thumbnails)
- [2. Async thumbnail via broadcast](#2-async-thumbnail-via-broadcast)
- [3. Stream-rendered gallery with server-side EXIF](#3-stream-rendered-gallery-with-server-side-exif)
- [4. Single-file edit form with a stream-replaced card](#4-single-file-edit-form-with-a-stream-replaced-card)
- [5. Rich media library list with rename and reorder](#5-rich-media-library-list-with-rename-and-reorder)

## 1. MediaMan Gallery With Lazy Thumbnails

Let `<hw:file-upload>` handle selection, drag/drop, upload progress and Turbo Stream delivery while
[`emaia/laravel-mediaman`](https://github.com/emaia/laravel-mediaman) stores the file and owns the media relationship.
Each upload returns a server-rendered attachment card; that card carries the hidden input for the final form and uses
[`lazy-image`](../controllers/lazy-image.md) to poll the generated thumbnail URL until the conversion exists.

Keep the media list outside the final form so each card can have its own DELETE form. The hidden inputs point back to the
real form with the native `form` attribute.

```blade
<hw:form id="post-form" action="{{ route('posts.update', $post) }}" method="put">
    <hw:field name="title" label="Title">
        <hw:input name="title" :value="$post->title" />
    </hw:field>
</hw:form>

<hw:field name="media_ids" label="Gallery">
    <hw:file-upload
        name="media_ids"
        url="{{ route('posts.media.store', $post) }}"
        multiple
        turbo-stream
        :clearable="false"
        accept="image/*"
        view="grid"
        :max-size-bytes="10 * 1024 * 1024"
        :messages="['idleMultiple' => 'Drop images or click to upload']"
    />
</hw:field>

<hw:attachment.group id="post-media-gallery" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
    @foreach ($post->getMedia('gallery') as $media)
        @include('posts._media-card', ['post' => $post, 'media' => $media, 'formId' => 'post-form'])
    @endforeach
</hw:attachment.group>

<hw:button type="submit" form="post-form">Save post</hw:button>
```

The upload endpoint creates a MediaMan `Media` row, starts the thumbnail conversion, and appends the card. The card is
the source of truth for the submitted media id; raw Turbo Stream mode disables automatic hidden inputs.

```php
use App\Models\Post;
use Emaia\MediaMan\Jobs\PerformConversions;
use Emaia\MediaMan\MediaUploader;
use Emaia\MediaMan\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::middleware('auth')->group(function () {
    Route::post('/posts/{post}/media', [PostMediaController::class, 'store'])->name('posts.media.store');
    Route::delete('/posts/{post}/media/{media}', [PostMediaController::class, 'destroy'])->name('posts.media.destroy');
});
```

```php
class PostMediaController
{
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:10240'],
        ]);

        $media = MediaUploader::fromRequest('file', $request)
            ->useCollection('Post uploads')
            ->withCustomProperties([
                'uploaded_by' => $request->user()->id,
                'post_id' => $post->id,
            ])
            ->upload();

        PerformConversions::dispatch($media, ['thumb']);

        return turbo_stream()->append(
            'post-media-gallery',
            view('posts._media-card', ['post' => $post, 'media' => $media, 'formId' => 'post-form'])
        );
    }

    public function destroy(Request $request, Post $post, Media $media)
    {
        abort_unless(
            (int) $media->getCustomProperty('uploaded_by') === (int) $request->user()->id
            && (int) $media->getCustomProperty('post_id') === (int) $post->id,
            403
        );

        $post->detachMedia($media);
        $media->delete();

        return turbo_stream()->remove("media-{$media->id}");
    }
}
```

Render the card with `lazy-image`. The placeholder appears immediately; the controller replaces it with the thumb when
the conversion URL starts returning 200 OK.

```blade
{{-- resources/views/posts/_media-card.blade.php --}}
<hw:attachment id="media-{{ $media->id }}" orientation="vertical" role="listitem" data-media-card>
    <hw:attachment.media variant="image">
        <picture
            {{
                stimulus()->controller('lazy-image', [
                    'url' => $media->getUrl('thumb'),
                    'alt' => $media->name,
                    'interval' => 1500,
                    'maxAttempts' => 40,
                    'width' => $media->getImageWidth(),
                    'height' => $media->getImageHeight(),
                    'imgClass' => 'h-full w-full object-cover',
                ])
            }}
            class="block aspect-square bg-muted"
        >
            @if ($media->getPlaceholder())
                <img src="{{ $media->getPlaceholder() }}" alt="" class="h-full w-full object-cover" aria-hidden="true">
            @else
                <hw:skeleton class="h-full w-full" />
            @endif
        </picture>
    </hw:attachment.media>

    <hw:attachment.content>
        <hw:attachment.title>{{ $media->name }}</hw:attachment.title>
        <hw:attachment.description>{{ strtoupper($media->extension) }} · {{ $media->friendly_size }}</hw:attachment.description>
        <input type="hidden" name="media_ids[]" value="{{ $media->id }}" form="{{ $formId }}">
    </hw:attachment.content>

    <hw:attachment.actions>
        <form action="{{ route('posts.media.destroy', [$post, $media]) }}" method="post">
            @csrf
            @method('delete')
            <hw:attachment.action type="submit" aria-label="Remove {{ $media->name }}">
                <hw:icon name="x" />
            </hw:attachment.action>
        </form>
    </hw:attachment.actions>
</hw:attachment>
```

On final submit, validate the submitted ids and sync the model channel. `syncMedia()` is still the ownership boundary;
the upload endpoint only stages Media rows and returns visible cards.

```php
public function update(Request $request, Post $post)
{
    $validated = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'media_ids' => ['array'],
        'media_ids.*' => [
            'integer',
            Rule::exists(config('mediaman.tables.media'), 'id')
                ->where('custom_properties->uploaded_by', $request->user()->id)
                ->where('custom_properties->post_id', $post->id),
        ],
    ]);

    $post->update(['title' => $validated['title']]);
    $post->syncMedia($validated['media_ids'] ?? [], 'gallery');

    return back();
}
```

If your `gallery` channel already runs `performConversions('thumb')`, skip the explicit `PerformConversions::dispatch()`
unless you need the thumbnail before the final submit. If the same Media can be shared by several models, detach it in
the destroy action instead of deleting the Media row. Add a scheduled prune for uploaded rows that are never claimed.

## 2. Async Thumbnail Via Broadcast

Heavy thumbnail generation moves to a queued job. The upload endpoint returns a Turbo Stream immediately with a pending
card; when processing finishes, your broadcaster replaces that card with the final thumb.

```blade
<hw:file-upload
    name="attachments"
    url="{{ route('uploads.store') }}"
    multiple
    turbo-stream
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

The server-rendered card carries its own hidden input; raw Turbo Stream mode does not emit another one.

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

The native uploader sends `Accept: application/json, text/vnd.turbo-stream.html`, detects the stream body and calls
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

The attachment list stays outside the final form so every remove action can own a valid DELETE micro-form. Card inputs
and the Save button target the final form through the native `form` attribute.

```blade
<hw:form id="gallery-form" action="{{ route('gallery.store') }}">
    <hw:field name="attachments" label="Images">
        <hw:file-upload
            name="attachments"
            url="{{ route('uploads.store') }}"
            multiple
            accept="image/*,application/pdf"
            turbo-stream
            :max-size-bytes="10 * 1024 * 1024"
            :messages="['idleMultiple' => 'Drag files or click to add media']"
        />
    </hw:field>
</hw:form>

<hw:attachment.group id="media-list" data-controller="media-list">
    @foreach ($gallery->items as $item)
        @include('gallery.media-card', ['item' => $item])
    @endforeach
</hw:attachment.group>

<hw:button type="submit" form="gallery-form">Save gallery</hw:button>
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
        <input type="text" name="attachments[][name]" value="{{ $item->name }}" form="gallery-form" data-app-name>
        <input type="hidden" name="attachments[][token]" value="{{ $item->token }}" form="gallery-form" data-app-token>
    </hw:attachment.content>
    <hw:attachment.actions>
        <hw:attachment.action data-app-drag aria-label="Reorder {{ $item->name }}">≡</hw:attachment.action>
        <form action="{{ route('uploads.destroy', $item) }}" method="post">
            @csrf
            @method('DELETE')
            <hw:attachment.action type="submit" aria-label="Remove {{ $item->name }}">×</hw:attachment.action>
        </form>
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
