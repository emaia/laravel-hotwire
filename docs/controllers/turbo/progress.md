# Progress

Shows the native Turbo Drive progress bar during Turbo Frame and Stream requests, which by default only appears on full-page navigations.

**Identifier:** `progress`

## Requirements

- `@hotwired/turbo`

## Usage

Add the controller to a global element in the layout (e.g. `<body>` or a wrapper):

```html
<body data-controller="progress">
    ...
</body>
```

From that point, any Turbo Frame or Stream request will show the progress bar at the top of the page.

## How it works

| Turbo event | Action |
|---|---|
| `turbo:before-fetch-request` | Shows the progress bar |
| `turbo:frame-render` | Hides the progress bar |
| `turbo:before-stream-render` | Hides the progress bar |

## Example with Turbo Frame

```html
<body data-controller="progress">
    <turbo-frame id="items" src="/items">
        <!-- The progress bar appears while this frame loads -->
    </turbo-frame>
</body>
```

## Example with lazy loading

```html
<body data-controller="progress">
    <turbo-frame id="dashboard-stats" src="/dashboard/stats" loading="lazy">
        <p>Loading stats...</p>
    </turbo-frame>
</body>
```
