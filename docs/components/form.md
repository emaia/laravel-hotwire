# Form

Form wrapper that composes optional Stimulus behaviors via boolean props. Renders a `<form>` element with a default `method="post"`.

## Quick example

```blade
<x-hwc::form :action="route('items.index')" method="get" auto-submit clean-query-params>
    <x-hwc::input type="search" name="q" placeholder="Search..." />
    <x-hwc::input type="hidden" name="category" value="books" />
    <button type="submit">Search</button>
</x-hwc::form>
```

## Props

| Prop                 | Type    | Default  | Description                                                                 |
|----------------------|---------|----------|-----------------------------------------------------------------------------|
| `auto-submit`        | `bool`  | `false`  | Adds `auto-submit` controller (submit on input/change — requires `data-action` on fields) |
| `unsaved-changes`    | `bool`  | `false`  | Warns before navigating away with unsaved changes                          |
| `clean-query-params` | `bool`  | `false`  | Strips empty fields from GET query strings before submission                |
| `remote`             | `bool`  | `false`  | Adds `remote-form` controller (trigger submit from decoupled element)      |

Any other HTML attribute (`action`, `method`, `enctype`, `class`, `data-*`, `aria-*`) passes through to the `<form>` element. Method defaults to `post` unless overridden.

## Controllers

Each boolean prop activates a Stimulus controller on `data-controller`. Multiple props compose automatically:

```blade
{{-- Renders: data-controller="auto-submit unsaved-changes" --}}
<x-hwc::form auto-submit unsaved-changes>
    ...
</x-hwc::form>
```

### auto-submit

Submits the form automatically in response to events. You still wire individual fields via `data-action`:

```blade
<x-hwc::form action="/search" method="get" auto-submit>
    <input
        type="search"
        name="q"
        data-action="input->auto-submit#debouncedSubmit"
    />
    <select name="category" data-action="change->auto-submit#submit">
        ...
    </select>
</x-hwc::form>
```

See [auto-submit controller](../controllers/auto-submit.md).

### unsaved-changes

Warns with a browser confirmation dialog when attempting to navigate away with unsaved form changes. Integrates with Turbo Drive.

```blade
<x-hwc::form :action="route('posts.update', $post)" method="post" unsaved-changes>
    @csrf @method('put')
    <x-hwc::input name="title" :value="$post->title" />
    <button type="submit">Save</button>
</x-hwc::form>
```

See [unsaved-changes controller](../controllers/unsaved-changes.md).

### clean-query-params

Removes empty parameters from the query string before submitting a GET form, avoiding polluted URLs like `?q=&category=`.

```blade
<x-hwc::form action="/items" method="get" clean-query-params>
    <input type="search" name="q" />
    <select name="category">
        <option value="">All</option>
        ...
    </select>
    <button type="submit">Filter</button>
</x-hwc::form>
```

See [clean-query-params controller](../controllers/clean-query-params.md).

### remote

Adds the `remote-form` controller for submitting a form through a decoupled trigger element. Requires a `data-remote-form-target="submitBtn"` on the real submit button.

```blade
<x-hwc::form action="/preview" method="post" remote>
    <select data-action="change->remote-form#remoteSubmit" name="content_type">
        ...
    </select>
    <button type="submit" class="hidden" data-remote-form-target="submitBtn">
        Load
    </button>
</x-hwc::form>
```

See [remote-form controller](../controllers/remote-form.md).

## Required controllers

`hotwire:check` looks for `auto-submit`, `unsaved-changes`, `clean-query-params`, and `remote-form`. Only the ones you use need to be published.
