# View Transition

Applies the [View Transitions API](https://developer.mozilla.org/en-US/docs/Web/API/Document/startViewTransition) when rendering Turbo Frame content, adding native browser transition animations.

**Identifier:** `turbo--view-transition`

Package controllers auto-load from the vendor directory. Run
`php artisan hotwire:controllers turbo/view-transition` only when you want to publish this controller for customization.

## Requirements

- No external dependencies.
- Browser with View Transitions API support (Chrome 111+, Edge 111+). In unsupported browsers, the controller is a no-op.

## Usage

Add the controller directly on the `<turbo-frame>`:

```html
<turbo-frame
    id="content"
    data-controller="turbo--view-transition"
    src="/items"
>
    ...
</turbo-frame>
```

When the frame receives new content, the transition is animated automatically via `document.startViewTransition()`.

Blade components expose the same integration as a boolean attribute:

```blade
<hw:frame id="content" view-transition>
    ...
</hw:frame>

<hw:modal frame="modal" view-transition />
<hw:sheet frame="settings-panel" view-transition />
<hw:drawer frame="drawer-panel" view-transition />
```

For frame-backed overlays, the controller is mounted on the internal persistent frame host, not the overlay root. This
animates navigation between already-open frame contents without replacing or reopening the overlay. Without `frame`, the
overlay option emits no controller or additional markup.

Putting `view-transition` only on a `<hw:frame-or-page>` response is not enough to configure a host that already exists
in the page. Turbo preserves the current frame element and replaces its children; it does not copy attributes from the
matching response frame. Enable the option on the layout-owned Modal, Sheet, or Drawer host.

## Values

| Value          | Type      | Default | Description                                                                  |
|----------------|-----------|---------|------------------------------------------------------------------------------|
| `skip-initial` | `Boolean` | `false` | Skip the render that fills an empty frame, so only later navigation animates. |

Frame-backed overlays set `skip-initial` automatically. Their opening keeps the overlay's existing motion and backdrop;
only later navigation inside the open host uses View Transitions. Clearing the host resets the behavior for its next
opening.

"Empty" is measured on the host itself. A host that already carries server-rendered content — a `modal.content`,
`sheet.content`, or `drawer.content` with fallback markup — counts as filled, so its very next navigation animates
instead of being skipped.

## With custom transition CSS

The View Transitions API uses CSS pseudo-elements to control animations:

```css
::view-transition-old(root) {
    animation: fade-out 0.2s ease-in;
}

::view-transition-new(root) {
    animation: fade-in 0.2s ease-out;
}

@keyframes fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
```

## Example — list with transition

```html
<turbo-frame
    id="items-list"
    data-controller="turbo--view-transition"
>
    @foreach ($items as $item)
        <div>{{ $item->title }}</div>
    @endforeach

    {{ $items->links() }}
</turbo-frame>
```

When navigating between pagination pages, the content transitions smoothly.

## How it works

The controller intercepts the `turbo:before-frame-render` event and wraps the original render inside `document.startViewTransition()`. If the API is unavailable, the default render occurs without changes.
