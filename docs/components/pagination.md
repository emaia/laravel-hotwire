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

| Component             | Prop            | Default      | Description                                                               |
|-----------------------|-----------------|--------------|---------------------------------------------------------------------------|
| `pagination`          | `paginator`     | `null`       | Optional Laravel paginator for automatic links.                           |
| `pagination`          | `label`         | `Pagination` | Accessible label for the root `nav`.                                      |
| `pagination`          | `turboFrame`    | `null`       | Turbo Frame target for generated links.                                   |
| `pagination`          | `previousLabel` | `Previous`   | Visible previous label; empty or null renders icon-only.                  |
| `pagination`          | `nextLabel`     | `Next`       | Visible next label; empty or null renders icon-only.                      |
| `pagination`          | `ellipsisLabel` | `More pages` | Accessible label for automatic ellipsis markers.                          |
| `pagination`          | `display`       | `full`       | Automatic mode: `full`, `numbers`, `controls` or `icons`.                 |
| `pagination.link`     | `href`          | `null`       | Link destination.                                                         |
| `pagination.link`     | `active`        | `false`      | Renders the page as current and non-clickable.                            |
| `pagination.link`     | `disabled`      | `false`      | Renders the page as non-clickable.                                        |
| `pagination.link`     | `size`          | `icon`       | Link size token.                                                          |
| `pagination.link`     | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the link renders as `<a>`.                   |
| `pagination.previous` | `href`          | `null`       | Previous-page URL.                                                        |
| `pagination.previous` | `disabled`      | `false`      | Forces the previous control to render disabled.                           |
| `pagination.previous` | `label`         | `Previous`   | Visible label; empty or null omits the label span and uses `size="icon"`. |
| `pagination.previous` | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the control renders as `<a>`.                |
| `pagination.previous` | `size`          | `default`    | Control size token. Empty labels force `icon`.                            |
| `pagination.next`     | `href`          | `null`       | Next-page URL.                                                            |
| `pagination.next`     | `disabled`      | `false`      | Forces the next control to render disabled.                               |
| `pagination.next`     | `label`         | `Next`       | Visible label; empty or null omits the label span and uses `size="icon"`. |
| `pagination.next`     | `turboFrame`    | `null`       | Adds `data-turbo-frame` when the control renders as `<a>`.                |
| `pagination.next`     | `size`          | `default`    | Control size token. Empty labels force `icon`.                            |
| `pagination.ellipsis` | `label`         | `More pages` | Accessible label for the ellipsis marker.                                 |

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
