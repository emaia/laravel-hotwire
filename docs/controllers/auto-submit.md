# Auto Submit

Submits the form automatically in response to events, with debounce support.

**Identifier:** `auto-submit`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `auto-submit#submit` | Submits the form immediately |
| `auto-submit#debouncedSubmit` | Submits after 300ms of inactivity (debounce) |
| `auto-submit#submitOnChange` | Alias for submit, semantic for `change` events |

## Basic usage — submit on select change

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

## Combined filters

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
