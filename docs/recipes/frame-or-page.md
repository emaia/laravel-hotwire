# Frame-or-page views

Render the **same view** as either a full-page response or a Turbo Frame modal payload, depending on
how the user reached it. One controller, one view, no duplication.

## The problem

A typical CRUD flow has two ways to open a record's "edit" form:

- From the list page → users expect a **modal** (no full navigation, fast feedback).
- From a direct link / bookmark / refresh → users expect a **standalone page** (URL is shareable,
  back button works, refresh keeps you on the form).

Natively, that's two views and two controllers — or one view with branching logic at every level.

## The pattern

Push the branching to a single layout component. The controller stays oblivious to whether the
caller is a frame or a page.

### 1. The layout component

```blade
{{-- resources/views/components/layouts/modal-base.blade.php --}}
@if (request()->wasFromTurboFrame('modal'))
    <turbo-frame id="modal">
        {{ $slot }}
    </turbo-frame>
@else
    <x-layouts.dashboard>
        {{ $slot }}
    </x-layouts.dashboard>
@endif
```

`request()->wasFromTurboFrame('modal')` is provided by `emaia/laravel-hotwire-turbo` — it checks the
`Turbo-Frame` request header.

### 2. The view uses the layout

```blade
{{-- resources/views/users/edit.blade.php --}}
<x-layouts.modal-base>
    <div class="modal:p-5">
        <x-headings.main-section>Change password</x-headings.main-section>

        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf
            @method('PATCH')
            {{-- fields --}}
            <button type="submit">Save</button>
        </form>
    </div>
</x-layouts.modal-base>
```

The view itself doesn't know — and doesn't care — whether it's rendered as a modal or as a page.

### 3. The dashboard layout has the modal host

```blade
{{-- resources/views/components/layouts/dashboard.blade.php --}}
<!DOCTYPE html>
<html>
<head>...</head>
<body>
    <header>...</header>
    <main>{{ $slot }}</main>

    <x-hwc::modal frame="modal">
        <x-slot:loading_template>
            <div class="flex items-center justify-center p-12">
                <span>Loading...</span>
            </div>
        </x-slot:loading_template>
    </x-hwc::modal>

    <x-hwc::flash-container />
    <x-hwc::flash-message />
</body>
</html>
```

The modal host lives in the dashboard layout — available on every page. `frame="modal"` renders the
receiving `<turbo-frame id="modal" data-modal-target="dynamicContent">`. When the frame receives
content, the modal's content observer opens it automatically.

### 4. Links choose the experience

Trigger the modal:

```blade
<a href="{{ route('users.edit', $user) }}" data-turbo-frame="modal">
    Change password
</a>
```

`data-turbo-frame="modal"` makes Turbo issue the request scoped to the frame, sending the
`Turbo-Frame: modal` header. The layout sees it and renders only the frame content. The frame
rendered by `<x-hwc::modal frame="modal">` receives the response, the modal observer fires, and the
modal opens.

The modal controller listens globally for clicks on `a[data-turbo-frame="<its frame id>"]`, so the
loading template fires automatically — no `data-action` required, even when the link lives outside
the modal element.

Trigger the full page (no frame attribute):

```blade
<a href="{{ route('users.edit', $user) }}">Change password</a>
```

The same controller, the same view, but now the layout renders the dashboard wrapper and the user
gets a standalone page.

## Closing on success

When the form submits successfully, you want to close the modal **and** refresh the underlying page.
Return a Turbo Stream from the controller:

```php
public function update(Request $request, User $user)
{
    $request->validate([...]);
    $user->update($request->only('password'));

    return turbo_stream()
        ->refresh(method: 'morph')
        ->update('modal')
        ->flash('success', 'Password updated');
}
```

Requires the [`flash` macro](../components/flash-message.md#convenience-macro) registered in your
service provider.

## Why this works well

- **One URL per resource** — the edit form lives at `/users/{user}/edit` whether it opens as a modal
  or a page.
- **Refresh-safe** — refreshing the page on the modal route re-renders as the standalone page (no
  broken state).
- **Bookmark/share-friendly** — copying the URL produces the standalone page experience.
- **Progressive enhancement** — without JS, links navigate normally to the standalone page.
- **No view duplication** — one Blade file covers both presentations.

## Trade-offs

- The modal and frame must be present on **every** page that triggers modals — typically by living in
  the shared layout.
- `data-turbo-frame="modal"` must be set on every link/form that should open as a modal. Easy to forget.
- The frame id is global — you can't have two modal frames on the same page without renaming. (You
  rarely want two anyway.)

## See also

- [`<x-hwc::modal>`](../components/modal.md) — the modal primitive.
- [`modal` controller](../controllers/modal.md) — dynamic content observer internals.
- [Server-driven modals](./server-driven-modals.md) — closing and replacing content from the server.
- [Composing streams](./composing-streams.md) — chain `refresh + update + flash` for clean
  success responses.
