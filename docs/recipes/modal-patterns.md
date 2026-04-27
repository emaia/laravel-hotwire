# Modal patterns

Three real-world ways to wire a modal into a Laravel + Hotwire app, plus when to drop down to the
raw Stimulus controller.

## Pattern 1 — Inline modal

Modal and trigger live together. The simplest setup; good for self-contained widgets where the
modal belongs to one specific spot on the page.

```blade
<x-hwc::modal>
    <x-slot:trigger>
        <button type="button" data-action="modal#open">Edit profile</button>
    </x-slot:trigger>

    <form method="POST" action="{{ route('profile.update') }}" class="p-6">
        @csrf
        @method('PATCH')
        {{-- fields --}}
        <button type="submit">Save</button>
    </form>
</x-hwc::modal>
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

    <x-hwc::modal id="modal">
        <turbo-frame id="modal" data-modal-target="dynamicContent"></turbo-frame>

        <x-slot:loading_template>
            <div class="flex items-center justify-center p-12">
                <span>Loading...</span>
            </div>
        </x-slot:loading_template>
    </x-hwc::modal>
</body>
```

```blade
{{-- a list page --}}
@foreach ($posts as $post)
    <tr>
        <td>{{ $post->title }}</td>
        <td>
            <a href="{{ route('posts.edit', $post) }}" data-turbo-frame="modal">
                Edit
            </a>
        </td>
    </tr>
@endforeach
```

The link clicks issues a frame-scoped request → response lands in the layout's `<turbo-frame
id="modal">` → the modal's content observer opens it. The `showLoading` action fires automatically
because the controller listens globally for `a[data-turbo-frame="modal"]` clicks.

**When to use:** CRUD lists, dashboards with multiple modal-driven actions, anywhere you want
modals without duplicating markup.

**Trade-offs:** every page that opens a modal must use this layout (not a problem if it's your
default). Pairs naturally with the
[frame-or-page recipe](./frame-or-page.md) so the same view renders as a page **or** a modal.

### Per-link loading templates

Different actions can show different skeletons:

```blade
<a href="{{ route('posts.edit', $post) }}"
   data-turbo-frame="modal"
   data-loading-template="#form-skeleton">
    Edit
</a>

<a href="{{ route('posts.comments', $post) }}"
   data-turbo-frame="modal"
   data-loading-template="#list-skeleton">
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
<x-hwc::modal id="welcome-modal">
    <x-slot:trigger>
        <button type="button" data-action="modal#open">What's new?</button>
    </x-slot:trigger>

    <div class="p-6 space-y-4">
        <h2 class="text-xl font-semibold">Welcome to v2</h2>
        <p>Here's what changed since you were last here.</p>
        <ul class="list-disc pl-6">
            <li>Inline comments</li>
            <li>Faster search</li>
        </ul>
    </div>
</x-hwc::modal>
```

**When to use:** content that doesn't need a server fetch (welcome dialogs, info modals, terms
acceptance, embedded media).

**Closing from the server:** if the static modal is opened by user action and closed by a form
submission elsewhere, use the [`closeModal` macro](../components/modal.md#convenience-macro)
plus the [`modal-auto-close`](../controllers/modal-auto-close.md) controller.

## Component vs raw controller

Reach for the Blade component (`<x-hwc::modal>`) by default — it ships sensible markup, default
classes, Turbo `before-cache` integration, and slot ergonomics.

Drop down to the raw [`modal` controller](../controllers/modal.md) only when:

- You need a **substantially different DOM structure** (custom backdrop, non-standard panel layout,
  multi-pane modals) that fighting the slot wouldn't cover.
- You're embedding the modal in a **third-party UI kit** that owns the markup.
- You want **no Tailwind dependency** in the rendered HTML — the component ships with Tailwind
  classes baked into the view.

For everything else (custom width, animations, focus-trap behaviour, click-outside semantics), the
component already exposes props or Stimulus values — no need to drop down.

## See also

- [`<x-hwc::modal>`](../components/modal.md) — component reference.
- [`modal` controller](../controllers/modal.md) — raw controller reference.
- [Frame-or-page views](./frame-or-page.md) — render the same view as a page or as a modal.
- [Server-driven modals](./server-driven-modals.md) — open and close from controller responses.
- [Composing streams](./composing-streams.md) — chain `refresh + closeModal + flash`.
