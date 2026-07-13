# Pagination

Semantic pagination navigation with composed subcomponents and an automatic `LengthAwarePaginator` mode.

## Usage

```blade
<hw:pagination>
    <hw:pagination.content>
        <hw:pagination.item>
            <hw:pagination.previous href="{{ $users->previousPageUrl() }}" />
        </hw:pagination.item>

        <hw:pagination.item>
            <hw:pagination.link href="{{ $users->url(1) }}">1</hw:pagination.link>
        </hw:pagination.item>

        <hw:pagination.item>
            <hw:pagination.link active>2</hw:pagination.link>
        </hw:pagination.item>

        <hw:pagination.item>
            <hw:pagination.ellipsis />
        </hw:pagination.item>

        <hw:pagination.item>
            <hw:pagination.next href="{{ $users->nextPageUrl() }}" />
        </hw:pagination.item>
    </hw:pagination.content>
</hw:pagination>
```

The active page renders as a `<span aria-current="page">` instead of linking to itself. Disabled previous and next
controls render as spans with `aria-disabled="true"`.

## Paginator Mode

Pass Laravel's `LengthAwarePaginator` to render the navigation from Laravel's paginator URL window.

```blade
<hw:pagination :paginator="$users" />
```

When the paginator has no extra pages, the component renders nothing. Use Laravel's `onEachSide()` before passing the
paginator when you need a different page window.

```blade
<hw:pagination :paginator="$users->onEachSide(1)" />
```

## Turbo Frames

Use `turbo-frame` to add `data-turbo-frame` to links generated in paginator mode.

```blade
<turbo-frame id="users">
    @include('users.table', ['users' => $users])

    <hw:pagination :paginator="$users" turbo-frame="users" />
</turbo-frame>
```

In manual mode, pass `data-turbo-frame` directly to each link component.

```blade
<hw:pagination.link href="{{ $users->url(2) }}" data-turbo-frame="users">2</hw:pagination.link>
```

## Props

| Component             | Prop            | Default      | Description                                                |
|-----------------------|-----------------|--------------|------------------------------------------------------------|
| `pagination`          | `paginator`     | `null`       | Optional `LengthAwarePaginator` for automatic links.       |
| `pagination`          | `label`         | `Pagination` | Accessible label for the root `nav`.                       |
| `pagination`          | `turboFrame`    | `null`       | Turbo Frame target for generated links.                    |
| `pagination`          | `previousLabel` | `Previous`   | Visible previous label, hidden on small screens.           |
| `pagination`          | `nextLabel`     | `Next`       | Visible next label, hidden on small screens.               |
| `pagination`          | `ellipsisLabel` | `More pages` | Accessible label for automatic ellipsis markers.           |
| `pagination.link`     | `href`          | `null`       | Link destination.                                          |
| `pagination.link`     | `active`        | `false`      | Renders the page as current and non-clickable.             |
| `pagination.link`     | `disabled`      | `false`      | Renders the page as non-clickable.                         |
| `pagination.link`     | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the link renders as `<a>`.    |
| `pagination.previous` | `href`          | `null`       | Previous-page URL.                                         |
| `pagination.previous` | `disabled`      | `false`      | Forces the previous control to render disabled.            |
| `pagination.previous` | `label`         | `Previous`   | Visible label, hidden on small screens.                    |
| `pagination.previous` | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the control renders as `<a>`. |
| `pagination.next`     | `href`          | `null`       | Next-page URL.                                             |
| `pagination.next`     | `disabled`      | `false`      | Forces the next control to render disabled.                |
| `pagination.next`     | `label`         | `Next`       | Visible label, hidden on small screens.                    |
| `pagination.next`     | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the control renders as `<a>`. |
| `pagination.ellipsis` | `label`         | `More pages` | Accessible label for the ellipsis marker.                  |

## Components

| Component             | Element       | Slot                  |
|-----------------------|---------------|-----------------------|
| `pagination`          | `nav`         | `pagination`          |
| `pagination.content`  | `ul`          | `pagination-content`  |
| `pagination.item`     | `li`          | `pagination-item`     |
| `pagination.link`     | `a` or `span` | `pagination-link`     |
| `pagination.previous` | `a` or `span` | `pagination-previous` |
| `pagination.next`     | `a` or `span` | `pagination-next`     |
| `pagination.ellipsis` | `span`        | `pagination-ellipsis` |

## Styling Hooks

- `data-slot="pagination"`
- `data-slot="pagination-content"`
- `data-slot="pagination-item"`
- `data-slot="pagination-link"`
- `data-slot="pagination-previous"`
- `data-slot="pagination-previous-label"`
- `data-slot="pagination-next"`
- `data-slot="pagination-next-label"`
- `data-slot="pagination-ellipsis"`
