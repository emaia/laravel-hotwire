# Auto Submit

Submits the form automatically in response to events, with optional debounce.

**Identifier:** `auto-submit`  
**Install:** `php artisan hotwire:controllers auto-submit`

## Requirements

- No external dependencies.

## Actions

| Action                        | Description                                                                    |
|-------------------------------|--------------------------------------------------------------------------------|
| `auto-submit#submit`          | Submits the form immediately, cancelling any pending debounced submit          |
| `auto-submit#debouncedSubmit` | Submits after `delay` ms of inactivity; set `delay` to `0` to submit instantly |

## Values

| Value   | Type     | Default | Description                                                                           |
|---------|----------|---------|---------------------------------------------------------------------------------------|
| `delay` | `Number` | `300`   | Debounce window in milliseconds for `debouncedSubmit`. Set to `0` to submit instantly |

`delay` only affects `debouncedSubmit` — `submit` is always immediate.

## Action params

| Param   | Type     | Description                                                              |
|---------|----------|--------------------------------------------------------------------------|
| `delay` | `Number` | Per-field debounce override for `debouncedSubmit`; falls back to `delay` |

This lets a form keep a global debounce while one field uses a longer or shorter window:

```html
<form data-controller="auto-submit" data-auto-submit-delay-value="300">
    <input
        name="q"
        data-action="input->auto-submit#debouncedSubmit"
        data-auto-submit-delay-param="600"
    />
</form>
```

## Basic usage — submit on select change

A select `change` is a discrete event, so wire it to `submit` for an instant submit:

```html
<form data-controller="auto-submit">
    <select data-action="change->auto-submit#submit" name="status">
        <option value="all">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</form>
```

## With debounce — search as you type

Wire high-frequency `input` events to `debouncedSubmit` so the form submits only after the user stops
typing:

```html
<form data-controller="auto-submit">
    <input
        type="search"
        name="q"
        placeholder="Search..."
        data-action="input->auto-submit#debouncedSubmit"
    />
</form>
```

Tune the debounce window with `data-auto-submit-delay-value`:

```html
<form data-controller="auto-submit" data-auto-submit-delay-value="500">
    <input
        type="search"
        name="q"
        placeholder="Search..."
        data-action="input->auto-submit#debouncedSubmit"
    />
</form>
```

Field-level component props render the same wiring:

```blade
<hw:form method="get" action="/items" auto-submit auto-submit-delay="300">
    <hw:input name="q" type="search" auto-submit auto-submit-delay="600" />
    <hw:select name="category" :options="$categories" auto-submit />
</hw:form>
```

## Combined filters

Debounce the text input and submit selects immediately. Because `submit` cancels a debounce still pending
from typing, changing a select mid-search produces a single request instead of two:

```html
<form data-controller="auto-submit" method="get" action="/items">
    <input
        type="search"
        name="q"
        placeholder="Search..."
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
