# Draft-as-state — media gallery

A pattern for **multi-step creation flows where the draft is canonical state**: every
upload, rename, removal and reorder hits the server immediately and updates a `pending_*`
table. The form on the page barely has any data — by the time the user clicks "Publish",
everything they did is already saved server-side. The final action just promotes the draft
into the permanent resource.

This recipe applies the pattern to an **image gallery**. The same skeleton works for any
multi-step creation flow (wizard builders, multi-part forms, product editors with media);
the closing section [generalizes the pattern](#generalizing-the-pattern).

## Why this pattern

Compare it with the [client-side rich media list](file-upload-patterns.md#5-rich-media-library-list-with-rename-and-reorder)
(recipe #5 in file-upload-patterns):

| Concern                            | Recipe #5 (client state)    | Draft-as-state (this recipe)                   |
|------------------------------------|-----------------------------|------------------------------------------------|
| Source of truth                    | DOM until submit            | DB (`pending_*` table) at every interaction    |
| Lost work on F5 / accidental close | Yes — DOM state goes away   | No — draft persists                            |
| Resumable across sessions          | No                          | Yes (cross-device too)                         |
| Validation timing                  | At submit only              | Per-action (file too big, name too long, etc.) |
| Multi-user editing later           | Re-architect                | Add `Broadcasts` trait                         |
| Network chatter                    | One submit                  | One round trip per action                      |
| Setup complexity                   | Single component + subclass | Domain model + 5 endpoints + cleanup           |

**Pick this pattern when** the user genuinely needs the draft to outlive the session
(image-heavy editors, multi-step product creation, collaborative galleries) and the chatter
cost is acceptable. **Pick recipe #5 when** the flow is single-session and you want to
avoid the server infrastructure.

## Domain model

A separate table for drafts. Keeping pending and final records apart means you can prune
abandoned drafts without touching the published gallery, and the schemas can drift
independently.

```php
// database/migrations/2025_01_01_000000_create_pending_media_items_table.php
Schema::create('pending_media_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('token');               // temp storage path returned by the upload
    $table->string('name');                // editable display name
    $table->unsignedSmallInteger('position');
    $table->string('mime_type');
    $table->unsignedInteger('size_bytes');
    $table->string('thumbnail_path')->nullable();
    $table->timestamp('expires_at');
    $table->timestamps();

    $table->index(['user_id', 'position']);
});
```

```php
// app/Models/PendingMediaItem.php
class PendingMediaItem extends Model
{
    protected $fillable = [
        'user_id', 'token', 'name', 'position',
        'mime_type', 'size_bytes', 'thumbnail_path', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'size_bytes' => 'integer',
        'position' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function thumbnailUrl(): string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : '';
    }

    public function formattedSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->size_bytes;
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    public function typeLabel(): string
    {
        return match ($this->mime_type) {
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/webp' => 'WebP',
            'image/gif' => 'GIF',
            'application/pdf' => 'PDF',
            default => strtoupper(pathinfo($this->name, PATHINFO_EXTENSION) ?: 'FILE'),
        };
    }
}
```

A matching policy enforces "user can only touch their own drafts":

```php
// app/Policies/PendingMediaItemPolicy.php
class PendingMediaItemPolicy
{
    public function update(User $user, PendingMediaItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    public function delete(User $user, PendingMediaItem $item): bool
    {
        return $user->id === $item->user_id;
    }
}
```

## Routes

Five endpoints in total. Four manage the draft incrementally; one publishes.

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/gallery/new', [GalleryController::class, 'create'])->name('gallery.create');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('gallery.store');

    Route::controller(PendingMediaController::class)
        ->prefix('pending-media')
        ->name('pending-media.')
        ->group(function () {
            Route::post('/', 'store')->name('store');
            Route::patch('/{pendingMedia}', 'update')->name('update');
            Route::delete('/{pendingMedia}', 'destroy')->name('destroy');
            Route::post('/reorder', 'reorder')->name('reorder');
        });
});
```

## Controllers

### `PendingMediaController`

Each action is small and atomic. Streams target the gallery list (append/remove); rename
and reorder return `noContent()` because the UI doesn't need a stream — the input already
holds the new value, Sortable already moved the DOM.

```php
class PendingMediaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $request->file('file');
        $token = $file->store('pending-media', 'public');
        $thumbnail = $this->makeThumbnail($file);

        $item = PendingMediaItem::create([
            'user_id' => $request->user()->id,
            'token' => $token,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'position' => (PendingMediaItem::forUser($request->user())->max('position') ?? -1) + 1,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'thumbnail_path' => $thumbnail,
            'expires_at' => now()->addHours(24),
        ]);

        return turbo_stream()->append('gallery-list', view('gallery._card', ['item' => $item]));
    }

    public function update(Request $request, PendingMediaItem $pendingMedia)
    {
        Gate::authorize('update', $pendingMedia);

        $pendingMedia->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        return response()->noContent();
    }

    public function destroy(Request $request, PendingMediaItem $pendingMedia)
    {
        Gate::authorize('delete', $pendingMedia);

        Storage::disk('public')->delete(array_filter([
            $pendingMedia->token,
            $pendingMedia->thumbnail_path,
        ]));
        $pendingMedia->delete();

        return turbo_stream()->remove(dom_id($pendingMedia));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                Rule::exists('pending_media_items', 'id')
                    ->where('user_id', $request->user()->id),
            ],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['ids'] as $position => $id) {
                PendingMediaItem::where('id', $id)->update(['position' => $position]);
            }
        });

        return response()->noContent();
    }

    private function makeThumbnail(UploadedFile $file): ?string
    {
        // App concern — use Intervention Image, Imagick, or whatever fits.
        // Returning null is fine; the view falls back to the original file's URL.
        return null;
    }
}
```

### `GalleryController`

The publish action reads the draft from DB and promotes it. The submitted form only
carries fields that aren't already in the draft (the gallery title).

```php
class GalleryController extends Controller
{
    public function create(Request $request)
    {
        $pending = PendingMediaItem::forUser($request->user())
            ->orderBy('position')
            ->get();

        return view('gallery.create', compact('pending'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $pending = PendingMediaItem::forUser($request->user())
            ->orderBy('position')
            ->get();

        if ($pending->isEmpty()) {
            return back()->withErrors(['images' => 'Add at least one image before publishing.']);
        }

        $gallery = DB::transaction(function () use ($request, $data, $pending) {
            $gallery = $request->user()->galleries()->create($data);

            foreach ($pending as $position => $item) {
                $finalPath = "galleries/{$gallery->id}/".basename($item->token);
                Storage::disk('public')->move($item->token, $finalPath);

                $gallery->items()->create([
                    'path' => $finalPath,
                    'name' => $item->name,
                    'position' => $position,
                ]);
            }

            PendingMediaItem::forUser($request->user())->delete();

            return $gallery;
        });

        return redirect()->route('gallery.show', $gallery);
    }
}
```

## Views

### The main page

The page has two visually-coupled but structurally independent regions:

1. **Image list** — managed entirely by the draft endpoints. Cards are server-rendered;
   each carries its own micro-forms (rename auto-save, delete). The file-upload component
   posts new uploads to the draft store endpoint and the response stream appends a card.
2. **Publish form** — a single `<hw:form>` with the gallery title and a submit button.
   When clicked, the server reads the draft from DB.

Keeping these as separate forms avoids the "form inside a form" footgun.

```blade
{{-- resources/views/gallery/create.blade.php --}}
<x-app-layout>
    <h1 class="text-2xl font-semibold">New gallery</h1>

    <section class="mt-6">
        <h2 class="text-sm uppercase text-gray-500">Images</h2>

        <hw:file-upload
            name="files"
            url="{{ route('pending-media.store') }}"
            param-name="file"
            accept="image/*"
            multiple
            :max-size-bytes="10 * 1024 * 1024"
            :turbo-stream="true"
            :preview="false"
            :emit-hidden="false"
            :messages="['default' => 'Drag images or click to upload']"
            class="mt-2"
        />

        <div
            id="gallery-list"
            data-controller="reorder-list"
            data-reorder-list-url-value="{{ route('pending-media.reorder') }}"
            class="mt-4 bg-white rounded shadow"
        >
            @foreach ($pending as $item)
                @include('gallery._card', ['item' => $item])
            @endforeach
        </div>
    </section>

    <hw:form action="{{ route('gallery.store') }}" class="mt-8">
        <hw:field name="title" label="Title">
            <hw:input name="title" required />
        </hw:field>

        <button type="submit" class="mt-4 bg-red-600 text-white px-4 py-2 rounded">
            Publish gallery
        </button>
    </hw:form>
</x-app-layout>
```

Key file-upload props:

- `:turbo-stream="true"` — accept stream responses on upload XHR
- `:preview="false"` — native client-side attachment cards are skipped because the server-rendered list owns the UI
- `:emit-hidden="false"` — no hidden inputs in the upload area (cards own their state)

### The card partial — single source of truth

This partial is used both on initial render (the `@foreach` loop) and inside Turbo Stream
responses (`view('gallery._card', ...)` in the controller). One file, two callers.

```blade
{{-- resources/views/gallery/_card.blade.php --}}
<div
    id="{{ dom_id($item) }}"
    data-item-id="{{ $item->id }}"
    class="flex items-center gap-4 p-3 border-b last:border-b-0"
>
    <button type="button" data-app-drag class="cursor-move text-gray-400" tabindex="-1" aria-label="Reorder">
        ≡
    </button>

    <img
        src="{{ $item->thumbnailUrl() ?: Storage::disk('public')->url($item->token) }}"
        alt=""
        class="w-16 h-16 object-cover rounded bg-gray-100 shrink-0"
    >

    <div class="w-32 shrink-0">
        <div class="text-xs text-gray-500 uppercase">{{ $item->typeLabel() }}</div>
        <div class="text-xs text-gray-500">{{ $item->formattedSize() }}</div>
        <a
            href="{{ Storage::disk('public')->url($item->token) }}"
            target="_blank"
            class="text-xs text-blue-600 underline"
        >Download</a>
    </div>

    <form
        action="{{ route('pending-media.update', $item) }}"
        method="post"
        data-controller="auto-save"
        data-auto-save-debounce-value="500"
        class="flex-1"
    >
        @csrf
        @method('PATCH')
        <label class="block text-xs text-gray-500" for="name-{{ $item->id }}">Name</label>
        <input
            type="text"
            id="name-{{ $item->id }}"
            name="name"
            value="{{ $item->name }}"
            class="block w-full rounded border-gray-300 text-sm"
        >
    </form>

    <form
        action="{{ route('pending-media.destroy', $item) }}"
        method="post"
        data-turbo-confirm="Remove this image?"
    >
        @csrf
        @method('DELETE')
        <button type="submit" class="text-gray-400 hover:text-red-500" aria-label="Remove">×</button>
    </form>
</div>
```

The rename form uses [`auto-save`](../controllers/auto-save.md) from the package — it
debounces input, submits the PATCH form, and ignores the empty response body. The delete
form is a regular DELETE form; the server returns a stream that removes the card.

## Stimulus — the `reorder-list` controller

The only piece of JavaScript this recipe needs beyond what the package ships. SortableJS
handles the drag interaction; the controller POSTs the new order to the server on drop.

```js
// resources/js/controllers/reorder_list_controller.js
import { Controller } from "@hotwired/stimulus";
import Sortable from "sortablejs";

export default class extends Controller {
    static values = { url: String };

    connect() {
        this.sortable = new Sortable(this.element, {
            handle: "[data-app-drag]",
            animation: 150,
            onEnd: () => this.persist(),
        });
    }

    disconnect() {
        this.sortable?.destroy();
    }

    persist() {
        const ids = [...this.element.children].map((el) => el.dataset.itemId).filter(Boolean);
        const token = document.querySelector('meta[name="csrf-token"]')?.content;

        fetch(this.urlValue, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": token,
                "Accept": "application/json",
            },
            body: JSON.stringify({ ids }),
        }).catch((e) => console.error("reorder persist failed:", e));
    }
}
```

Install SortableJS app-side: `bun add sortablejs` (or `npm install sortablejs`).
Scaffold the controller via `php artisan hotwire:make-controller reorder-list` to wire it
into the dynamic loader, then paste the code above.

## Cleanup — pruning abandoned drafts

Drafts older than `expires_at` should be removed periodically. Storage files go too,
otherwise abandoned uploads accumulate.

```php
// app/Console/Commands/PruneExpiredPendingMedia.php
class PruneExpiredPendingMedia extends Command
{
    protected $signature = 'pending-media:prune';
    protected $description = 'Delete expired pending media items and their files';

    public function handle(): int
    {
        $expired = PendingMediaItem::expired()->get();

        foreach ($expired as $item) {
            Storage::disk('public')->delete(array_filter([
                $item->token,
                $item->thumbnail_path,
            ]));
        }

        $count = $expired->count();
        PendingMediaItem::expired()->delete();

        $this->info("Pruned {$count} expired pending media items.");

        return self::SUCCESS;
    }
}
```

Schedule it hourly:

```php
// bootstrap/app.php (Laravel 11+)
->withSchedule(function (Schedule $schedule) {
    $schedule->command('pending-media:prune')->hourly();
})
```

A 24-hour TTL is conservative for image uploads; tune `expires_at` and the schedule
frequency to your storage tolerance and user behavior.

## Generalizing the pattern

The skeleton has six moving parts and barely any of them are specific to media uploads.
Map each one to your resource:

| Piece                  | Gallery                               | Multi-step form                   | Wizard builder                      |
|------------------------|---------------------------------------|-----------------------------------|-------------------------------------|
| **Draft table**        | `pending_media_items`                 | `pending_form_responses`          | `pending_wizard_steps`              |
| **Per-attribute REST** | Upload, rename, remove, reorder       | Save each field on change         | Save each step on next              |
| **Card partial**       | `_card.blade.php`                     | `_field.blade.php`                | `_step_summary.blade.php`           |
| **Publish action**     | `POST /gallery` reads pending + title | `POST /form/submit` reads pending | `POST /wizard/finish` reads pending |
| **Cleanup TTL**        | 24 hours, hourly cron                 | 7 days for abandoned forms        | 30 days for unfinished wizards      |
| **Auth scope**         | `user_id` (this recipe)               | `user_id` or `session_id`         | `user_id`                           |

What changes per use case:

- **Granularity of the draft action** — coarse (whole step) vs. fine (single field). Pick
  the smallest unit that delivers useful immediate validation.
- **What gets server-rendered vs. client-managed** — anything the server "owns" (markup
  from a Blade partial, validation errors, computed fields) goes through streams; pure UX
  (drag interactions, hover states) stays client-side.
- **Auto-save vs. explicit save buttons per region** — debounced auto-save fits text
  inputs and reorder; explicit save fits structured edits ("upload new file", "confirm
  step").

The mental shift is: stop treating the form as the state. The form is just the **promotion
trigger**. State lives server-side; the user's actions are individual server interactions;
the final "save" is a state machine transition, not a data dump.

## Tradeoffs

**Use this pattern when:**

- Users genuinely abandon and return (mobile uploads, long sessions)
- Multi-user editing is on the roadmap (broadcast streams plug in trivially)
- Per-action validation is valuable ("name too long" *now*, not at publish)
- The "draft" concept is part of the product (saved drafts list, restore on login)

**Don't reach for this when:**

- The flow is single-session and single-shot (use [recipe #5](file-upload-patterns.md#5-rich-media-library-list-with-rename-and-reorder))
- Network latency per action is a UX problem (heavy SSR, slow regions, offline-first apps)
- The resource lifecycle is simple enough that "publish on submit" carries no real risk
  of lost work
- Guest flows where you don't have a `user_id` to scope by (this recipe assumes
  authenticated users; covering guests means scoping by `session_id` with all the cleanup
  caveats that brings)

## See also

- [Recipe #5 — Rich media library, client-side](file-upload-patterns.md#5-rich-media-library-list-with-rename-and-reorder) — same UI, single-submit semantics, simpler infrastructure
- [Recipe #3 — Stream-rendered gallery with EXIF](file-upload-patterns.md#3-stream-rendered-gallery-with-server-side-exif) — closest sibling pattern, but without the per-action draft persistence
- [`auto-save` controller](../controllers/auto-save.md) — the debounced-submit primitive used by the rename input
