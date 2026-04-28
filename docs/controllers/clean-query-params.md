# Clean Query Params

Removes empty parameters from the query string before submitting a GET form, avoiding polluted URLs like `?q=&category=&page=`.

The controller registers a `formdata` listener on `connect()`, so it intercepts every form submission automatically — regardless of what triggers it (native submit button, `auto-submit`, or `requestSubmit()` calls).

**Identifier:** `clean-query-params`  
**Install:** `php artisan hotwire:controllers clean-query-params`

## Requirements

- No external dependencies.

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

    <button type="submit">Filter</button>
</form>
```

If the user submits without filling anything, the URL will be `/items` instead of `/items?q=&category=`.

If only the search is filled, it will be `/items?q=term` instead of `/items?q=term&category=`.

## Combined with auto-submit

Because the listener is registered automatically, composing with `auto-submit` requires no extra wiring:

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
        data-action="change->auto-submit#submit"
    >
        <option value="">All</option>
        <option value="news">News</option>
    </select>
</form>
```

Empty fields are cleaned regardless of which input triggers the submission.
