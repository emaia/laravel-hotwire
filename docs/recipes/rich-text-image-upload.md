# Rich text image upload

Wire the `<x-hwc::rich-text>` component to a Laravel endpoint that stores pasted/dropped images
and returns a public URL the editor can insert. The package doesn't ship a runtime endpoint —
storage and access control are app concerns — so this recipe is the canonical reference for how
the pieces fit together.

## Overview

```
user pastes/drops image
    └─▶ rich-text controller intercepts (image-upload prop enabled)
        └─▶ dispatches rich-text:image-upload { file, editor }
            └─▶ app listener POSTs file to /uploads
                └─▶ Laravel stores it and returns { url }
                    └─▶ app listener calls editor.chain().focus().setImage({ src: url }).run()
```

The package's responsibility ends at "dispatch an event with the file". Everything past that is
your app's choice: where the file lives, how it's served, what authorization protects it.

## Enable image upload on the component

```blade
<x-hwc::rich-text
    name="content"
    placeholder="Write something…"
    :content="$post->content"
    :image-upload="true"
/>
```

That's the only change at the markup level. With `image-upload` enabled, the controller registers
Tiptap `editorProps.handlePaste` and `handleDrop` handlers that filter for files where
`file.type` starts with `image/`, call `preventDefault`, and dispatch the event.

## The image extension

Tiptap ships an `Image` extension separately. Install it and add it to the editor's extension
stack via a subclass — the controller's default stack is StarterKit + Link + Underline +
(optional) Placeholder.

```bash
bun add @tiptap/extension-image
```

```js
// resources/js/controllers/rich_text_controller.js (or a subclass)
import RichTextController from "@hotwire/rich_text_controller.js";
import { defaultExtensions } from "@hotwire/_rich_text_editor.js";
import Image from "@tiptap/extension-image";

export default class extends RichTextController {
    extensions(options) {
        return [...defaultExtensions(options), Image];
    }
}
```

If you're publishing the controller without subclassing, edit
`resources/js/controllers/rich_text_controller.js` directly — the version published via
`hotwire:controllers rich-text` is yours to modify.

## Listen for the upload event

Two paths — pick the one that fits your codebase.

### Path 1 — Global Stimulus event listener (default)

The controller dispatches `rich-text:image-upload` which bubbles from the editor element. A small
script anywhere on the page can catch it:

```js
// resources/js/app.js (after the Stimulus + Turbo bootstrap)
document.addEventListener("rich-text:image-upload", async (event) => {
    const { file, editor } = event.detail;

    const body = new FormData();
    body.append("image", file);
    body.append("_token", document.querySelector('meta[name="csrf-token"]').content);

    try {
        const response = await fetch("/posts/upload-image", {
            method: "POST",
            body,
            headers: { "Accept": "application/json" },
        });

        if (!response.ok) throw new Error(`Upload failed: ${response.status}`);

        const { url } = await response.json();
        editor.chain().focus().setImage({ src: url }).run();
    } catch (error) {
        console.error("Rich text image upload failed:", error);
    }
});
```

Add `<meta name="csrf-token" content="{{ csrf_token() }}">` to your layout if it isn't already
there — Laravel's `VerifyCsrfToken` middleware expects either a `_token` field or `X-CSRF-TOKEN`
header on POSTs.

### Path 2 — Subclass override (colocated)

Keep the upload logic next to the editor by overriding the wrapper hook in a subclass. The base
controller dispatches the event through `handleImageUpload(file)` — override it to do the work
directly:

```js
// resources/js/controllers/rich_text_controller.js (forked via `hotwire:controllers rich-text`)
import { Controller } from "@hotwired/stimulus";
import { RichTextEditor, defaultExtensions } from "@hotwire/_rich_text_editor.js";
import Image from "@tiptap/extension-image";

export default class extends Controller {
    // …existing static values/targets/connect/disconnect from the published controller…

    extensions(options) {
        return [...defaultExtensions(options), Image];
    }

    async handleImageUpload(file) {
        const body = new FormData();
        body.append("image", file);

        const response = await fetch("/posts/upload-image", {
            method: "POST",
            body,
            headers: {
                "Accept": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const { url } = await response.json();

        this.editor?.chain().focus().setImage({ src: url }).run();
    }
}
```

Path 1 keeps the controller stock but adds a global listener; Path 2 is one-stop but pins the
upload URL into the controller. Either way, the response is `{ url: "https://…/path/to/file" }`.

## The Laravel route

```php
// routes/web.php
Route::post('/posts/upload-image', [PostImageController::class, '__invoke'])
    ->middleware('auth')
    ->name('posts.upload-image');
```

```php
// app/Http/Controllers/PostImageController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostImageController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:8192'],
        ]);

        $path = $validated['image']->store('posts/inline', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }
}
```

Tighten or loosen the validation rules to fit your domain. `image` already verifies the file is a
valid image; `mimes:` adds an allowlist; `max:8192` caps at 8 MB.

If you're using S3 or another remote disk, swap `'public'` for the disk name and Laravel returns
the right URL automatically — as long as the disk is configured with `'visibility' => 'public'`
or your filesystem layer signs URLs.

## With Spatie Media Library

When you already use `spatie/laravel-medialibrary` for your model's attachments, treat each
inline image as a media item rather than a raw file in storage. This keeps file management
consistent (conversions, deletes-cascade, etc.):

```php
public function __invoke(Request $request, Post $post)
{
    $validated = $request->validate([
        'image' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:8192'],
    ]);

    $media = $post
        ->addMedia($validated['image']->getRealPath())
        ->usingFileName($validated['image']->hashName())
        ->toMediaCollection('inline');

    return response()->json([
        'url' => $media->getUrl(),
    ]);
}
```

For draft posts (`$post` doesn't exist yet), you can stash the upload against a temp owner and
move it to the real `Post` after the form submits.

## Sanitize before saving

The editor's payload is client HTML. Sanitize on the server before persisting:

```php
public function update(Request $request, Post $post)
{
    $clean = clean($request->input('content'), [
        'HTML.Allowed' => 'p,br,strong,em,u,ul,ol,li,blockquote,a[href|title],h1,h2,h3,pre,code,img[src|alt|width|height]',
        'URI.AllowedSchemes' => ['http' => true, 'https' => true],
    ]);

    $post->update(['content' => $clean]);

    return to_route('posts.show', $post);
}
```

The `img` tag rule is what lets the inserted images survive sanitization. Lock the allowed
attributes (`src|alt|width|height`) to what your editor actually emits.

## Drag-and-drop UX

Files dropped onto the editor get the same treatment as pastes — the controller dispatches one
event per image. The dropped images replace the current selection in the document. If you want a
custom drop UI (highlight, preview), listen for `dragenter`/`dragleave` on the editor target and
toggle a class:

```js
const editor = document.querySelector("[data-rich-text-target='editor']");
editor.addEventListener("dragover", (e) => editor.classList.add("is-dragging"));
editor.addEventListener("dragleave", (e) => editor.classList.remove("is-dragging"));
editor.addEventListener("drop", () => editor.classList.remove("is-dragging"));
```

## Things to think about

- **Auth on the upload endpoint** — anyone who can paste into an editor can hit your upload
  route. Gate it behind `auth` (or stricter) so you don't end up as a free image host.
- **Storage location** — the public disk works for low-traffic sites; switch to S3 / CDN for
  anything user-facing at scale.
- **Cleanup of unused uploads** — a user who pastes 10 images and then deletes 8 of them before
  saving leaves 8 orphans. A periodic job that reconciles `posts/inline/*` against `posts.content`
  catches that drift; alternatively, only upload on submit by stashing files locally first
  (heavier UX trade-off).
- **CSRF on cross-origin requests** — when the editor and the upload route live on different
  domains, you'll need CORS + token auth instead of session CSRF.

## See also

- [Component documentation](../components/rich-text.md)
- [Rich text controller](../controllers/rich-text.md)
- [Tiptap Image extension](https://tiptap.dev/api/nodes/image)
