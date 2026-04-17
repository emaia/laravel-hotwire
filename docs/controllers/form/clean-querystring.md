# Clean Query String

Removes empty parameters from the query string before submitting a GET form, avoiding polluted URLs like `?q=&category=&page=`.

**Identifier:** `form--clean-querystring`

## Requirements

- No external dependencies.

## Actions

| Action | Description |
|--------|-------------|
| `form--clean-querystring#submit` | Clears empty fields and submits the form |

## Basic usage

```html
<form
    method="get"
    action="/items"
    data-controller="form--clean-querystring"
>
    <input type="search" name="q" placeholder="Search..." />

    <select name="category">
        <option value="">All</option>
        <option value="news">News</option>
    </select>

    <button type="button" data-action="form--clean-querystring#submit">Filter</button>
</form>
```

Attach `form--clean-querystring#submit` to the control that should trigger the filtered submit. The action registers a
`formdata` hook, removes empty values, and then submits the form.

If the user submits without filling anything, the URL will be `/items` instead of `/items?q=&category=`.

If only the search is filled, it will be `/items?q=term` instead of `/items?q=term&category=`.

## Combined with autosubmit

```html
<form
    method="get"
    action="/items"
    data-controller="form--clean-querystring form--autosubmit"
>
    <input
        type="search"
        name="q"
        data-action="input->form--autosubmit#debouncedSubmit"
    />

    <select
        name="category"
        data-action="change->form--clean-querystring#submit"
    >
        <option value="">All</option>
        <option value="news">News</option>
    </select>
</form>
```
