# View Transition

Applies the [View Transitions API](https://developer.mozilla.org/en-US/docs/Web/API/Document/startViewTransition) when rendering Turbo Frame content, adding native browser transition animations.

**Identifier:** `view-transition`
**Install:** `php artisan hotwire:controllers turbo/view-transition`

## Requirements

- No external dependencies.
- Browser with View Transitions API support (Chrome 111+, Edge 111+). In unsupported browsers, the controller is a no-op.

## Usage

Add the controller directly on the `<turbo-frame>`:

```html
<turbo-frame
    id="content"
    data-controller="view-transition"
    src="/items"
>
    ...
</turbo-frame>
```

When the frame receives new content, the transition is animated automatically via `document.startViewTransition()`.

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
    data-controller="view-transition"
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
