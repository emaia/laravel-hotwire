# Optimistic

Declare an optimistic Turbo Stream action inline, next to the element it should
mutate. Works with **any** Turbo trigger — form submissions, frame links,
method links — by pairing the `optimistic--dispatch` core controller with the
trigger wrapper of your choice.

## Why

Server-rendered UIs wait for a round-trip before reflecting user intent —
typically 150–300ms even on fast networks, which is past the "feels instant"
threshold. For actions whose outcome is predictable (toggles, inserts, deletes,
navigations), we can pre-render the success state client-side and let the
server *reconcile* via a Turbo 8 morph on the real response.

## Architecture

```
<x-hwc::optimistic>            ← emits <template data-optimistic-stream …>
        ↑ (declares dependency)
optimistic--dispatch           ← scans templates, materialises <turbo-stream>
        ↑ dispatch()
   ┌────┴─────────┐
form--optimistic   link--optimistic
(turbo:submit-start)   (click)
```

The component declares only the **core** controller as its dependency. You add
the **trigger wrapper** (`form--optimistic` or `link--optimistic`) to the host
element manually, in the same `data-controller` attribute as the core.

## Basic usage — form submission

```html
<form
    data-controller="optimistic--dispatch form--optimistic"
    action="/posts/1/favorite"
    method="post"
>
    @csrf

    <x-hwc::optimistic target="post_1_favorite">
        <button class="favorited">❤️ Favorited</button>
    </x-hwc::optimistic>

    <div id="post_1_favorite">
        <button>🤍 Favorite</button>
    </div>
</form>
```

## Basic usage — frame link with skeleton

```html
<a
    href="/posts/42"
    data-turbo-frame="detail"
    data-controller="optimistic--dispatch link--optimistic"
>
    Ver detalhes

    <x-hwc::optimistic target="detail" action="update">
        <div class="animate-pulse p-4">Carregando…</div>
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

<div id="{{ dom_id($post, 'favorite') }}" class="inline-flex items-center gap-2">
    <form
        data-controller="optimistic--dispatch form--optimistic"
        action="{{ route('posts.favorite.toggle', $post) }}"
        method="post"
    >
        @csrf
        @method($post->isFavoritedBy(auth()->user()) ? 'DELETE' : 'POST')

        {{-- Optimistic action: replace the whole wrapper with the inverted state --}}
        <x-hwc::optimistic :target="dom_id($post, 'favorite')">
            <div id="{{ dom_id($post, 'favorite') }}" class="inline-flex items-center gap-2">
                @if ($post->isFavoritedBy(auth()->user()))
                    <button type="button" class="text-slate-400">🤍 Favorite</button>
                @else
                    <button type="button" class="text-rose-500">❤️ Favorited</button>
                @endif
                <span class="text-sm text-slate-500">
                    {{ $post->favorites_count + ($post->isFavoritedBy(auth()->user()) ? -1 : 1) }}
                </span>
            </div>
        </x-hwc::optimistic>

        <button type="submit" class="{{ $post->isFavoritedBy(auth()->user()) ? 'text-rose-500' : 'text-slate-400' }}">
            {{ $post->isFavoritedBy(auth()->user()) ? '❤️ Favorited' : '🤍 Favorite' }}
        </button>
        <span class="text-sm text-slate-500">{{ $post->favorites_count }}</span>
    </form>
</div>
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
public function store(Request $request, Post $post)
{
    try {
        $this->authorize('favorite', $post);
    } catch (\Throwable $e) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->append('toasts', view('components.hwc.flash-message', [
                'message' => 'Could not favorite this post.',
                'type' => 'error',
            ]))
            ->withResponse(403);
    }

    // ...
}
```

## Multiple optimistic actions per submit

Drop several `<x-hwc::optimistic>` siblings into the same form — each becomes
its own Turbo Stream, dispatched in order.

```blade
<form
    data-controller="optimistic--dispatch form--optimistic"
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
- **Combine with `frame--view-transition`** for smooth cross-fades between
  optimistic and reconciled state.
- **Publish the controllers** before using:
  `php artisan hotwire:controllers optimistic/dispatch form/optimistic link/optimistic`
- For roadmap items (rollback opt-in, ULID inserts, Sortable persist,
  prefetch+optimistic, broadcast-aware dispatch) see
  [`docs/roadmap.md`](../../roadmap.md).

## Dependencies

Declared via `HasStimulusControllers`:

- `optimistic--dispatch` (core)

The trigger wrapper (`form--optimistic` or `link--optimistic`) is added by you
on the host element. Run `php artisan hotwire:check` to ensure the core
dispatcher is published.
