# Back to Top

Toggles `data-visible` and `inert` on the controller element as the window scrolls past a threshold,
and exposes a `scrollToTop` action that scrolls smoothly back to the top while respecting
`prefers-reduced-motion`.

Use the [`<hw:back-to-top>`](../components/back-to-top.md) component for accessible markup and Nova preset styling.
The standalone controller ships no styling, so custom markup must provide its own visible states.

**Identifier:** `back-to-top`  
**Load:** Automatically after `php artisan hotwire:install`; publish with `php artisan hotwire:controllers back-to-top`
only to customize it.

## Requirements

- No external dependencies.

## Values

| Value       | Type   | Default | Description                                                              |
|-------------|--------|---------|--------------------------------------------------------------------------|
| `threshold` | Number | `400`   | Pixels of `window.scrollY` after which `data-visible` flips to `"true"`. |

## Actions

| Action        | Description                                                                                |
|---------------|--------------------------------------------------------------------------------------------|
| `scrollToTop` | Scrolls the window to `(0, 0)` with `behavior: "smooth"` (or `"auto"` for reduced motion). |

## Behavior

- Listens on `window` `scroll` with a `requestAnimationFrame` throttle (one frame per update).
- Writes `data-visible="true"` when `window.scrollY > threshold`, `"false"` otherwise. Strictly
  greater than — at the exact threshold the button stays hidden.
- Removes `inert` while visible and restores it while hidden so the button follows its visual state in the focus order.
- `data-visible` is set on `connect()` so the initial state matches the current scroll position.
- Cleans up the listener and any pending frame in `disconnect()`.

## Basic Usage

```html

<button
    type="button"
    data-controller="back-to-top"
    data-action="back-to-top#scrollToTop"
    data-visible="false"
    class="fixed bottom-6 end-6 rounded-full bg-primary p-3 text-primary-foreground shadow-lg
           transition-opacity duration-200
           data-[visible=false]:opacity-0 data-[visible=false]:pointer-events-none
           data-[visible=true]:opacity-100"
    aria-label="Back to top"
    inert
>
    ↑
</button>
```

The `data-[visible=...]` Tailwind variants drive the fade and pointer handling. The controller synchronizes `inert` so
the hidden button also stays out of the keyboard focus order.

## Customizing the threshold

```html

<button
    type="button"
    data-controller="back-to-top"
    data-back-to-top-threshold-value="800"
    data-action="back-to-top#scrollToTop"
    data-visible="false"
    aria-label="Back to top"
    inert
>
    ↑
</button>
```

`800` keeps the button hidden on shorter pages and only reveals it after a full viewport-or-two of
scrolling.

## With an SVG icon

```html

<button
    type="button"
    data-controller="back-to-top"
    data-action="back-to-top#scrollToTop"
    class="..."
    aria-label="Back to top"
>
    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>
```

## Reduced motion

When the user has `prefers-reduced-motion: reduce` set, `scrollToTop` uses
`behavior: "auto"` so the page jumps instantly instead of animating. No configuration required.

## CSS-only alternative

Without Tailwind, drive the same effect with plain CSS:

```css
[data-controller~="back-to-top"] {
    opacity: 0;
    pointer-events: none;
    transition: opacity 200ms ease;
}

[data-controller~="back-to-top"][data-visible="true"] {
    opacity: 1;
    pointer-events: auto;
}
```
