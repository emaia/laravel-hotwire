# Presets

Laravel Hotwire components render semantic attributes (`data-slot`, `data-variant`, `data-size`, `data-state`). Presets turn those attributes into Tailwind styles.

## Install with a preset

```bash
php artisan hotwire:install --preset=nova
```

The installer writes a thin `resources/css/app.css` that imports Tailwind and enables one preset:

```css
@import "tailwindcss";

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
```

That public entrypoint aggregates Nova's ordered visual sources. Their internal paths are an implementation detail;
applications should keep importing `presets/nova.css` rather than individual package files.

## Generate a custom preset

Generate an empty preset scaffold when token overrides are not enough:

```bash
php artisan hotwire:make-preset brand
```

The command creates `resources/css/presets/brand.css`. It imports the package token, custom-variant and structural
layers — the last carrying the runtime utility safelist, so your preset picks up new package mechanics on upgrade
instead of freezing them — and mirrors every rule the shipped presets define with an empty body, grouped by the
component that owns it. You get the full set of selectors to fill in — including the ones whose state lives on an
ancestor, which no summary of a slot's own attributes can express:

```css
@layer components {

    /* Accordion */
    [data-slot="accordion"] {}
    [data-slot="accordion-item"] {}
    [data-slot="accordion-trigger"] {}
    [data-slot="accordion-item"][aria-disabled="true"] > [data-slot="accordion-trigger"] {}
    [data-slot="accordion-trigger-icon"] {}
    [data-slot="accordion-item"][open] > [data-slot="accordion-trigger"] [data-slot="accordion-trigger-icon"] {}
}
```

At-rules that qualify a rule (`@supports`, `@media`) come along, since the rule inside them means nothing on its own.
Rules for structural slots do not, nor does anything `structural.css` owns — presets are not expected to restate the
mechanics, which is why the Accordion's `::details-content` block is absent above. Deleting a selector you have no use
for is part of writing the preset; what the scaffold guarantees is that nothing the shipped presets style is missing.

Replace the vendor preset import in `resources/css/app.css` with the line printed by the command:

```css
@import './presets/brand.css';
```

To customize Nova instead of starting from empty selectors, clone it into the application:

```bash
php artisan hotwire:make-preset brand --from=nova
```

The clone is one application-owned file: package foundation imports are rewritten to their vendor paths and Nova's
private visual sources are flattened in canonical order. It never leaves imports to package-internal module paths.

Use `--force` to replace an existing generated file. The command never edits `resources/css/app.css`, so application
styles and import ordering remain under your control.

The rules arrive in the order the source preset declares them, and that order is worth keeping. Between equal-specificity
rules in the same layer, the later one wins. Presets may also declare ordered component sublayers; the scaffold preserves
those boundaries and their explicit order. Reordering the scaffold as you fill it in can therefore change which rule
applies without changing a single declaration.

### A note on IDE warnings

PhpStorm reports hundreds of `'x' applies the same CSS properties as 'y'` warnings on a preset — for example
`has-[[data-variant=inset]]:bg-sidebar` against `bg-background`, or `[&>a:hover]:text-primary` against
`text-muted-foreground`. These are false positives: its Tailwind support does not model variants inside `@apply`, so it
reads a conditional utility as an unconditional declaration and sees a duplicate where there is none. Nova alone carries
552 variant-prefixed utilities. Rewriting each state as its own rule to silence the inspection would multiply the file
for no gain.

## Structural and visual CSS

CSS that makes a component work — as opposed to CSS that gives it a look — lives in `resources/css/structural.css`. The
test is whether breaking the rule leaves the component broken rather than restyled. It owns the carousel's viewport
overflow, flex track, axis and slide sizing through `data-carousel-*` hooks, and the Accordion's `::details-content`
collapse, which needs `allow-discrete` and `calc-size(auto, size)` or the panel snaps shut instead of animating.

Every preset imports that file, so the behavior compiles into your stylesheet and holds on the first paint — no waiting
for the bundle to run — and no preset has to rediscover it. Override the timing if you want (`transition-duration` on
`::details-content`); you never restate the mechanism.

CSS that defines appearance belongs in a preset and targets `data-slot`. Carousel buttons, dots, progress and counter
are visual, so presets own them. Controller CSS must not choose their colors, radius or spacing; preset CSS must not
duplicate the controller's geometry. Slots that are presentation-free containers, assistive nodes or controller-owned
structure are marked `structural` in the catalog and are intentionally omitted from an empty scaffold.

The generated file is an inventory, not a complete design. State relationships, motion, top-layer resets and compound
selectors cannot be inferred from slot names alone. Use Nova and the component docs as references when implementing
those behaviors in a new design.

Each slot is scaffolded once, under the first catalog entry that declares it, with one selector per slot and the
attribute values Nova differentiates it by commented directly above it. Slots that share an appearance are better
written as a single grouped rule — Nova styles every button-like slot through one
`:is([data-slot="button"], [data-slot="modal-trigger"], …)` selector rather than repeating the same declarations.

## Override a component

Add app CSS after the preset import and target semantic slots:

```css
[data-slot="button"][data-variant="default"] {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
```

## Surface motion

Dropdown, Popover, Hover Card, Multi Select and Tooltip share state-driven Presence styling. Their floating content uses
`data-state="open|closed"` and `data-motion="default|none"`; server-rendered closed content also starts with
`hidden inert`.

Modal, Alert Dialog, Drawer and Sheet use the same `data-state`, `data-motion`, `hidden`, and `inert` contract on their
overlay target. Sidebar keeps desktop `data-state="expanded|collapsed"` and uses `data-mobile-state="open|closed"` for
mobile Presence. Their backdrop and panel transitions are observed together, so the longest finite motion determines
settlement.

While one of these modal overlays stays connected, Turbo morphs can update its contents but do not overwrite
`data-state` (or Sidebar's `data-mobile-state`), `data-presence`, `hidden`, `inert`, or active top-layer attributes.
`data-motion` and attributes on ordinary descendants remain morphable; nested overlays independently protect their own
targets. An open overlay therefore stays open through refresh morphs; close it through its public action or frame/stream
lifecycle rather than relying on closed server-rendered attributes.

The selected preset transitions only `opacity`, `scale`, and `translate`. Presence suppresses transition and animation while
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

Scope overlay state selectors to direct children. A descendant selector from an open parent Modal can otherwise apply
the open visual state to a nested Modal or Alert Dialog before that child opens:

```css
[data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] {
    opacity: 1;
}
```

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
