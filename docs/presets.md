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

## Nova's reference boundary

Nova was normalized against the shadcn `ui` repository at commit
[`71e5095`](https://github.com/shadcn-ui/ui/commit/71e50952fbb7eda2c992660d36cd58671a2edf42). This is a one-time design
reference, not a promise of continuous synchronization or pixel parity. Nova is package-owned after that comparison and
evolves with Laravel Hotwire's own component contracts.

The reference supplies the compact neutral density, radii and surface vocabulary. Laravel Hotwire deliberately keeps
native Checkbox, Radio, Switch, Select and Slider elements; Blade-rendered content; real links and buttons in Dropdown;
server-rendered overlay trees; and its ECharts, Leaflet, Tiptap, Turbo, File Upload, Reveal and Toast integrations. Those
differences are adaptations or package features, not missing shadcn behavior.

Accessibility and runtime behavior take precedence over visual parity. The package keeps contrast-adjusted foreground
tokens, a neutral sidebar primary, forced-colors and print fallbacks, Presence-driven `hidden`/`inert` settlement,
top-layer resets, focus management, Floating UI placement and Turbo morph protection. Physical `left`/`right` overlay
APIs also remain physical in RTL; inline `start`/`end` semantics continue to mirror.

The normalized ownership boundary is:

- `tokens.css`: semantic palette, including the shared translucent `--backdrop` color.
- `structural.css`: required geometry, top-layer resets, progressive enhancement, accessibility mechanics and layer-safe
  fallbacks needed when no preset supplies them.
- Preset modules: visual surfaces, spacing, typography, radii, state treatment and motion choices or overrides.
- Component-specific modules: package-only features and selectors that cannot be shared without coupling unrelated APIs.

Shared control, floating-panel, backdrop and native-indicator surfaces may be consolidated further without exposing the
module topology as public API. Such extraction must preserve selector specificity, cascade order and the public
`presets/nova.css` entrypoint.

## Generate a selective bundle

The complete preset is the safe default. For a layout that uses a known subset of components, generate an
application entrypoint without the omitted visual modules:

```bash
php artisan hotwire:styles \
  --preset=nova \
  --components=badge,button,field,input,navbar,pagination,popover \
  --include=tooltip \
  --output=resources/css/hotwire-front.css
```

Replace the complete preset import in that layout's CSS entrypoint with the generated file. Do not import both:

```css
@import "tailwindcss";

@import "./hotwire-front.css";
```

`--components` accepts catalog component keys and may be repeated or comma-separated. The command automatically
includes controllers mounted by those components, shared visual modules and their transitive dependencies.
`--include` accepts additional component keys or Stimulus controller identifiers; use it for UI rendered dynamically
by PHP, Turbo Streams, vendor views or JavaScript when that UI is not represented by the layout's initial component
list. Controller identifiers with `--` may also use their publish form, such as `turbo/progress`. The output must stay
under `resources/css`, which is the same boundary `hotwire:check` audits. Paths elsewhere in the application, including
`vendor`, are rejected.

Tokens, custom variants and structural CSS remain complete foundations and are imported exactly once. The selective
part is the preset's visual layer, so progressive enhancement and runtime utility coverage do not depend on which
components were listed.

The generated file starts with the package marker and should not be edited. Re-run the same command with `--force`
after changing the selection or upgrading Laravel Hotwire. Only an existing `hotwire:styles` bundle is replaceable;
application-owned files and other package-marked CSS are never replaced, even with `--force`. If the complete set of
dynamic components is not known, keep the public `presets/nova.css` import as the fallback instead of guessing.

Generated bundles also record their canonical component, controller and module selection in a versioned header.
`hotwire:check` inspects marked bundles under `resources/css` and reports visual components/controllers found in the
scanned Blade views when none of those bundles covers them. With multiple layout bundles this is deliberately a global
safety net, not layout inference: coverage in any generated bundle satisfies the check. If any CSS entrypoint under
`resources/css` retains an official complete preset import, or imports an application preset from
`resources/css/presets`, it acts as complete coverage for mixed-layout applications. The check also reconstructs each
generated bundle from its recorded plan, so stale or truncated CSS fails even when its metadata remains intact. `--fix`
never changes a CSS selection because it cannot know which layout should own the missing component; regenerate the
appropriate bundle explicitly. Dynamic PHP, Turbo or JavaScript markup still requires `--include` because static view
scanning cannot see it.

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
Named keyframes are not scaffolded; Reveal inherits layer-safe fallback definitions from `structural.css`, and a preset
can redefine those names in its own `components` layer.
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
rules in the same layer, the later one wins. Reordering the scaffold as you fill it in can therefore change which rule
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
for the bundle to run — and no preset has to rediscover it. Its Accordion motion fallback lives in the `components`
layer: override the timing if you want (`transition-duration` on `::details-content`); you never restate the mechanism.

The same foundation owns a minimum accessibility baseline for custom-painted controls. In forced-colors mode, native
Checkbox, Radio and Switch rendering returns so the browser can preserve checked, indeterminate, focus and disabled
states; Slider, Multi Select and Progress use system colors or non-background state marks. Print similarly restores
native form controls and keeps selection/progress observable without requiring background graphics. These rules do not
hide navigation, expand disclosure content or otherwise decide application print layout.

The baseline lives in `@layer hotwire-accessibility` with low-specificity selectors. A preset can refine it without
`!important` by writing a later rule in that same layer:

```css
@layer hotwire-accessibility {
    @media (forced-colors: active) {
        [data-slot="switch"] {
            /* A preset-specific system-color treatment. */
        }
    }
}
```

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

Use logical properties for inline semantics: `start`/`end`, `ps`/`pe`, `ms`/`me`, `border-s`/`border-e`,
`rounded-s`/`rounded-e`, and `text-start`/`text-end`. Attributes named `inline-start`, `inline-end`, `align=start`, or
`align=end` follow the document direction. Horizontal transforms have no logical equivalent, so provide an explicit
`:where(:dir(rtl), [dir="rtl"], [dir="rtl"] *)` inversion, or Tailwind's equivalent `rtl:` variant, when they
represent inline movement. The attribute-backed branches survive production CSS compatibility transforms that may
lower bare `:dir(rtl)` to language selectors. Test custom presets in both inherited `dir="ltr"` and `dir="rtl"` scopes.

Physical side APIs are the exception. `side=left|right` and `direction=left|right` on Sidebar, Sheet, Drawer, Side
Panel, and floating surfaces continue to mean the viewport's physical edge in either document direction. Side-aware
floating styles should use `data-side` and `data-align`; these attributes reflect Floating UI's resolved placement
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
