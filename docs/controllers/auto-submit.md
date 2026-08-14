# Auto Submit

The `auto-submit` controller submits a form when fields change. You may submit immediately for discrete controls such as
selects and checkboxes, or debounce high-frequency input events such as typing in a search field.

**Identifier:** `auto-submit`  
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers auto-submit`.

## Basic Usage

To submit a form when a select changes, connect the `change` event to the `submit` action:

```html
<form data-controller="auto-submit" method="get" action="/posts">
    <select data-action="change->auto-submit#submit" name="status">
        <option value="">All posts</option>
        <option value="draft">Draft</option>
        <option value="published">Published</option>
    </select>
</form>
```

The `submit` action is immediate. It also cancels any debounced submit that may already be waiting, so changing a select
after typing in a search field produces one request instead of two.

## Debouncing Text Input

When submitting a search field, use `debouncedSubmit` so the form is only submitted after the user stops typing:

```html
<form data-controller="auto-submit" method="get" action="/posts">
    <input
        type="search"
        name="q"
        placeholder="Search posts..."
        data-action="input->auto-submit#debouncedSubmit"
    />
</form>
```

The default debounce window is `300` milliseconds. You may change it for the whole form with
`data-auto-submit-delay-value`:

```html
<form data-controller="auto-submit" data-auto-submit-delay-value="500" method="get" action="/posts">
    <input
        type="search"
        name="q"
        placeholder="Search posts..."
        data-action="input->auto-submit#debouncedSubmit"
    />
</form>
```

## Combined Filters

A common filter form debounces text input and submits selects immediately:

```html
<form data-controller="auto-submit" method="get" action="/posts">
    <input
        type="search"
        name="q"
        placeholder="Search posts..."
        data-action="input->auto-submit#debouncedSubmit"
    />

    <select name="category" data-action="change->auto-submit#submit">
        <option value="">All categories</option>
        <option value="news">News</option>
        <option value="events">Events</option>
    </select>

    <select name="order" data-action="change->auto-submit#submit">
        <option value="recent">Most recent</option>
        <option value="oldest">Oldest</option>
    </select>
</form>
```

If the user is still typing when a select changes, the immediate submit wins and the pending search submit is cancelled.

## Per-Field Delay

You may override the debounce window for a single field with an action parameter. This lets a form keep a global debounce
while one field uses a longer or shorter delay:

```html
<form data-controller="auto-submit" data-auto-submit-delay-value="300" method="get" action="/posts">
    <input
        type="search"
        name="q"
        placeholder="Search posts..."
        data-action="input->auto-submit#debouncedSubmit"
        data-auto-submit-delay-param="600"
    />
</form>
```

## Using Blade Components

Several form components expose `auto-submit` props that render the same Stimulus wiring for you:

```blade
<hw:form method="get" action="/products" auto-submit auto-submit-delay="300">
    <hw:input name="q" type="search" placeholder="Search products..." auto-submit auto-submit-delay="600" />
    <hw:slider name="max_price" :max="1000" aria-label="Maximum price" auto-submit />
    <hw:select name="category" :options="$categories" auto-submit />
</hw:form>
```

For example, the field-level `auto-submit` prop on a text input renders the same action you would write by hand:

```html
<input
    name="q"
    type="search"
    data-action="input->auto-submit#debouncedSubmit"
    data-auto-submit-delay-param="600"
>
```

## Behavior

Both actions ignore events that have already been prevented by another handler. During IME composition, the controller
cancels pending work and waits for the committed value before submitting, including when the configured debounce is `0`.
If a browser emits the commit input before `compositionend`, the controller resumes from `compositionend` without
submitting twice.

The `delay` value only affects `debouncedSubmit`. The `submit` action is always immediate.

## Actions

| Action                        | Description                                                                    |
|-------------------------------|--------------------------------------------------------------------------------|
| `auto-submit#submit`          | Submits the form immediately, cancelling any pending debounced submit          |
| `auto-submit#debouncedSubmit` | Submits after `delay` ms of inactivity; set `delay` to `0` to submit instantly |

## Values

| Value   | Type     | Default | Description                                                                           |
|---------|----------|---------|---------------------------------------------------------------------------------------|
| `delay` | `Number` | `300`   | Debounce window in milliseconds for `debouncedSubmit`. Set to `0` to submit instantly |

## Action Params

| Param   | Type     | Description                                                              |
|---------|----------|--------------------------------------------------------------------------|
| `delay` | `Number` | Per-field debounce override for `debouncedSubmit`; falls back to `delay` |

## Requirements

- No external dependencies.
- Ships with `_composition.js`; publishing the controller publishes this helper too.
