# Stable component ids

Laravel Hotwire generates deterministic ids for components that need internal DOM identity. Most components need no
configuration: repeated renders from the same page or Turbo Frame response root produce the same ids, allowing Turbo
morphs to preserve the existing nodes and their client-side state.

## When the default is enough

Use the automatic id when the component has a stable position and every render comes from the same response root:

```blade
<hw:modal>...</hw:modal>
<hw:carousel>...</hw:carousel>
```

The sequence is unique within the rendered response. Automatic IDs use the `hw-` package namespace. A requested Turbo
Frame has its own namespace so a lazy frame does not collide with the surrounding page.

An eager frame first rendered as part of a full page and later refreshed as an individual frame crosses response roots.
Pass a model or explicit string in that case, just as you would for a Turbo Stream fragment.

The ordinal also assumes that earlier automatic components with the same prefix stay in the same order. If a conditional
sibling of the same component type appears before another, both receive new semantic positions: during a morph, the old
ID can match the new sibling and mix their state instead of merely losing it. Give the affected components model-derived
or explicit IDs.

## Lists and server-rendered fragments

A separate request cannot inspect ids that are already present in the browser. Pass a persisted record with a stable key
to `id` when a stateful component appears in a collection, can be reordered, or is rendered again by a frame or Turbo
Stream:

```blade
@foreach ($tasks as $task)
    <article id="{{ dom_id($task) }}">
        <hw:dropdown :id="$task">
            <hw:dropdown.trigger>Actions</hw:dropdown.trigger>
            <hw:dropdown.content>...</hw:dropdown.content>
        </hw:dropdown>
    </article>
@endforeach
```

The component resolves the model identity with the same naming convention as `dom_id()` and adds its own prefix,
producing an id such as
`dropdown_task_42`. Rendering the same task in a later request produces the same component id.

Unsaved models do not have a durable cross-request identity and are rejected. Save the record first or pass an explicit
string that is stable for the lifetime of the UI.

The `id` prop accepts a model on Alert Dialog, Accordion, Carousel, Drawer, Dropdown, File Upload, Hover Card, Modal,
Multi Select, Popover, Read More, Rich Text, Sheet, and Tabs.

## Durable external targets

Use an explicit string when CSS, application JavaScript, a test, or a Turbo Stream targets the component directly:

```blade
<hw:modal id="task-editor">...</hw:modal>
```

If the same component appears more than once for one record, derive distinct ids explicitly:

```blade
<hw:dropdown :id="dom_id($task, 'primary_actions')">...</hw:dropdown>
<hw:dropdown :id="dom_id($task, 'secondary_actions')">...</hw:dropdown>
```

Automatic ids are an internal identity mechanism, not a public selector contract. An explicit id remains the right
choice whenever other application code needs to address the element.

## Development diagnostics

Mount `dev--duplicate-ids` on the application root in development to warn when a separate response inserts any ID that
is already present:

```blade
<body @env('local') data-controller="dev--duplicate-ids" @endenv>
    ...
</body>
```

The warning identifies the duplicate ID and its matching elements before silent first-match behavior in DOM APIs, ARIA
references, labels, or Turbo morphing hides the source of the problem. Automatic package IDs receive targeted guidance;
model-derived and explicit IDs are checked too, including when the same model-backed component appears twice.

## Decision guide

| Situation | Recommended id |
| --- | --- |
| Stable component rendered repeatedly from the same page or frame root | Omit `id` |
| A conditional component can appear before another with the same automatic prefix | Pass a model or explicit string to both |
| Component in a list, eager frame refresh, reordered collection, or cross-request fragment | Pass a persisted model with `:id="$record"` |
| Component targeted by CSS, JavaScript, tests, or Turbo Streams | Pass an explicit string |
| Two instances of the same component for one model | Use distinct `dom_id()` prefixes |

Form controls continue to derive their ids from `name`. Pass an explicit control or Field `id` when the same field name
appears more than once in the current document.
