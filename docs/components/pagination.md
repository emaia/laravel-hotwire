# Pagination

Semantic pagination navigation with composed subcomponents and automatic Laravel paginator modes.

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

Pass Laravel's paginator instance to render links automatically.

```blade
<hw:pagination :paginator="$users" />
```

`LengthAwarePaginator` renders previous, numbered page links, ellipsis markers and next controls. `Paginator` from
`simplePaginate()` and `CursorPaginator` from `cursorPaginate()` render previous and next controls only because Laravel
does not expose numbered page windows for those modes.

When the paginator has no extra pages, the component renders nothing. Use Laravel's `onEachSide()` before passing the
paginator when you need a different page window.

```blade
<hw:pagination :paginator="$users->onEachSide(1)" />
```

## Display Modes

Use `display` to choose which automatic links render.

```blade
<hw:pagination :paginator="$users" display="numbers" />

<hw:pagination :paginator="$users" display="controls" />

<hw:pagination :paginator="$users" display="icons" />
```

`display="full"` is the default and renders previous, numbers and next for length-aware paginators. `numbers` renders
only numbered page links and ellipsis markers. `controls` renders only previous and next controls with labels. `icons`
renders only previous and next controls without visible labels.

Pass an empty or null control label when you want icon-only previous and next controls inside the full paginator.

```blade
<hw:pagination previous-label="" next-label="" :paginator="$users" />
```

## Turbo Frames

For frame pagination, wrap both the paginated content and the pagination controls in the same `<turbo-frame>`. Turbo
automatically scopes clicks inside the frame back to that frame, so no `turbo-frame` prop is needed.

```blade
<turbo-frame id="users">
    @include('users.table', ['users' => $users])

    <hw:pagination :paginator="$users" />
</turbo-frame>
```

If the browser URL should track frame pagination, put `data-turbo-action="advance"` or `data-turbo-action="replace"`
on the frame itself.

Use `turbo-frame="_top"` when pagination renders inside a frame but should navigate the full page instead of updating
only the frame.

```blade
<turbo-frame id="users">
    @include('users.table', ['users' => $users])

    <hw:pagination :paginator="$users" turbo-frame="_top" />
</turbo-frame>
```

In manual mode, pass `data-turbo-frame` directly to each link component.

```blade
<hw:pagination.link href="{{ $users->url(2) }}" data-turbo-frame="users">2</hw:pagination.link>
```

## Turbo Streams

Do not place automatic pagination outside a frame and point it back at the frame unless something else also updates the
pagination controls. Turbo Frames replace only the target frame, so controls outside the frame would keep stale links.

When controls must live outside the replaced region, use `turbo-stream` and return streams that update both the content
and the pagination controls.

```blade
<div id="users-table">
    @include('users.table', ['users' => $users])
</div>

<div id="users-pagination">
    <hw:pagination :paginator="$users" turbo-stream />
</div>
```

```php
public function index()
{
    $users = User::query()->paginate();

    if (request()->wantsTurboStream()) {
        return turbo_stream()
            ->update('users-table', view('users.table', compact('users')))
            ->update('users-pagination', view('users.pagination', compact('users')));
    }

    return view('users.index', compact('users'));
}
```

In manual mode, pass `data-turbo-stream` directly to each link component.

```blade
<hw:pagination.link href="{{ $users->url(2) }}" data-turbo-stream>2</hw:pagination.link>
```

## Accessibility Labels

Previous and next controls always render an `aria-label`, including icon-only controls. Customize these labels when the
visible control text is hidden or when the paginator needs domain-specific wording.

```blade
<hw:pagination
    :paginator="$users"
    display="icons"
    previous-aria-label="Previous users page"
    next-aria-label="Next users page"
/>
```

## Props

| Component             | Prop                | Default               | Description                                                               |
|-----------------------|---------------------|-----------------------|---------------------------------------------------------------------------|
| `pagination`          | `paginator`         | `null`                | Optional Laravel paginator for automatic links.                           |
| `pagination`          | `label`             | `Pagination`          | Accessible label for the root `nav`.                                      |
| `pagination`          | `turboFrame`        | `null`                | Turbo Frame target for generated links.                                   |
| `pagination`          | `turboStream`       | `false`               | Adds `data-turbo-stream` to generated links.                              |
| `pagination`          | `previousLabel`     | `Previous`            | Visible previous label; empty or null renders icon-only.                  |
| `pagination`          | `nextLabel`         | `Next`                | Visible next label; empty or null renders icon-only.                      |
| `pagination`          | `previousAriaLabel` | `Go to previous page` | Accessible label for the generated previous control.                      |
| `pagination`          | `nextAriaLabel`     | `Go to next page`     | Accessible label for the generated next control.                          |
| `pagination`          | `ellipsisLabel`     | `More pages`          | Accessible label for automatic ellipsis markers.                          |
| `pagination`          | `display`           | `full`                | Automatic mode: `full`, `numbers`, `controls` or `icons`.                 |
| `pagination.link`     | `href`              | `null`                | Link destination.                                                         |
| `pagination.link`     | `active`            | `false`               | Renders the page as current and non-clickable.                            |
| `pagination.link`     | `disabled`          | `false`               | Renders the page as non-clickable.                                        |
| `pagination.link`     | `size`              | `icon`                | Link size token.                                                          |
| `pagination.link`     | `turboFrame`        | `null`                | Adds `data-turbo-frame` when the link renders as `<a>`.                   |
| `pagination.link`     | `turboStream`       | `false`               | Adds `data-turbo-stream` when the link renders as `<a>`.                  |
| `pagination.previous` | `href`              | `null`                | Previous-page URL.                                                        |
| `pagination.previous` | `disabled`          | `false`               | Forces the previous control to render disabled.                           |
| `pagination.previous` | `label`             | `Previous`            | Visible label; empty or null omits the label span and uses `size="icon"`. |
| `pagination.previous` | `turboFrame`        | `null`                | Adds `data-turbo-frame` when the control renders as `<a>`.                |
| `pagination.previous` | `turboStream`       | `false`               | Adds `data-turbo-stream` when the control renders as `<a>`.               |
| `pagination.previous` | `ariaLabel`         | `Go to previous page` | Accessible label for the control.                                         |
| `pagination.previous` | `size`              | `default`             | Control size token. Empty labels force `icon`.                            |
| `pagination.next`     | `href`              | `null`                | Next-page URL.                                                            |
| `pagination.next`     | `disabled`          | `false`               | Forces the next control to render disabled.                               |
| `pagination.next`     | `label`             | `Next`                | Visible label; empty or null omits the label span and uses `size="icon"`. |
| `pagination.next`     | `turboFrame`        | `null`                | Adds `data-turbo-frame` when the control renders as `<a>`.                |
| `pagination.next`     | `turboStream`       | `false`               | Adds `data-turbo-stream` when the control renders as `<a>`.               |
| `pagination.next`     | `ariaLabel`         | `Go to next page`     | Accessible label for the control.                                         |
| `pagination.next`     | `size`              | `default`             | Control size token. Empty labels force `icon`.                            |
| `pagination.ellipsis` | `label`             | `More pages`          | Accessible label for the ellipsis marker.                                 |

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

The previous and next label hooks render only when their labels are not empty.
