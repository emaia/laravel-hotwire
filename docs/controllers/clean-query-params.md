# Clean Query Params

Removes empty parameters from the query string before submitting a GET form, avoiding polluted URLs like `?q=&category=&page=`.

**Identifier:** `clean-query-params`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `clean-query-params#submit` | Clears empty fields and submits the form |

## Basic usage

```html
<form
    method="get"
    action="/items"
    data-controller="clean-query-params"
>
    <input type="search" name="q" placeholder="Search..." />

    <select name="category">
        <option value="">All</option>
        <option value="news">News</option>
    </select>

    <button type="button" data-action="clean-query-params#submit">Filter</button>
</form>
```

Attach `clean-query-params#submit` to the control that should trigger the filtered submit. The action registers a
`formdata` hook, removes empty values, and then submits the form.

If the user submits without filling anything, the URL will be `/items` instead of `/items?q=&category=`.

If only the search is filled, it will be `/items?q=term` instead of `/items?q=term&category=`.

## Combined with autosubmit

```html
<form
    method="get"
    action="/items"
    data-controller="clean-query-params auto-submit"
>
    <input
        type="search"
        name="q"
        data-action="input->auto-submit#debouncedSubmit"
    />

    <select
        name="category"
        data-action="change->clean-query-params#submit"
    >
        <option value="">All</option>
        <option value="news">News</option>
    </select>
</form>
```
