# Composing streams

Describe a complete UI transition in a single response by chaining Turbo Stream operations.
Combined with the [`flash`](../components/flash-message/readme.md#convenience-macro) and
[`closeModal`](../components/modal/readme.md#convenience-macro) macros, controller actions stay
small and declarative.

## The macros

Register them once in `AppServiceProvider::boot()`:

```php
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Support\Facades\Blade;

TurboStreamBuilder::macro('flash', function (string $type, string $message, ?string $description = null) {
    return $this->append('flash-container', Blade::render(
        '<x-hwc::flash-message :type="$type" :message="$message" :description="$description" />',
        compact('type', 'message', 'description'),
    ));
});

TurboStreamBuilder::macro('closeModal', function (string $id) {
    return $this->append($id, '<span data-controller="modal-auto-close"></span>');
});
```

## Common compositions

### Save → close → refresh → toast

The canonical "modal form succeeded" response:

```php
public function update(Request $request, Post $post)
{
    $request->validate([...]);
    $post->update($request->validated());

    return turbo_stream()
        ->refresh(method: 'morph')
        ->closeModal('modal')
        ->flash('success', 'Post updated');
}
```

Order matters less than you might think — Turbo applies streams in order, but `refresh` morphs the
DOM in place, the modal close runs on the morphed modal, and the flash appends to the persistent
flash container.

### Optimistic action rejected → revert + explain

Pair `refresh` (which morphs back to the real state) with a flash to explain the rollback:

```php
public function favorite(Request $request, Post $post)
{
    try {
        $this->authorize('favorite', $post);
    } catch (\Throwable $e) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->flash('error', 'Could not favorite this post.')
            ->withResponse(403);
    }

    $post->favoriteFor($request->user());
    return turbo_stream()->refresh(method: 'morph');
}
```

### Validation failure → keep modal open, surface errors

Don't compose anything special — return a normal redirect/error response. The Turbo Frame holding
the form re-renders with the validation errors inside, the modal stays open, and the
[`<x-hwc::flash-message>`](../components/flash-message/readme.md) component picks up the first
validation error from the session and shows a toast.

### Append a row → highlight it → toast

Multiple stream actions in one response:

```php
public function store(Request $request)
{
    $comment = Comment::create($request->validated());

    return turbo_stream()
        ->append('comments', view('comments.row', compact('comment')))
        ->replace('comment-form', view('comments.form'))
        ->flash('success', 'Comment posted');
}
```

## Patterns to avoid

- **Don't `redirect()` from a Turbo Stream action.** Stream responses are processed in place — a
  redirect breaks the stream contract. Use `refresh()` instead.
- **Don't compose streams that target the same id with conflicting operations** (e.g. `update` then
  `append` on the same target in the same response). Behavior is technically defined but hard to
  reason about.
- **Don't put server-side logic inside Blade templates rendered by macros.** Keep the macro template
  a thin wrapper around the component; do the real work in the controller before composing.

## See also

- [`flash` macro](../components/flash-message/readme.md#convenience-macro)
- [`closeModal` macro](../components/modal/readme.md#convenience-macro)
- [Server-driven modals](./server-driven-modals.md)
- [Frame-or-page views](./frame-or-page.md)
