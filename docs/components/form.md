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
| `error-scroll`       | `bool`  | `false`  | Scrolls to the first validation error after form submission                |
| `clean-query-params` | `bool`  | `false`  | Strips empty fields from GET query strings before submission                |
| `track-frame-src`    | `bool`  | `false`  | Includes a hidden `_turbo_frame_src` input for correct redirect resolution inside Turbo Frames |
| `enctype`            | `string\|null` | `null`  | HTML `enctype` attribute. Set to `"multipart/form-data"` for file uploads. Default `null` omits the attribute (browser uses `application/x-www-form-urlencoded`) |

Any other HTML attribute (`action`, `method`, `class`, `data-*`, `aria-*`) passes through to the `<form>` element. Method defaults to `post` unless overridden.

The component automatically includes `@csrf` for all non-GET methods and `@method` for PUT, PATCH, and DELETE forms.

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
<x-hwc::form :action="route('posts.update', $post)" method="put" unsaved-changes>
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

### error-scroll

Scrolls to the first validation error after a form submission fails. Listens to `turbo:frame-render` (inside a Turbo Frame) or `turbo:render` (full-page morphs) and finds the first `[aria-invalid]` element to scroll it into view. Works automatically with `<x-hwc::field>` and `<x-hwc::input>`, which set `aria-invalid` on validation errors.

```blade
<x-hwc::form :action="route('posts.store')" method="post" error-scroll>
    <x-hwc::field name="title" label="Title" required>
        <x-hwc::input />
    </x-hwc::field>
    <button type="submit">Save</button>
</x-hwc::form>
```

The controller also supports customising the selector, scroll behavior, and block position via `data-error-scroll-*-value` attributes. See [error-scroll controller](../controllers/error-scroll.md).

### CSRF and method spoofing

The component automatically includes `@csrf` for all non-GET methods, and `@method` for PUT, PATCH, and DELETE forms. You don't need to add them manually inside the slot:

```blade
<x-hwc::form :action="route('posts.store')" method="post">
    <x-hwc::input name="title" />
    <button type="submit">Save</button>
</x-hwc::form>
{{-- @csrf is automatically included --}}

<x-hwc::form :action="route('posts.update', $post)" method="put">
    <x-hwc::input name="title" :value="$post->title" />
    <button type="submit">Update</button>
</x-hwc::form>
{{-- @csrf and @method('put') are automatically included --}}
```

GET forms (search, filters) don't get a CSRF token or method spoofing since they don't modify state.

### Frame redirect resolution

When a form lives inside a Turbo Frame (`<turbo-frame>`), validation failures would historically break out of the frame context because Laravel's default redirect-back points to the wrong URL. The `track-frame-src` prop solves this by including a hidden `_turbo_frame_src` input with the current page URL:

```blade
<turbo-frame id="content" src="/posts/create">
    <x-hwc::form :action="route('posts.store')" method="post" track-frame-src>
        <x-hwc::input name="title" />
        <button type="submit">Save</button>
    </x-hwc::form>
</turbo-frame>
```

On validation failure, the server reads this input and redirects back to the frame's source URL, keeping the frame context intact. For client-side header injection (alternative approach), publish the standalone `turbo--frame-src` controller. See [frame-src controller](../controllers/turbo/frame-src.md).

## Required controllers

`hotwire:check` looks for `auto-submit`, `unsaved-changes`, `error-scroll`, and `clean-query-params`. Only the ones you actually use need to be published — `hotwire:check` will warn for the others even if you do not enable them.
