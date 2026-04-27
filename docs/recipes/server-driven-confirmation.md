# Server-driven confirmation

Two-step destructive actions where the **server** decides what the confirmation looks like — counts
of dependent records, policy-based warnings, type-the-name guards. The first request paints the
modal, the second performs the action.

## The pattern

1. User clicks **Delete** on a row.
2. Link is frame-scoped → server returns a confirmation view inside the modal frame.
3. The modal opens with server-computed context ("This task has 47 sub-tasks that will be deleted").
4. User confirms → form POSTs `DELETE` → server returns a Turbo Stream that removes the row, closes
   the modal, and flashes a toast.

Two round-trips, zero client-side conditional logic.

## When to use this vs `confirm-dialog`

| Scenario                                                              | Use                                              |
|-----------------------------------------------------------------------|--------------------------------------------------|
| "Are you sure?" with no extra context                                 | [`confirm-dialog`](../components/confirm-dialog.md) (one round-trip) |
| Confirmation copy depends on server data (counts, related records)    | This recipe                                      |
| Server decides *whether* confirmation is needed (policy, thresholds)  | This recipe                                      |
| Audit/security: the decision must be re-validated server-side         | This recipe                                      |
| Type-the-resource-name guard fed by server-side normalization rules   | This recipe                                      |

`confirm-dialog` is faster (no extra request) and ergonomic for the common case. Reach for the
server-driven path only when the confirmation itself needs server context.

## Setup

Assumes the [layout-shared modal](./modal-patterns.md#pattern-2--layout-shared-modal) and
[frame-or-page](./frame-or-page.md) recipes — one `<x-hwc::modal id="modal">` in the layout, one
`<turbo-frame id="modal">` inside it.

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
<a href="{{ route('tasks.confirm-destroy', $task) }}"
   data-turbo-frame="modal">
    Delete
</a>
```

`data-turbo-frame="modal"` makes Turbo issue a frame-scoped request. The modal controller
auto-fires `showLoading` because it listens globally for `a[data-turbo-frame="modal"]` clicks.

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
<x-layouts.modal-base>
    <div class="p-6 space-y-4">
        <h2 class="text-xl font-semibold">Delete task?</h2>

        <p>
            <strong>{{ $task->title }}</strong> will be permanently deleted.
            @if ($dependentCount > 0)
                This will also delete <strong>{{ $dependentCount }}</strong>
                sub-{{ Str::plural('task', $dependentCount) }}.
            @endif
        </p>

        <div class="flex justify-end gap-2">
            <button type="button" data-action="modal#close">Cancel</button>

            <form method="POST" action="{{ route('tasks.destroy', $task) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600">Delete</button>
            </form>
        </div>
    </div>
</x-layouts.modal-base>
```

The `modal-base` layout (from the [frame-or-page recipe](./frame-or-page.md)) wraps the content in a
`<turbo-frame id="modal">` when called via Turbo Frame, or in the dashboard layout otherwise. The
confirmation page is bookmarkable — refreshing it shows a standalone confirmation page.

### 5. The destroy action

```php
public function destroy(Task $task)
{
    $this->authorize('delete', $task);

    $task->delete();

    return turbo_stream()
        ->remove(dom_id($task))
        ->closeModal('modal')
        ->flash('success', 'Task deleted');
}
```

One stream describes the whole transition: row vanishes, modal closes, toast appears.

## Variants

### Conditional confirmation

Skip the modal entirely when there's nothing to confirm. The server decides:

```php
public function confirmDestroy(Task $task)
{
    $this->authorize('delete', $task);

    if ($task->subtasks()->doesntExist() && ! $task->is_critical) {
        return $this->destroy($task);
    }

    return view('tasks.confirm-destroy', [
        'task' => $task,
        'dependentCount' => $task->subtasks()->count(),
    ]);
}
```

Cheap deletes go straight through; risky ones get a modal. Clients can't tell the difference at the
trigger site — the link is the same either way.

### Type-the-name guard

For high-stakes deletions, require the user to type the resource name:

```blade
<form method="POST" action="{{ route('projects.destroy', $project) }}"
      data-controller="confirm-typed"
      data-confirm-typed-expected-value="{{ $project->slug }}">
    @csrf
    @method('DELETE')

    <label>
        Type <code>{{ $project->slug }}</code> to confirm:
        <input type="text" data-confirm-typed-target="input" autofocus>
    </label>

    <button type="submit"
            data-confirm-typed-target="submit"
            disabled
            class="text-red-600">
        Delete project
    </button>
</form>
```

The slug — and the rules for what counts as a match — are server-computed, so this pattern has to
live behind a server-rendered confirmation view.

### Reusing the confirmation view

The same confirmation view serves frame and full-page calls. A user who refreshes the modal URL gets
a standalone confirmation page with the same form — submit still works because the action posts to a
real route, and the response is a Turbo Stream that Turbo Drive applies to the page it lands on.

## Trade-offs

- **Two round-trips** instead of one. Acceptable for destructive actions where the round-trip cost
  is dwarfed by the cost of getting it wrong.
- **Server has to model the "needs confirmation?" decision.** That's usually where it belongs anyway
  — same place that enforces the authorization.
- **The confirmation URL is real and shareable.** Mostly a feature (refreshable, bookmarkable for
  audit flows) but worth knowing.

## See also

- [`<x-hwc::confirm-dialog>`](../components/confirm-dialog.md) — client-side confirmation for
  the trivial case.
- [Modal patterns](./modal-patterns.md) — the layout-shared setup this recipe builds on.
- [Frame-or-page views](./frame-or-page.md) — the layout that makes the confirmation view dual-mode.
- [Server-driven modals](./server-driven-modals.md) — opening and closing modals from the server.
- [Composing streams](./composing-streams.md) — chaining `remove + closeModal + flash`.
