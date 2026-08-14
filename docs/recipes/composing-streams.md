# Composing streams

Describe a complete UI transition in a single response by chaining Turbo Stream operations.
Combined with the [`toast()`](../components/toast.md#the-toast-stream-macro) macro, controller
actions stay small and declarative.

## The macro

`toast()` ships with the package — nothing to register. See
[its parameters](../components/toast.md#the-toast-stream-macro), including `target` for a viewport with a
custom id.

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
        ->update('modal')
        ->toast('success', 'Post updated');
}
```

Order matters less than you might think — Turbo applies streams in order, but `refresh` morphs the
DOM in place, the modal frame is cleared, and the toast appends to the persistent toaster.

### Optimistic action rejected → revert + explain

Pair `refresh` (which morphs back to the real state) with a toast to explain the rollback:

```php
public function favorite(Request $request, Post $post)
{
    try {
        $this->authorize('favorite', $post);
    } catch (\Throwable $e) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->toast('error', 'Could not favorite this post.')
            ->withResponse(403);
    }

    $post->favoriteFor($request->user());
    return turbo_stream()->refresh(method: 'morph');
}
```

### Validation failure → keep modal open, surface errors

Don't compose anything special — return a normal redirect/error response. The Turbo Frame holding
the form re-renders with the validation errors inside, the modal stays open, and the
[`<hw:toast>`](../components/toast.md) component picks up the first
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
        ->toast('success', 'Comment posted');
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

- [`toast()` stream macro](../components/toast.md#the-toast-stream-macro)
- [Server-driven modals](./server-driven-modals.md)
- [Frame-or-page views](./frame-or-page.md)
