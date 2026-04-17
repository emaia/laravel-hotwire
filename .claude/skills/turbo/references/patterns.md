# Turbo Patterns (Laravel)

Common patterns for Turbo Frames and Streams in Laravel applications.

---

## Inline Editing

Edit items in place without page navigation.

### List View

```blade
{{-- resources/views/tasks/index.blade.php --}}
<ul id="tasks">
    @foreach($tasks as $task)
        <x-turbo::frame :id="dom_id($task)">
            @include('tasks._task', ['task' => $task])
        </x-turbo::frame>
    @endforeach
</ul>
```

```blade
{{-- resources/views/tasks/_task.blade.php --}}
<li>
    <span>{{ $task->title }}</span>
    <a href="{{ route('tasks.edit', $task) }}">Edit</a>
</li>
```

### Edit Form

```blade
{{-- resources/views/tasks/edit.blade.php --}}
<x-turbo::frame :id="dom_id($task)">
    <li>
        <form action="{{ route('tasks.update', $task) }}" method="post">
            @csrf @method('PUT')
            <input name="title" value="{{ $task->title }}">
            <button type="submit">Save</button>
            <a href="{{ route('tasks.show', $task) }}">Cancel</a>
        </form>
    </li>
</x-turbo::frame>
```

### Controller

```php
public function edit(Task $task)
{
    return view('tasks.edit', compact('task'));
}

public function update(Request $request, Task $task)
{
    $task->update($request->validated());

    if (request()->wantsTurboStream()) {
        return turbo_stream()->replace(dom_id($task), view('tasks._task_frame', compact('task')));
    }

    return redirect()->route('tasks.index');
}
```

---

## Search with Live Results

Search form with results updating as user types.

```blade
<x-turbo::frame id="search">
    <form action="{{ route('search') }}" method="get" data-turbo-frame="search">
        <input type="search" name="q" value="{{ request('q') }}"
               data-controller="form--autosubmit"
               data-action="input->form--autosubmit#submit">
    </form>

    <ul>
        @foreach($results as $item)
            <li>{{ $item->name }}</li>
        @endforeach
    </ul>
</x-turbo::frame>
```

Uses the package's `form/autosubmit` controller. Publish it with:

```bash
php artisan hotwire:controllers form/autosubmit
```

---

## Tabs

Tab navigation with frame-based content loading.

```blade
<nav>
    <a href="{{ route('tabs.one') }}" data-turbo-frame="tab-content">Tab 1</a>
    <a href="{{ route('tabs.two') }}" data-turbo-frame="tab-content">Tab 2</a>
</nav>

<x-turbo::frame id="tab-content" data-turbo-action="advance">
    @include('tabs._tab_one')
</x-turbo::frame>
```

Each tab route returns a full page with a matching frame:

```blade
<x-turbo::frame id="tab-content">
    {{-- Tab-specific content --}}
</x-turbo::frame>
```

---

## Modal Dialog

Load modal content via frame. Uses the package's `<x-hwc::modal>` component with the `dialog--modal` controller.

```blade
{{-- Empty modal frame --}}
<x-turbo::frame id="modal"></x-turbo::frame>

{{-- Link to open modal --}}
<a href="{{ route('items.delete-confirm', $item) }}"
   data-turbo-frame="modal">
    Delete
</a>
```

```blade
{{-- resources/views/items/delete-confirm.blade.php --}}
<x-turbo::frame id="modal">
    <x-hwc::modal>
        <h2>Confirm Delete</h2>
        <p>Delete "{{ $item->name }}"?</p>

        <form action="{{ route('items.destroy', $item) }}" method="post">
            @csrf @method('DELETE')
            <button type="submit">Delete</button>
        </form>
    </x-hwc::modal>
</x-turbo::frame>
```

---

## Pagination

Paginate within a frame, optionally updating URL.

```blade
<x-turbo::frame id="items" data-turbo-action="advance">
    <ul>
        @foreach($items as $item)
            <li>{{ $item->name }}</li>
        @endforeach
    </ul>

    {{ $items->links() }}
</x-turbo::frame>
```

Laravel's paginator links work inside frames automatically.

---

## Form with Multiple Updates (Streams)

Form submission that updates multiple page sections.

### Controller

```php
public function store(Request $request)
{
    $comment = Comment::create($request->validated());

    if (request()->wantsTurboStream()) {
        return turbo_stream()
            ->append('comments', view('comments._comment', compact('comment')))
            ->update('comment-count', '<span>' . Comment::count() . '</span>')
            ->replace('comment-form', view('comments._form'));
    }

    return redirect()->route('comments.index');
}
```

### Stream View Alternative

```php
// Controller
return turbo_stream_view('comments.streams.created', compact('comment', 'count'));
```

```blade
{{-- resources/views/comments/streams/created.blade.php --}}
<x-turbo::stream action="append" target="comments">
    @include('comments._comment', ['comment' => $comment])
</x-turbo::stream>

<x-turbo::stream action="update" target="comment-count">
    <span>{{ $count }}</span>
</x-turbo::stream>

<x-turbo::stream action="replace" target="comment-form">
    @include('comments._form')
</x-turbo::stream>
```

---

## Delete with Confirmation

Remove item with stream response.

```blade
<div id="@domid($item)">
    <span>{{ $item->name }}</span>
    <form action="{{ route('items.destroy', $item) }}"
          method="post"
          data-turbo-confirm="Delete this item?">
        @csrf @method('DELETE')
        <button type="submit">Delete</button>
    </form>
</div>
```

### Controller

```php
public function destroy(Item $item)
{
    $item->delete();

    if (request()->wantsTurboStream()) {
        return turbo_stream()->remove(dom_id($item));
    }

    return redirect()->route('items.index');
}
```

---

## Flash Messages with Streams

Uses the package's `<x-hwc::flash-message>` component.

```php
// Controller: redirect with session flash (Turbo Drive)
return redirect()->route('items.index')->with('success', 'Item saved!');

// Or via stream for frame/stream responses
return turbo_stream()
    ->append('flash-container', view('components.flash', ['message' => 'Saved!', 'type' => 'success']));
```

---

## Lazy Loading Sections

Load heavy content after initial page load.

```blade
<h1>Dashboard</h1>

{{-- Stats load asynchronously --}}
<x-turbo::frame id="stats" src="{{ route('dashboard.stats') }}" loading="lazy">
    <div class="skeleton">Loading stats...</div>
</x-turbo::frame>

{{-- Activity loads when scrolled into view --}}
<x-turbo::frame id="activity" src="{{ route('dashboard.activity') }}" loading="lazy">
    <div class="skeleton">Loading activity...</div>
</x-turbo::frame>
```

---

## Form Validation Errors

Show validation errors without full page reload.

### Using TurboFormRequest

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

class UpdateProfileRequest extends TurboFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ];
    }
}
```

When validation fails inside a Turbo Frame, it redirects to the previous URL so the frame re-renders with errors.

### Manual Handling

```php
public function store(Request $request)
{
    $validated = $request->validate(['title' => 'required']);

    // On validation failure, Laravel automatically redirects back
    // Turbo Frame re-renders the form with errors

    $item = Item::create($validated);

    if (request()->wantsTurboStream()) {
        return turbo_stream()->append('items', view('items._item', compact('item')));
    }

    return redirect()->route('items.index');
}
```
