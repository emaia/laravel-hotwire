# Rich text image upload

Wire the `<hw:rich-text>` component to a Laravel endpoint that stores pasted or dropped images
and returns a public URL the editor can insert. The package doesn't ship a runtime endpoint;
storage and access control are app concerns.

## Overview

```
user pastes/drops image
    └─▶ post-rich-text subclass intercepts it (image-upload prop enabled)
        └─▶ subclass POSTs the file to Laravel
            └─▶ Laravel stores it and returns { url }
                └─▶ subclass inserts the image URL into Tiptap
```

The package provides the interception hook. Your app chooses where the file lives, how it is
served, and what authorization protects it.

## Extend the package controller

Tiptap ships its `Image` extension separately. Install it, then create an app-owned controller
under a different identifier. Importing the parent through `@hotwire` keeps the package controller
available as `rich-text` and lets the subclass inherit package fixes.

```bash
bun add @tiptap/extension-image@3.31.3
```

```js
// resources/js/controllers/post_rich_text_controller.js
import RichTextController from "@hotwire/rich_text_controller.js";
import { defaultExtensions } from "@hotwire/_rich_text_editor.js";
import Image from "@tiptap/extension-image";

export default class extends RichTextController {
    static values = {
        ...RichTextController.values,
        endpoint: String,
    };

    extensions(options) {
        return [...defaultExtensions(options), Image];
    }

    async handleImageUpload(file) {
        const body = new FormData();
        body.append("image", file);

        try {
            if (!this.hasEndpointValue) throw new Error("Missing image upload endpoint");

            const response = await fetch(this.endpointValue, {
                method: "POST",
                body,
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) throw new Error(`Upload failed: ${response.status}`);

            const { url } = await response.json();
            this.editor?.chain().focus().setImage({ src: url }).run();
        } catch (error) {
            console.error("Rich text image upload failed:", error);
        }
    }
}
```

The file name registers this controller as `post-rich-text`. Mount that identifier through the
component and pass existing content with `value`:

```blade
<hw:rich-text
    name="content"
    controller="post-rich-text"
    placeholder="Write something…"
    :value="$post->content"
    :image-upload="true"
    :stimulus="stimulus()->controller('post-rich-text', [
        'endpoint' => route('posts.upload-image'),
    ])"
/>
```

Add `<hw:meta csrf />` to your layout if it isn't already
there. Laravel's `VerifyCsrfToken` middleware expects either a `_token` field or `X-CSRF-TOKEN`
header on POSTs.

With `image-upload` enabled, the controller registers Tiptap paste and drop handlers, filters for
`image/*` files, and calls `handleImageUpload(file)`. The `endpoint` value keeps the controller
reusable across generic and model-specific upload routes. The endpoint response is
`{ url: "https://…/path/to/file" }`.

## Fork the `rich-text` identifier only when needed

If the app must replace the package behavior under the same `rich-text` identifier, publish an
explicit fork:

```bash
php artisan hotwire:controllers rich-text
```

Edit `resources/js/controllers/rich_text_controller.js` and remove its `// @hotwire-package`
marker so package repair commands treat it as user-owned. This local controller shadows the
vendor controller and must be maintained by the app. Prefer the `post-rich-text` subclass above
unless same-identifier replacement is intentional. See
[Extending controllers](../extending-controllers.md) for the tradeoffs.

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
consistent (conversions, deletes-cascade, etc.). Unlike the generic endpoint above, this route
must identify and authorize the owning post:

```php
// routes/web.php
Route::post('/posts/{post}/upload-image', PostMediaImageController::class)
    ->middleware('auth')
    ->name('posts.inline-images.store');
```

```php
use Illuminate\Support\Facades\Gate;

public function __invoke(Request $request, Post $post)
{
    Gate::authorize('update', $post);

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

Point the same `post-rich-text` subclass at the post-specific named route:

```blade
<hw:rich-text
    name="content"
    controller="post-rich-text"
    :value="$post->content"
    :image-upload="true"
    :stimulus="stimulus()->controller('post-rich-text', [
        'endpoint' => route('posts.inline-images.store', $post),
    ])"
/>
```

For draft posts (`$post` doesn't exist yet), you can stash the upload against a temp owner and
move it to the real `Post` after the form submits.

## Sanitize before saving

The editor's payload is client-controlled HTML. Neither Laravel nor this package ships an HTML
sanitizer. Install and configure a maintained PHP sanitizer that fits your application's security
policy, then sanitize after request validation and before persistence or rendering. Laravel's
string and length validation rules validate the payload shape; they do not make HTML safe.

**Pseudocode only — these names are not Laravel or package APIs:**

```text
validatedHtml = validate request content as a string with an application-appropriate size limit
sanitizedHtml = configuredSanitizer.sanitize(validatedHtml, applicationAllowlist)
persist sanitizedHtml on the authorized model
```

Keep the allowlist aligned with the Tiptap extensions you enable. Image support normally requires
`img` plus only the attributes your editor emits, such as `src`, `alt`, `width`, and `height`.
Constrain URL schemes and origins according to where your upload endpoint stores media.

## Drag-and-drop UX

Files dropped onto the editor get the same treatment as pastes; the controller calls the upload
hook once per image. Because the upload is asynchronous, the example inserts each image at the
editor's active selection when that upload completes, which may no longer be the original drop
location. If exact placement matters, capture a Tiptap document position from the drop event before
starting the upload and insert at that saved position after the response. For a custom drop UI
(highlight, preview), listen for `dragenter`/`dragleave` on the editor target and toggle a class:

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
