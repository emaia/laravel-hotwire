# Presets

Laravel Hotwire components render semantic attributes (`data-slot`, `data-variant`, `data-size`, `data-state`). Presets turn those attributes into Tailwind styles.

## Install with a preset

```bash
php artisan hotwire:install --preset=nova
```

The installer writes a thin `resources/css/app.css` that imports Tailwind, scans package CSS, and enables one preset:

```css
@import "tailwindcss";

@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
```

## Override a component

Add app CSS after the preset import and target semantic slots:

```css
[data-slot="button"][data-variant="default"] {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
```

## Floating surface motion

Dropdown, Popover, Hover Card, Multi Select and Tooltip share state-driven Presence styling. Their floating content uses
`data-state="open|closed"` and `data-motion="default|none"`; server-rendered closed content also starts with
`hidden inert`.

The Nova preset transitions only `opacity`, `scale`, and `translate`. Presence suppresses transition and animation while
the first placement is prepared, so a resolved flip cannot animate the closed transform before enter begins. During
exit, Presence sets `data-state="closed"`
and `inert` immediately but waits for the element's CSS transition or finite animation before applying `hidden`. A
closed-state rule must therefore remain a visual state and must never set `display: none` or otherwise hide the element.

Override motion after importing the preset:

```css
[data-slot="popover-content"] {
    transition: opacity 200ms ease, scale 200ms ease, translate 200ms ease;
}

[data-slot="popover-content"][data-state="closed"] {
    opacity: 0;
    scale: .97;
    translate: 0 -.25rem;
}

[data-slot="popover-content"][data-state="open"] {
    opacity: 1;
    scale: 1;
    translate: 0 0;
}
```

The same state hooks can drive custom CSS animations. `motion="none"` on supported Blade APIs, or
`data-tooltip-motion-value="none"` for the standalone Tooltip controller, skips motion. The shared Presence helper
temporarily suppresses custom CSS transition and animation in this mode, does the same for
`prefers-reduced-motion: reduce`, and cancels stale exit cleanup when a surface rapidly reopens.

Side-aware styles should use `data-side` and `data-align`; these attributes reflect Floating UI's resolved placement
after any flip. The preset also resets native Popover margins and removes the browser border only from borderless
floating slots, preserving component borders and overflow while a surface participates in the top layer.

## Scoped overrides

The selected preset applies globally. Preset files may also include scoped selectors, so app CSS can opt a page region into targeted overrides with `data-preset`:

```blade
<section data-preset="compact">
    <hw:button>Save</hw:button>
</section>
```

Only CSS that has been imported can respond to `data-preset`. A `data-preset` attribute does nothing by itself.
