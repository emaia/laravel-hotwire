# Server-driven confirmation

Two-step destructive actions where the **server** decides what the confirmation looks like — counts
of dependent records, policy-based warnings, type-the-name guards. The first request paints the
modal, the second performs the action.

## The pattern

1. User clicks **Delete** on a row.
2. A frame-scoped GET asks the server for a read-only confirmation view.
3. The modal opens with server-computed context ("This task has 47 sub-tasks that will be deleted").
4. User confirms → an explicit `DELETE` form performs the mutation → the server returns a Turbo
   Stream that removes the row, closes the modal, and shows a toast.

Two round-trips, zero client-side conditional logic.

## When to use this vs `alert-dialog`

| Scenario                                                             | Use                                                              |
| -------------------------------------------------------------------- | ---------------------------------------------------------------- |
| "Are you sure?" with no extra context                                | [`alert-dialog`](../components/alert-dialog.md) (one round-trip) |
| Confirmation copy depends on server data (counts, related records)   | This recipe                                                      |
| Server decides _whether_ confirmation is needed (policy, thresholds) | This recipe                                                      |
| Audit/security: the decision must be re-validated server-side        | This recipe                                                      |
| Type-the-resource-name guard fed by server-side normalization rules  | This recipe                                                      |

`alert-dialog` is faster (no extra request) and ergonomic for the common case. Reach for the
server-driven path only when the confirmation itself needs server context.

## Setup

Assumes the [layout-shared modal](./modal-patterns.md#pattern-2--layout-shared-modal) and
[frame-or-page](./frame-or-page.md) recipes — one `<hw:modal frame="modal">` host in the
layout.

### 1. Routes

```php
// routes/web.php
Route::get('tasks/{task}/confirm-destroy', [TaskController::class, 'confirmDestroy'])
    ->name('tasks.confirm-destroy');

Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
    ->name('tasks.destroy');
```

### 2. The trigger

```blade
<a href="{{ route('tasks.confirm-destroy', $task) }}" data-turbo-frame="modal">Delete</a>
```

`data-turbo-frame="modal"` makes Turbo issue a frame-scoped request. The modal controller injects
the loading template automatically because it listens globally for `a[data-turbo-frame="modal"]` clicks.

### 3. The confirmation action

```php
public function confirmDestroy(Task $task)
{
    $this->authorize('delete', $task);

    return view('tasks.confirm-destroy', [
        'task' => $task,
        'dependentCount' => $task->subtasks()->count(),
    ]);
}
```

### 4. The confirmation view

```blade
{{-- resources/views/tasks/confirm-destroy.blade.php --}}
<hw:frame-or-page frame="modal" layout="dashboard">
    <hw:modal.header>
        <hw:modal.title>Delete task?</hw:modal.title>
        <hw:modal.description>This action cannot be undone.</hw:modal.description>
    </hw:modal.header>

    <p>
        <strong>{{ $task->title }}</strong>
        will be permanently deleted.
        @if ($dependentCount > 0)
            This will also delete
            <strong>{{ $dependentCount }}</strong>
            sub-{{ Str::plural('task', $dependentCount) }}.
        @endif
    </p>

    <div class="flex justify-end gap-2">
        <hw:frame-or-page.frame>
            <hw:modal.close>Cancel</hw:modal.close>
        </hw:frame-or-page.frame>

        <hw:frame-or-page.page>
            <hw:button as="a" :href="route('tasks.index')" variant="outline">Cancel</hw:button>
        </hw:frame-or-page.page>

        <hw:form :action="route('tasks.destroy', $task)" method="delete">
            <hw:button type="submit" variant="destructive">Delete</hw:button>
        </hw:form>
    </div>
</hw:frame-or-page>
```

`<hw:frame-or-page>` wraps a frame request in `<turbo-frame id="modal">`. That response lands in the
content host already rendered by `<hw:modal frame="modal">`; the response must not add another
`<hw:modal.content>`. On direct navigation, the same view renders in the dashboard layout. The
`<hw:form>` emits the CSRF token and method override for the explicit `DELETE` action.

### 5. The destroy action

```php
public function destroy(Request $request, Task $task)
{
    $this->authorize('delete', $task);

    $task->delete();

    if ($request->wasFromTurboFrame('modal') && $request->wantsTurboStream()) {
        return turbo_stream()
            ->remove(dom_id($task))
            ->update('modal')
            ->toast('success', 'Task deleted');
    }

    return redirect()
        ->route('tasks.index')
        ->with('status', 'Task deleted');
}
```

For the modal submission, one stream describes the whole transition: the row vanishes, the modal
frame is cleared, and the toast appears. Direct and non-JavaScript submissions follow the normal
redirect fallback.

## Variants

### Conditional confirmation

Never call `destroy()` from `confirmDestroy()`: the confirmation route is GET and must stay
read-only. If server-rendered list data says an item does not need the extra confirmation screen,
render a `DELETE` form at the trigger site instead of the frame link:

```blade
@if ($task->requiresDeletionConfirmation())
    <a href="{{ route('tasks.confirm-destroy', $task) }}" data-turbo-frame="modal">Delete</a>
@else
    <hw:form :action="route('tasks.destroy', $task)" method="delete">
        <hw:button type="submit" variant="destructive">Delete</hw:button>
    </hw:form>
@endif
```

The `DELETE` action must still repeat authorization and any destructive precondition checks. Treat
the GET response as an explanation of the current state, never as permission to mutate later.

### Type-the-name guard

For high-stakes deletions, require the user to type the resource name:

```blade
<hw:form :action="route('projects.destroy', $project)" method="delete" track-frame-src>
    <hw:field
        name="confirmation"
        label="Type {{ $project->slug }} to confirm"
        description="The value must match exactly."
        required
    >
        <hw:input autofocus autocomplete="off" />
    </hw:field>

    <hw:button type="submit" variant="destructive">Delete project</hw:button>
</hw:form>
```

Validate the guard in a form request that extends `TurboFormRequest`:

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;
use Illuminate\Validation\Rule;

final class DestroyProjectRequest extends TurboFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('project'));
    }

    public function rules(): array
    {
        return [
            'confirmation' => [
                'required',
                Rule::in([$this->route('project')->slug]),
            ],
        ];
    }
}
```

The form submits to the mutation URL, not the confirmation GET URL. `track-frame-src` records the
GET URL that rendered the form, and `TurboFormRequest` redirects validation failures there so the
matching frame is rendered again with errors. Type the destroy action with `DestroyProjectRequest`;
the slug and matching rule then remain server-enforced.

### Reusing the confirmation view

The same confirmation view serves frame and full-page calls. A user who refreshes the confirmation
URL gets a standalone page with the same real `DELETE` form. A frame submission receives the stream
composition; a standalone submission follows the redirect fallback.

## Trade-offs

- **Two round-trips** instead of one. Acceptable for destructive actions where the round-trip cost
  is dwarfed by the cost of getting it wrong.
- **Server has to model the "needs confirmation?" decision.** That's usually where it belongs anyway
  — same place that enforces the authorization.
- **The confirmation URL is real and shareable.** Mostly a feature (refreshable, bookmarkable for
  audit flows) but worth knowing.

## See also

- [`<hw:alert-dialog>`](../components/alert-dialog.md) — client-side confirmation for
  the trivial case.
- [Modal patterns](./modal-patterns.md) — the layout-shared setup this recipe builds on.
- [Frame-or-page views](./frame-or-page.md) — the layout that makes the confirmation view dual-mode.
- [Server-driven modals](./server-driven-modals.md) — opening and closing modals from the server.
- [Composing streams](./composing-streams.md) — chaining `remove + update + toast`.
