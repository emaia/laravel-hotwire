# Autosubmit

Submits the form automatically in response to events, with debounce support.

**Identifier:** `form--autosubmit`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `form--autosubmit#submit` | Submits the form immediately |
| `form--autosubmit#debouncedSubmit` | Submits after 300ms of inactivity (debounce) |
| `form--autosubmit#submitOnChange` | Alias for submit, semantic for `change` events |

## Basic usage — submit on select change

```html
<form data-controller="form--autosubmit">
    <select data-action="change->form--autosubmit#submit" name="status">
        <option value="all">All</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
    </select>
</form>
```

## With debounce — search as you type

```html
<form data-controller="form--autosubmit">
    <input
        type="search"
        name="q"
        placeholder="Search..."
        data-action="input->form--autosubmit#debouncedSubmit"
    />
</form>
```

## Combined filters

```html
<form data-controller="form--autosubmit" method="get" action="/items">
    <input
        type="search"
        name="q"
        placeholder="Search..."
        data-action="input->form--autosubmit#debouncedSubmit"
    />

    <select name="category" data-action="change->form--autosubmit#submit">
        <option value="">All categories</option>
        <option value="news">News</option>
        <option value="events">Events</option>
    </select>

    <select name="order" data-action="change->form--autosubmit#submit">
        <option value="recent">Most recent</option>
        <option value="oldest">Oldest</option>
    </select>
</form>
```
