# Breadcrumb

Semantic navigation trail with a composable API and an `items` shortcut for common Laravel pages.

## Usage

```blade
<hw:breadcrumb>
    <hw:breadcrumb.list>
        <hw:breadcrumb.item>
            <hw:breadcrumb.link href="{{ route('dashboard') }}">Dashboard</hw:breadcrumb.link>
        </hw:breadcrumb.item>
        <hw:breadcrumb.separator />
        <hw:breadcrumb.item>
            <hw:breadcrumb.link href="{{ route('projects.index') }}">Projects</hw:breadcrumb.link>
        </hw:breadcrumb.item>
        <hw:breadcrumb.separator />
        <hw:breadcrumb.item>
            <hw:breadcrumb.page>{{ $project->name }}</hw:breadcrumb.page>
        </hw:breadcrumb.item>
    </hw:breadcrumb.list>
</hw:breadcrumb>
```

Use `items` when the trail is plain links plus the current page.

```blade
<hw:breadcrumb :items="[
    ['label' => 'Dashboard', 'href' => route('dashboard')],
    ['label' => 'Projects', 'href' => route('projects.index')],
    ['label' => $project->name],
]" />
```

## Ellipsis

In `items`, ellipsis is visual and accessible only. Compose with Dropdown manually when it should open a menu.

```blade
<hw:breadcrumb :items="[
    ['label' => 'Dashboard', 'href' => route('dashboard')],
    ['type' => 'ellipsis', 'label' => 'More pages'],
    ['label' => 'Projects', 'href' => route('projects.index')],
    ['label' => $project->name],
]" />
```

## Props

| Component             | Prop            | Default      | Description                                                                    |
|-----------------------|-----------------|--------------|--------------------------------------------------------------------------------|
| `breadcrumb`          | `label`         | `Breadcrumb` | Accessible label for the root `nav`.                                           |
| `breadcrumb`          | `items`         | `null`       | Array of breadcrumb items. Use composed subcomponents for per-item attributes. |
| `breadcrumb`          | `ellipsisLabel` | `More pages` | Fallback label for `type => 'ellipsis'` items.                                 |
| `breadcrumb.link`     | `href`          | `null`       | Link destination.                                                              |
| `breadcrumb.ellipsis` | `label`         | `More pages` | Accessible label for the ellipsis.                                             |

## Items API

| Key       | Description                                                        |
|-----------|--------------------------------------------------------------------|
| `label`   | Text or `Htmlable` content for regular items.                      |
| `href`    | Optional URL. Use `route()` explicitly when you need named routes. |
| `current` | Forces the item to render as the current page.                     |
| `type`    | Use `ellipsis` to render a non-interactive ellipsis.               |

The last item without `href` is inferred as the current page. Automatic URL matching, nested dropdown data and per-item
attributes are intentionally left out of v1.

## Components

| Component              | Element | Slot                   |
|------------------------|---------|------------------------|
| `breadcrumb`           | `nav`   | `breadcrumb`           |
| `breadcrumb.list`      | `ol`    | `breadcrumb-list`      |
| `breadcrumb.item`      | `li`    | `breadcrumb-item`      |
| `breadcrumb.link`      | `a`     | `breadcrumb-link`      |
| `breadcrumb.page`      | `span`  | `breadcrumb-page`      |
| `breadcrumb.separator` | `li`    | `breadcrumb-separator` |
| `breadcrumb.ellipsis`  | `span`  | `breadcrumb-ellipsis`  |

## Styling Hooks

- `data-slot="breadcrumb"`
- `data-slot="breadcrumb-list"`
- `data-slot="breadcrumb-item"`
- `data-slot="breadcrumb-link"`
- `data-slot="breadcrumb-page"`
- `data-slot="breadcrumb-separator"`
- `data-slot="breadcrumb-ellipsis"`
