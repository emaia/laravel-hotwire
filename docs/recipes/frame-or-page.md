# Frame-or-page views

Render one view as either a Turbo Frame payload or a standalone page, depending on how the user reached it. The URL,
controller and form stay the same in both contexts.

## Quick start

Wrap the shared view in `<hw:frame-or-page>` and declare the receiving frame and full-page layout:

```blade
{{-- resources/views/users/edit.blade.php --}}
<hw:frame-or-page frame="modal" layout="dashboard">
    <h1>Change password</h1>

    <hw:form :action="route('users.update', $user)" method="patch" track-frame-src>
        <hw:field name="password" label="New password">
            <hw:input name="password" type="password" />
        </hw:field>

        <hw:button type="submit">Save</hw:button>
    </hw:form>
</hw:frame-or-page>
```

A request carrying `Turbo-Frame: modal` returns only `<turbo-frame id="modal">`. Direct navigation renders the slot
inside `<x-layouts.dashboard>`, producing a refresh-safe and bookmarkable page.

Simple layout names resolve ergonomically: `layout="dashboard"` uses `dashboard` when that component exists, otherwise
it tries `layouts.dashboard`. See the [component reference](../components/frame-or-page.md) for multiple frames, lazy
context branches, model-aware frame ids and forwarded attributes.

## Add the frame host

Place one receiving modal in the shared dashboard layout:

```blade
{{-- resources/views/components/layouts/dashboard.blade.php --}}
<!DOCTYPE html>
<html>
    <head>
        ...
    </head>
    <body>
        <header>...</header>
        <main>{{ $slot }}</main>

        <hw:modal frame="modal">
            <x-slot:loading_template>
                <div class="flex items-center justify-center p-12">Loading...</div>
            </x-slot>
        </hw:modal>

        <hw:toaster />
    </body>
</html>
```

The host is available from every page using that layout. When its frame receives content, the modal opens
automatically. The loading template also activates for matching frame links outside the modal; no `data-action` is
required.

## Choose the presentation

Target the host to open the form in the modal:

```blade
<a href="{{ route('users.edit', $user) }}" data-turbo-frame="modal">Change password</a>
```

Omit `data-turbo-frame` for a regular page navigation:

```blade
<a href="{{ route('users.edit', $user) }}">Change password</a>
```

Both links reach the same route and render the same view. Only the request context changes its outer presentation.

## Close on success

After a successful frame submission, refresh the underlying page, clear the modal frame and show feedback:

```php
public function update(UpdateUserRequest $request, User $user)
{
    $user->update($request->validated());

    if ($request->turboFrameId() === 'modal' && $request->wantsTurboStream()) {
        return turbo_stream()
            ->refresh(method: 'morph')
            ->update('modal')
            ->toast('success', 'Password updated');
    }

    return redirect()
        ->route('users.edit', $user)
        ->with('status', 'Password updated');
}
```

Have `UpdateUserRequest` extend `TurboFormRequest`. Together with `track-frame-src`, validation failures redirect to the
GET URL that rendered the frame instead of the mutation URL or underlying page. The normal redirect keeps direct and
non-JavaScript submissions usable.

The [`toast()` stream macro](../components/toast.md#the-toast-stream-macro) is registered by Laravel Hotwire.

## How it works

`<hw:frame-or-page>` packages a small request-header branch. The equivalent lower-level layout looks like this:

```blade
{{-- resources/views/components/layouts/modal-base.blade.php --}}
@if (request()->turboFrameId() === 'modal')
    <hw:frame id="modal">
        {{ $slot }}
    </hw:frame>
@else
    <x-layouts.dashboard>
        {{ $slot }}
    </x-layouts.dashboard>
@endif
```

`turboFrameId()` returns the normalized `Turbo-Frame` request header or `null`. Keeping this branch at the layout
boundary lets the view and controller remain independent of their presentation.

Use the manual form when you need complete control over the layout branch itself. Prefer `<hw:frame-or-page>` for normal
application views so frame matching, layout resolution and contextual content stay on the package's tested path.

## Why this works well

- **One URL per resource** — the form keeps the same route in modal and page contexts.
- **Refresh-safe** — refreshing the modal URL renders the standalone page.
- **Bookmarkable** — copied and shared URLs open as regular pages.
- **Progressive enhancement** — without JavaScript, links navigate to the standalone page.
- **No duplicated views** — one Blade file covers both presentations.

## Trade-offs

- The receiving frame host must be present on every page that triggers it, usually through a shared layout.
- Every link or form that should use the overlay must declare the correct `data-turbo-frame`.
- Frame ids are document-global. Use distinct hosts when one layout needs multiple overlay presentations.

## See also

- [`<hw:frame-or-page>`](../components/frame-or-page.md) — complete component API and advanced contexts.
- [`<hw:modal>`](../components/modal.md) — the receiving modal primitive.
- [Server-driven modals](./server-driven-modals.md) — close and replace modal content from the server.
- [Composing streams](./composing-streams.md) — combine refresh, update and toast actions.
