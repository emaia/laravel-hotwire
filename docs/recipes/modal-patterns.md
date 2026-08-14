# Modal patterns

Three real-world ways to wire a modal into a Laravel + Hotwire app, plus when to drop down to the
raw Stimulus controller.

## Pattern 1 — Inline modal

Modal and trigger live together. The simplest setup; good for self-contained widgets where the
modal belongs to one specific spot on the page.

```blade
<hw:modal>
    <hw:modal.trigger>Edit profile</hw:modal.trigger>

    <hw:modal.content>
        <form method="POST" action="{{ route('profile.update') }}" class="p-6">
            @csrf
            @method('PATCH')
            {{-- fields --}}
            <button type="submit">Save</button>
        </form>
    </hw:modal.content>
</hw:modal>
```

**When to use:** widget-level UI (settings popovers, single-action confirmations with custom UI,
help dialogs), one trigger only, content fits in the page.

**Trade-offs:** the modal markup duplicates if you have many similar triggers (e.g., "Edit" on every
row of a list). Reach for Pattern 2 instead.

## Pattern 2 — Layout-shared modal

One modal lives in the layout, many triggers reuse it via Turbo Frame. The trigger lives wherever
makes sense (list rows, navigation, deep in a partial) — Stimulus picks it up by frame id.

```blade
{{-- resources/views/components/layouts/dashboard.blade.php --}}
<body>
    <header>...</header>
    <main>{{ $slot }}</main>

    <hw:modal frame="modal">
        <x-slot:loading_template>
            <div class="flex items-center justify-center p-12">
                <span>Loading...</span>
            </div>
        </x-slot>
    </hw:modal>
</body>
```

```blade
{{-- a list page --}}
@foreach ($posts as $post)
    <tr>
        <td>{{ $post->title }}</td>
        <td>
            <a href="{{ route('posts.edit', $post) }}" data-turbo-frame="modal">Edit</a>
        </td>
    </tr>
@endforeach
```

The link click issues a frame-scoped request → the response lands in the `<turbo-frame id="modal">`
rendered by `frame="modal"` → the modal's content observer opens it. The loading template is
injected automatically because the controller listens globally for `a[data-turbo-frame="modal"]` clicks.

**When to use:** CRUD lists, dashboards with multiple modal-driven actions, anywhere you want
modals without duplicating markup.

**Trade-offs:** every page that opens a modal must use this layout (not a problem if it's your
default). Pairs naturally with the
[frame-or-page recipe](./frame-or-page.md) so the same view renders as a page **or** a modal.

### Per-link loading templates

Different actions can show different skeletons:

```blade
<a href="{{ route('posts.edit', $post) }}" data-turbo-frame="modal" data-loading-template="#form-skeleton">Edit</a>

<a href="{{ route('posts.comments', $post) }}" data-turbo-frame="modal" data-loading-template="#list-skeleton">
    Comments
</a>

<template id="form-skeleton">{{-- ... --}}</template>
<template id="list-skeleton">{{-- ... --}}</template>
```

Resolution: per-link template → modal's `loading_template` slot → empty.

## Pattern 3 — Static modal

No Turbo Frame, no dynamic content. The modal body is rendered server-side once and toggled via
`data-action`.

```blade
<hw:modal id="welcome-modal">
    <hw:modal.trigger>What's new?</hw:modal.trigger>

    <hw:modal.content>
        <div class="space-y-4 p-6">
            <h2 class="text-xl font-semibold">Welcome to v2</h2>
            <p>Here's what changed since you were last here.</p>
            <ul class="list-disc pl-6">
                <li>Inline comments</li>
                <li>Faster search</li>
            </ul>
        </div>
    </hw:modal.content>
</hw:modal>
```

**When to use:** content that doesn't need a server fetch (welcome dialogs, info modals, terms
acceptance, embedded media).

**Closing from the server:** if reusable static modal markup should remain available for another
open, append the self-removing `modal-auto-close` marker to its root:

```php
return turbo_stream()->append(
    'welcome-modal',
    '<span data-controller="modal-auto-close"></span>',
);
```

An empty update of the modal root removes its inner markup. Reserve that approach for disposable
static modals that should not reopen:

```php
return turbo_stream()->update('one-time-modal');
```

## Component vs raw controller

Reach for the Blade component (`<hw:modal>`) by default. It emits accessible markup, stable
`data-slot` hooks styled by the active CSS preset, Turbo `before-cache` integration, and ergonomic
subcomponents.

Drop down to the raw [`modal` controller](../controllers/modal.md) only when:

- You need a **substantially different DOM structure** (custom backdrop, non-standard panel layout,
  multi-pane modals) that fighting the slot wouldn't cover.
- You're embedding the modal in a **third-party UI kit** that owns the markup.
- You need a markup contract that is not based on the component's `data-slot` styling hooks.

For everything else (custom width, animations, focus-trap behaviour, click-outside semantics), the
component already exposes props or Stimulus values — no need to drop down.

## See also

- [`<hw:modal>`](../components/modal.md) — component reference.
- [`modal` controller](../controllers/modal.md) — raw controller reference.
- [Frame-or-page views](./frame-or-page.md) — render the same view as a page or as a modal.
- [Server-driven modals](./server-driven-modals.md) — open and close from controller responses.
- [Composing streams](./composing-streams.md) — chain `refresh + update + toast`.
