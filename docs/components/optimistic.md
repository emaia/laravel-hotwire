# Optimistic

Declare an optimistic Turbo Stream action inline, next to the element it should
mutate. Works with **any** Turbo trigger — form submissions, frame links,
method links — with a single `data-controller` attribute.

## Why

Server-rendered UIs wait for a round-trip before reflecting user intent —
typically 150–300ms even on fast networks, which is past the "feels instant"
threshold. For actions whose outcome is predictable (toggles, inserts, deletes,
navigations), we can pre-render the success state client-side and let the
server *reconcile* via a Turbo 8 morph on the real response.

## Architecture

```
<x-hwc::optimistic>            ← emits <template data-optimistic-stream …>

optimistic--form               ← turbo:submit-start → dispatch
optimistic--link               ← click → dispatch
optimistic--dispatch           ← escape hatch for custom triggers
        └── all import _dispatch.js (shared scan + emit logic)
```

Add **one** controller to the host element:
- `optimistic--form` on a `<form>`
- `optimistic--link` on an `<a>`
- `optimistic--dispatch` for anything else

The component itself declares **no** controller dependency — the trigger
choice is yours.

## Basic usage — form submission

```html
<form
    data-controller="optimistic--form"
    action="/posts/1/favorite"
    method="post"
>
    @csrf

    <x-hwc::optimistic target="post_1_favorite" action="update">
        ❤️ Favorited
    </x-hwc::optimistic>

    <button type="submit" id="post_1_favorite">
        🤍 Favorite
    </button>
</form>
```

## Basic usage — frame link with skeleton

```html
<a
    href="/posts/42"
    data-turbo-frame="detail"
    data-controller="optimistic--link"
>
    View details

    <x-hwc::optimistic target="detail" action="update">
        <div class="animate-pulse p-4">Loading...</div>
    </x-hwc::optimistic>
</a>

<turbo-frame id="detail"></turbo-frame>
```

## Props

| Prop      | Type     | Default     | Description                                                    |
|-----------|----------|-------------|----------------------------------------------------------------|
| `target`  | `string` | `''`        | DOM id of the element to mutate                                |
| `targets` | `string` | `''`        | CSS selector (alternative to `target`, applies the action to all matches) |
| `action`  | `string` | `'replace'` | Turbo Stream action: `replace`, `update`, `append`, `prepend`, `before`, `after`, `remove`, `refresh` |

## Slot

The default slot is the HTML payload rendered by the Turbo Stream action. For
`remove` and `refresh` the slot can be empty.

## Complete example: toggleable favorite with reconciliation

A realistic end-to-end flow: form, controller, morph-driven reconciliation and
error feedback.

### Blade

```blade
{{-- resources/views/posts/_favorite.blade.php --}}
@props(['post'])

@php
    $isFavorited = $post->isFavoritedBy(auth()->user());
@endphp

<form
    data-controller="optimistic--form"
    action="{{ route('posts.favorite.toggle', $post) }}"
    method="post"
    class="inline-flex items-center gap-2"
>
    @csrf
    @method($isFavorited ? 'DELETE' : 'POST')

    {{-- Optimistic action: update button contents, keeping the form intact --}}
    <x-hwc::optimistic :target="dom_id($post, 'favorite_status')" action="update">
        @if ($isFavorited)
            <span class="text-slate-400">🤍 Favorite</span>
        @else
            <span class="text-rose-500">❤️ Favorited</span>
        @endif
        <span class="text-sm text-slate-500">
            {{ $post->favorites_count + ($isFavorited ? -1 : 1) }}
        </span>
    </x-hwc::optimistic>

    <button type="submit" id="{{ dom_id($post, 'favorite_status') }}" class="inline-flex items-center gap-2">
        <span class="{{ $isFavorited ? 'text-rose-500' : 'text-slate-400' }}">
            {{ $isFavorited ? '❤️ Favorited' : '🤍 Favorite' }}
        </span>
        <span class="text-sm text-slate-500">{{ $post->favorites_count }}</span>
    </button>
</form>
```

### Controller

```php
// app/Http/Controllers/PostFavoriteController.php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostFavoriteController
{
    public function store(Request $request, Post $post)
    {
        $this->authorize('favorite', $post);

        $request->user()->favorites()->attach($post);

        return turbo_stream()->refresh(method: 'morph', scroll: 'preserve');
    }

    public function destroy(Request $request, Post $post)
    {
        $this->authorize('favorite', $post);

        $request->user()->favorites()->detach($post);

        return turbo_stream()->refresh(method: 'morph', scroll: 'preserve');
    }
}
```

### Morph-friendly layout

Add the meta tags so Turbo reconciles via morph instead of full replacement.
This keeps the optimistic node in place and preserves focus, selection and
scroll.

```blade
{{-- resources/views/layouts/app.blade.php --}}
<head>
    {{-- ... --}}
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="preserve">
</head>
```

### Error feedback

If the server rejects the action, respond with a refresh **plus** a flash
toast. The morph reverts the optimistic DOM back to the real state, and the
toast tells the user why:

```php
use Illuminate\Support\Facades\Blade;

public function store(Request $request, Post $post)
{
    try {
        $this->authorize('favorite', $post);
    } catch (\Throwable $e) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->append('flash-container', Blade::render(
                '<x-hwc::flash-message :message="$message" type="error" />',
                ['message' => 'Could not favorite this post.'],
            ))
            ->withResponse(403);
    }

    // ...
}
```

## Populating optimistic HTML from form data

When the optimistic fragment needs dynamic content that the server cannot
pre-render (e.g. the text the user just typed), mark elements inside the slot
with `data-field="<input-name>"`. On submit, the dispatcher copies the matching
value from the form's `FormData` into the element's `textContent`.

```blade
<form
    data-controller="optimistic--form"
    data-optimistic--form-reset-value="true"
    action="{{ route('messages.store') }}"
    method="post"
>
    @csrf
    <textarea name="content" placeholder="Write a message…" required></textarea>
    <button type="submit">Send</button>

    <x-hwc::optimistic target="messages" action="append">
        <article class="message">
            <p data-field="content"></p>
            <small>Sending…</small>
        </article>
    </x-hwc::optimistic>
</form>

<div id="messages">
    {{-- server-rendered messages --}}
</div>
```

The `data-optimistic--form-reset-value="true"` attribute resets the form after
a successful submission — the user can immediately type the next message.

### Security

Field population uses `textContent` exclusively — **never** `innerHTML`. Any
HTML in the user input is rendered literally, not executed. Rich formatting
(markdown, mentions, autolinks, etc.) should remain a server concern; it
lands on the next morph.

## Styling the optimistic state

Every top-level element inside a dispatched payload is tagged automatically
with `data-optimistic`. Use it as a pure-CSS hook to visually differentiate
the provisional state:

```css
[data-optimistic] {
    opacity: 0.6;
    transition: opacity 150ms ease;
}
[data-optimistic] .spinner { display: inline-block; }
```

When the server response arrives and Turbo morphs in the authoritative HTML,
the attribute is gone and the styling returns to normal.

## Caveats

Optimistic UI has well-known pitfalls. Here's how each maps to this stack:

- **Rollback.** Traditionally the hardest part — you apply an optimistic
  change, the server rejects it, and you must undo. Our default relies on the
  server returning `turbo_stream()->refresh(method: 'morph')`, which makes the
  morph algorithm the rollback mechanism. No manual snapshot needed in the
  happy path.

- **Cache invalidation.** Not an issue here — the server is the single source
  of truth for DOM state. There is no client-side cache to keep in sync with
  page props.

- **Race conditions.** Rapid-fire submissions can land out of order. Use
  `turbo_stream()->refresh(requestId: 'unique-id')` to let Turbo 8 debounce
  duplicate refreshes per request id.

- **Back-button betrayal.** Turbo Drive can restore a *previous* page from
  cache, including a DOM that still has your optimistic mutation applied. To
  prevent stale optimistic content from appearing on back navigation, tag the
  fragment as temporary so Turbo strips it from the cached snapshot:

  ```blade
  <div id="messages" data-turbo-temporary>
      {{-- optimistic additions here will not survive Turbo's cache --}}
  </div>
  ```

  Or, at the page level, opt out of preview caching on views where
  optimistic state is sensitive:

  ```blade
  <meta name="turbo-cache-control" content="no-preview">
  ```

- **Server/client formatting mismatch.** If the server renders the content
  through a helper (`Str::markdown`, `simple_format`, autolinks) and the
  optimistic HTML is plain text, the morph will visibly "reformat" the
  fragment when the response lands. For rich content, keep the optimistic
  render intentionally simple (e.g. plain paragraph, "Sending…" footer) so
  the transition reads as *adding detail* rather than as a flicker.

## Multiple optimistic actions per submit

Drop several `<x-hwc::optimistic>` siblings into the same form — each becomes
its own Turbo Stream, dispatched in order.

```blade
<form
    data-controller="optimistic--form"
    action="/todos/{{ $todo->id }}"
    method="post"
>
    @csrf
    @method('DELETE')

    {{-- Remove the row --}}
    <x-hwc::optimistic :target="dom_id($todo)" action="remove" />

    {{-- Decrement the counter --}}
    <x-hwc::optimistic target="todos_counter">
        <span id="todos_counter">{{ $todosCount - 1 }}</span>
    </x-hwc::optimistic>

    <button type="submit">Delete</button>
</form>
```

## Tips

- **Use `dom_id()`** (from `emaia/laravel-hotwire-turbo`) to generate stable
  target ids. Never build them from user input.
- **Combine with `view-transition`** for smooth cross-fades between
  optimistic and reconciled state.
- **Publish the controllers** before using:
  `php artisan hotwire:controllers optimistic` (publishes all: form, link,
  dispatch, and the shared `_dispatch.js`).

## Dependencies

The component declares no Stimulus controller dependencies — the trigger
choice is yours. Publish the controller you need:

```bash
php artisan hotwire:controllers optimistic/form   # for forms
php artisan hotwire:controllers optimistic/link   # for links
php artisan hotwire:controllers optimistic        # all (including escape hatch)
```

Shared dependencies (`_dispatch.js`) are published automatically alongside
whichever controller you select.
