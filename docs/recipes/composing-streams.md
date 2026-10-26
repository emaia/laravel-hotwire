# Composing streams

Describe a complete UI transition in a single response by chaining Turbo Stream operations.
Combined with the [`toast()`](../components/toast.md#the-toast-stream-macro) macro, controller
actions stay small and declarative.

## Built-in macros

`toast()` ships with the package — nothing to register. See
[its parameters](../components/toast.md#the-toast-stream-macro), including `target` for a viewport with a
custom id.

[`closeOverlay($origin)`](./overlay-responses.md) closes the specified Modal, Sheet or Drawer instance without erasing
reusable markup. Both macros preserve any application-defined implementation rather than replacing it.

## Common compositions

### Save → update a row → close → toast

Update the affected row instead of refreshing other forms that may hold drafts. In this example the route's Frame
presentation is an overlay, while ordinary page submissions keep their redirect:

```php
public function update(UpdatePostRequest $request, Post $post)
{
    $post->update($request->validated());

    $origin = $request->overlayOrigin();

    if ($origin !== null && $request->wantsTurboStream()) {
        return turbo_stream()
            ->replace(dom_id($post), view('posts._row', compact('post')))
            ->closeOverlay($origin)
            ->toast('success', 'Post updated');
    }

    return redirect()
        ->route('posts.show', $post)
        ->with('status', 'Post updated');
}
```

Turbo starts the actions in order, but the next action does not wait for the close animation or a page visit to finish.
Use a global `refresh(method: 'morph')` only when discarding other open drafts is intentional. It remains native to Turbo,
including request-id suppression; it does not implicitly close a single overlay or guarantee that a toast survives a
later page replacement. The redirect keeps direct and non-JavaScript submissions usable.

### Optimistic action rejected → revert + explain

Pair `refresh` (which morphs back to the real state) with a toast to explain the rollback:

```php
use Illuminate\Auth\Access\AuthorizationException;

public function favorite(Request $request, Post $post)
{
    try {
        $this->authorize('favorite', $post);
    } catch (AuthorizationException $exception) {
        if ($request->wantsTurboStream()) {
            return turbo_stream()
                ->refresh(method: 'morph')
                ->toast('error', 'Could not favorite this post.')
                ->withResponse(403);
        }

        throw $exception;
    }

    $post->favoriteFor($request->user());

    if ($request->wantsTurboStream()) {
        return turbo_stream()->refresh(method: 'morph');
    }

    return redirect()->route('posts.show', $post);
}
```

### Validation failure → keep modal open, surface errors

Don't compose anything special. Let validation return its normal redirect/error response so the
frame re-renders with errors and the modal stays open. When the frame GET URL differs from the form's
mutation URL, use `track-frame-src` and have the form request extend `TurboFormRequest`:

```blade
<hw:form :action="route('posts.update', $post)" method="patch" track-frame-src>
    {{-- fields --}}
</hw:form>
```

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

final class UpdatePostRequest extends TurboFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }
}
```

`track-frame-src` sends the URL that rendered the form; `TurboFormRequest` validates that source and
uses it for the validation redirect. [`<hw:toaster>`](../components/toaster.md) then picks up the
first validation error from the session and shows it as a toast, with nothing else to wire up.

### Append a row → reset the form → toast

Multiple stream actions in one response:

```php
public function store(StoreCommentRequest $request, Post $post)
{
    $comment = $post->comments()->create($request->validated());

    if ($request->wantsTurboStream()) {
        return turbo_stream()
            ->append('comments', view('comments.row', compact('comment')))
            ->replace('comment-form', view('comments.form', compact('post')))
            ->toast('success', 'Comment posted');
    }

    return redirect()
        ->route('posts.show', $post)
        ->with('status', 'Comment posted');
}
```

`StoreCommentRequest` is a Laravel form request, so `validated()` is available. If the comment form
is loaded from a different frame GET URL, extend `TurboFormRequest` and add `track-frame-src` to that
form just as in the modal example above.

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
