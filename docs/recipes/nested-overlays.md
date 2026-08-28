# Nested overlays for admin workflows

Use stacked overlays when a user needs to inspect or edit related records without losing the
screen they started from. A common admin flow is:

1. Users index opens an edit form in a shared Turbo Frame modal.
2. The edit form opens a second modal for roles and permissions.
3. A destructive role removal uses `alert-dialog` above the second modal.
4. The server returns Turbo Streams that update the user row, the roles panel and toast feedback.

This gives you SPA-like depth while keeping each screen server-rendered and URL-addressable.

## Why this pattern works

- Turbo Frames load each layer on demand from normal Laravel routes.
- Turbo Streams update the rows and panels that changed after submit.
- The overlay stack ensures `Escape` closes only the top layer.
- Top layer rendering keeps nested modals, dropdowns, popovers and multi-selects from being clipped.
- The first page keeps its filters, scroll position and selected rows while the user works through details.

## Layout hosts

Render one shared modal for the user editor and a second shared modal for role management. Keeping
separate frame ids makes each layer addressable and avoids replacing the wrong panel.

```blade
{{-- resources/views/components/layouts/dashboard.blade.php --}}
<body>
    <header>...</header>

    <main>
        {{ $slot }}
    </main>

    <hw:modal id="user-modal-shell" frame="user-modal" size="lg">
        <x-slot:loading_template>
            <hw:modal.title>Loading user</hw:modal.title>
            <div class="text-muted-foreground flex items-center justify-center p-12 text-sm">Loading user...</div>
        </x-slot>
    </hw:modal>

    <hw:modal id="roles-modal-shell" frame="roles-modal" size="md">
        <x-slot:loading_template>
            <hw:modal.title>Loading roles</hw:modal.title>
            <div class="text-muted-foreground flex items-center justify-center p-12 text-sm">Loading roles...</div>
        </x-slot>
    </hw:modal>
</body>
```

## Users index

The index stays a normal page. Edit links target the first modal frame.

```blade
{{-- resources/views/users/index.blade.php --}}
<hw:table>
    <hw:table.header>
        <hw:table.row>
            <hw:table.head>Name</hw:table.head>
            <hw:table.head>Email</hw:table.head>
            <hw:table.head></hw:table.head>
        </hw:table.row>
    </hw:table.header>

    <hw:table.body>
        @foreach ($users as $user)
            <hw:table.row id="{{ dom_id($user) }}">
                <hw:table.cell>{{ $user->name }}</hw:table.cell>
                <hw:table.cell>{{ $user->email }}</hw:table.cell>
                <hw:table.cell>
                    <hw:button
                        as="a"
                        href="{{ route('users.edit', $user) }}"
                        data-turbo-frame="user-modal"
                        variant="outline"
                    >
                        Edit
                    </hw:button>
                </hw:table.cell>
            </hw:table.row>
        @endforeach
    </hw:table.body>
</hw:table>
```

## Routes

Use GET routes for frame payloads and normal form routes for writes.

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('can:update,user')
        ->name('users.edit');

    Route::patch('users/{user}', [UserController::class, 'update'])
        ->middleware('can:update,user')
        ->name('users.update');

    Route::get('users/{user}/roles', [UserRoleController::class, 'edit'])
        ->middleware('can:manageRoles,user')
        ->name('users.roles.edit');

    Route::patch('users/{user}/roles', [UserRoleController::class, 'update'])
        ->middleware('can:manageRoles,user')
        ->name('users.roles.update');

    Route::delete('users/{user}/roles/{role}', [UserRoleController::class, 'destroy'])
        ->middleware('can:manageRoles,user')
        ->name('users.roles.destroy');
});
```

These routes assume `UserPolicy::update()` protects profile editing and
`UserPolicy::manageRoles()` protects role assignment changes.

## First layer: edit user

The edit response lands in `user-modal`. From inside that modal, a link targets `roles-modal`, opening
the second layer above it.

```blade
{{-- resources/views/users/edit.blade.php --}}
<hw:frame-or-page frame="user-modal" layout="dashboard">
    <hw:modal.header>
        <hw:modal.title>Edit {{ $user->name }}</hw:modal.title>
        <hw:modal.description>Update account details and role assignments.</hw:modal.description>
    </hw:modal.header>

    <hw:form :action="route('users.update', $user)" method="patch" class="space-y-4" track-frame-src>
        <hw:field name="name" label="Name">
            <hw:input name="name" :value="$user->name" />
        </hw:field>

        <hw:field name="email" label="Email">
            <hw:input name="email" type="email" :value="$user->email" />
        </hw:field>

        <div class="flex flex-wrap gap-2">
            <hw:button type="submit">Save user</hw:button>

            <hw:button
                as="a"
                href="{{ route('users.roles.edit', $user) }}"
                data-turbo-frame="roles-modal"
                variant="secondary"
            >
                Manage roles
            </hw:button>
        </div>
    </hw:form>
</hw:frame-or-page>
```

The layout-owned modal already renders its content surface and `user-modal` frame. The response only
supplies the matching frame payload; nesting another `<hw:modal.content>` would create a second
overlay inside the first.

## Second layer: manage roles

The roles modal can contain normal form controls plus floating components. Popovers, dropdowns and
multi-selects stay positioned correctly because they also use the top layer.

```blade
{{-- resources/views/users/roles.blade.php --}}
<hw:frame-or-page frame="roles-modal" layout="dashboard">
    <hw:modal.header>
        <hw:modal.title>Manage roles</hw:modal.title>
        <hw:modal.description>Change permissions for {{ $user->name }}.</hw:modal.description>
    </hw:modal.header>

    <hw:form :action="route('users.roles.update', $user)" method="patch" class="space-y-5" track-frame-src>
        <hw:field name="roles" label="Assigned roles">
            <hw:multi-select
                name="roles"
                placeholder="Select roles"
                :options="$availableRoles->pluck('name', 'id')->all()"
                :selected="$user->roles->pluck('id')->map(fn ($id) => (string) $id)->all()"
                search
                select-all
            />
        </hw:field>

        <div class="flex justify-end gap-2">
            <hw:frame-or-page.frame>
                <hw:modal.close>Cancel</hw:modal.close>
            </hw:frame-or-page.frame>

            <hw:frame-or-page.page>
                <hw:button as="a" :href="route('users.index')" variant="outline">Cancel</hw:button>
            </hw:frame-or-page.page>

            <hw:button type="submit">Save roles</hw:button>
        </div>
    </hw:form>

    <div class="border-border rounded-lg border p-3 text-sm">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="font-medium">Current roles</p>
                <p class="text-muted-foreground">Remove high-risk roles with confirmation.</p>
            </div>

            <hw:popover>
                <hw:popover.trigger>Policy notes</hw:popover.trigger>
                <hw:popover.content>
                    <p class="text-muted-foreground text-sm">
                        Admin and Billing roles grant access to sensitive account settings.
                    </p>
                </hw:popover.content>
            </hw:popover>
        </div>

        <div class="mt-3 space-y-2">
            @foreach ($user->roles as $role)
                <div
                    id="{{ dom_id($role, 'assignment') }}"
                    class="border-border flex items-center justify-between gap-3 rounded-md border p-2"
                >
                    <span>{{ $role->name }}</span>

                    <hw:form :action="route('users.roles.destroy', [$user, $role])" method="delete">
                        <hw:alert-dialog
                            title="Remove {{ $role->name }}?"
                            description="The user loses this access immediately."
                            confirm-label="Remove role"
                            confirm-variant="destructive"
                            cancel-label="Cancel"
                        >
                            <hw:button type="submit" variant="destructive" size="sm">Remove</hw:button>

                            <x-slot:content>
                                <p class="text-muted-foreground text-sm">
                                    This does not delete the role itself. It only removes the assignment from
                                    {{ $user->name }}.
                                </p>
                            </x-slot>
                        </hw:alert-dialog>
                    </hw:form>
                </div>
            @endforeach
        </div>
    </div>
</hw:frame-or-page>
```

The alert dialog intercepts the submit button's first click. Confirming replays that click, so the
surrounding form sends the declared `DELETE` request. Keep these forms outside the role-sync form;
nested HTML forms are invalid.

## Controllers

The GET actions render frame-or-page views. The write actions return streams only for the matching
frame submission and use redirects for direct or non-JavaScript submissions.

Because the validation-bearing update forms submit to mutation URLs different from the GET URLs
that rendered each frame, their form requests extend `TurboFormRequest`. Together with
`track-frame-src`, validation failures return to the correct frame source:

```php
use Emaia\LaravelHotwireTurbo\Http\Requests\TurboFormRequest;

final class UpdateUserRequest extends TurboFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}

final class UpdateUserRolesRequest extends TurboFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageRoles', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ];
    }
}
```

```php
use Illuminate\Support\Facades\Gate;

final class UserController
{
    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        Gate::authorize('update', $user);

        $user->update($request->validated());

        if ($request->wasFromTurboFrame('user-modal') && $request->wantsTurboStream()) {
            return turbo_stream()
                ->replace(dom_id($user), view('users._row', ['user' => $user]))
                ->update('user-modal')
                ->toast('success', 'User updated');
        }

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'User updated');
    }
}
```

```php
use Illuminate\Support\Facades\Gate;

final class UserRoleController
{
    public function edit(User $user)
    {
        Gate::authorize('manageRoles', $user);

        return view('users.roles', [
            'user' => $user->load('roles'),
            'availableRoles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user)
    {
        Gate::authorize('manageRoles', $user);

        $validated = $request->validated();

        $user->roles()->sync($validated['roles'] ?? []);

        if ($request->wasFromTurboFrame('roles-modal') && $request->wantsTurboStream()) {
            return turbo_stream()
                ->replace(dom_id($user), view('users._row', ['user' => $user->fresh('roles')]))
                ->update('roles-modal')
                ->toast('success', 'Roles updated');
        }

        return redirect()
            ->route('users.roles.edit', $user)
            ->with('status', 'Roles updated');
    }

    public function destroy(Request $request, User $user, Role $role)
    {
        Gate::authorize('manageRoles', $user);

        $user->roles()->detach($role);

        if ($request->wasFromTurboFrame('roles-modal') && $request->wantsTurboStream()) {
            return turbo_stream()
                ->remove(dom_id($role, 'assignment'))
                ->replace(dom_id($user), view('users._row', ['user' => $user->fresh('roles')]))
                ->toast('success', "{$role->name} removed from {$user->name}");
        }

        return redirect()
            ->route('users.roles.edit', $user)
            ->with('status', "{$role->name} removed from {$user->name}");
    }
}
```

## Interaction contract

When the user opens every layer, the stack behaves like this:

1. `Escape` closes the alert dialog first.
2. A second `Escape` closes the roles modal.
3. A third `Escape` closes the edit user modal.
4. Focus trapping belongs only to the top overlay.
5. Body scroll remains locked until the final modal closes.
6. Popover, dropdown and multi-select panels escape modal clipping and keep their own styling.

## When to use this

Reach for this pattern when an action needs related context but should not navigate away from the
current page: user administration, order review, CRM tickets, approval workflows, audit trails and
settings screens with related resources.

Avoid it when each layer is a full task with its own navigation state. In those cases, prefer normal
pages or the [frame-or-page](./frame-or-page.md) pattern.

## See also

- [Modal patterns](./modal-patterns.md) — layout-shared modal setup.
- [Server-driven confirmation](./server-driven-confirmation.md) — confirmations with server-computed context.
- [`<hw:alert-dialog>`](../components/alert-dialog.md) — one-round-trip destructive confirmations.
- [`<hw:multi-select>`](../components/multi-select.md), [`<hw:popover>`](../components/popover.md) and
  [`<hw:dropdown>`](../components/dropdown.md) — floating components that can live inside overlays.
