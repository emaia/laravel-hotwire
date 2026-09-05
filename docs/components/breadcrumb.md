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
]" frame="content" />
```

The root `frame` target is applied to generated links. An item descriptor can replace it with its own `frame`, or set
`'frame' => false` to suppress targeting for that item. Current pages, items without `href`, and ellipses omit frame
metadata.

`items` and slot composition are mutually exclusive: passing both raises an exception instead of silently dropping the
composed trail. Breadcrumb owns the `<ol>` when it generates items, so the two cannot be interleaved. Generated items
render through the same `breadcrumb.list`, `breadcrumb.item`, `breadcrumb.link`, `breadcrumb.page` and
`breadcrumb.ellipsis` subcomponents used in manual composition.

## Ellipsis

In `items`, an ellipsis is visual and accessible only — it marks that the trail was shortened, without offering the
pages it hides.

```blade
<hw:breadcrumb :items="[
    ['label' => 'Dashboard', 'href' => route('dashboard')],
    ['type' => 'ellipsis', 'label' => 'More pages'],
    ['label' => 'Projects', 'href' => route('projects.index')],
    ['label' => $project->name],
]" />
```

### Collapsed pages in a dropdown

When the ellipsis should open the pages it collapsed, compose the trail and put a `<hw:dropdown>` in the item. This is
composition-only on purpose: `items` stays declarative and Breadcrumb ships no JavaScript of its own, so the Dropdown
controller is a cost only the trails that need a menu pay.

```blade
<hw:breadcrumb>
    <hw:breadcrumb.list>
        <hw:breadcrumb.item>
            <hw:breadcrumb.link href="{{ route('dashboard') }}">Dashboard</hw:breadcrumb.link>
        </hw:breadcrumb.item>

        <hw:breadcrumb.separator />

        <hw:breadcrumb.item>
            <hw:dropdown>
                <hw:dropdown.trigger aria-label="Show collapsed pages">
                    <hw:breadcrumb.ellipsis label="More pages" />
                </hw:dropdown.trigger>

                <hw:dropdown.content>
                    <hw:dropdown.item href="{{ route('projects.index') }}">Projects</hw:dropdown.item>
                    <hw:dropdown.item href="{{ route('projects.archived') }}">Archived</hw:dropdown.item>
                </hw:dropdown.content>
            </hw:dropdown>
        </hw:breadcrumb.item>

        <hw:breadcrumb.separator />

        <hw:breadcrumb.item>
            <hw:breadcrumb.page>{{ $project->name }}</hw:breadcrumb.page>
        </hw:breadcrumb.item>
    </hw:breadcrumb.list>
</hw:breadcrumb>
```

Give `dropdown.trigger` its own `aria-label`: the trigger is the button, and the ellipsis inside it does not name it.

A named crumb can open a menu the same way — put the label and a chevron in the trigger instead of the ellipsis, and
drop `<hw:breadcrumb.ellipsis>` entirely.

## Props

| Component             | Prop            | Default      | Description                                                                    |
|-----------------------|-----------------|--------------|--------------------------------------------------------------------------------|
| `breadcrumb`          | `label`         | `Breadcrumb` | Accessible label for the root `nav`.                                           |
| `breadcrumb`          | `items`         | `[]`         | Array of breadcrumb items. Use composed subcomponents for per-item attributes. |
| `breadcrumb`          | `ellipsisLabel` | `More pages` | Fallback label for `type => 'ellipsis'` items.                                 |
| `breadcrumb`          | `frame`         | `null`       | Turbo Frame target inherited by generated links.                              |
| `breadcrumb.link`     | `href`          | `null`       | Link destination.                                                              |
| `breadcrumb.link`     | `frame`         | `null`       | Turbo Frame target when the composed link has an `href`.                      |
| `breadcrumb.ellipsis` | `label`         | `More pages` | Accessible label for the ellipsis.                                             |

## Items API

| Key       | Required             | Description                                                                                                                    |
|-----------|----------------------|--------------------------------------------------------------------------------------------------------------------------------|
| `label`   | yes, except ellipsis | Escaped string, integer or `Stringable`; trusted `Htmlable` renders directly. Ellipsis items fall back to `ellipsisLabel`.      |
| `href`    | no                   | URL as a string or `Stringable`. Use `route()` explicitly when you need named routes.                                          |
| `current` | no                   | Boolean forcing the item to render as the current page.                                                                        |
| `type`    | no                   | `item` (default) or `ellipsis`. Any other value is rejected.                                                                   |
| `frame`   | no                   | Override the root frame target; use `false` to suppress it.                                                                    |

Descriptors are validated when the component is constructed, so a missing or mistyped key raises an exception instead of
rendering a silently incomplete trail.

The last item without `href` is inferred as the current page. That is positional inference within the array, not URL
matching: `<hw:navbar>` deliberately refuses to infer `current` at all, because there the state depends on the request
rather than on an item's place in a list.

A trail has one current page, so at most one item may resolve to one. An item resolves to the current page when it sets
`current` or has no `href`. When more than one would qualify, give the intermediate items an `href` or compose the trail
manually.

Automatic URL matching, nested dropdown data and per-item attributes are intentionally left out of v1.

Frame values accept strings or objects resolved with `dom_id()`; null, false, empty, and whitespace-only values are
omitted. Href-less links omit frame metadata. On `<hw:breadcrumb.link>`, an explicit `data-turbo-frame` wins over `frame`
and can be bound to `false` to suppress it.

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

## Styling hooks

- `data-slot="breadcrumb"`
- `data-slot="breadcrumb-list"`
- `data-slot="breadcrumb-item"`
- `data-slot="breadcrumb-link"`
- `data-slot="breadcrumb-page"`
- `data-slot="breadcrumb-separator"`
- `data-slot="breadcrumb-ellipsis"`
