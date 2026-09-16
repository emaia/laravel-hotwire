# Presets

Laravel Hotwire components render semantic attributes (`data-slot`, `data-variant`, `data-size`, `data-state`). Presets turn those attributes into Tailwind styles.

## Install with a preset

```bash
php artisan hotwire:install --preset=nova
php artisan hotwire:install --preset=bloom
```

Nova remains the default compact, neutral preset. Bloom is the spacious and elevated alternative: a chromatic palette,
restrained control radii, status-tinted notification surfaces, an offset focus outline and softer motion.
Both style the same Blade markup, semantic slots, controllers and accessibility states; choose one preset per build.
Bloom re-declares shared tokens and registers a small set of additional status and geometry tokens; shared application
overrides work the same under either preset — see [`theming.md`](theming.md#preset-owned-tokens).

The installer writes a thin `resources/css/app.css` that imports Tailwind, enables one preset and keeps the other
official choices visible beside it:

```css
@import "tailwindcss";

/* Presets: keep exactly one import active. Nova is the default. */
@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
/* @import '../../vendor/emaia/laravel-hotwire/resources/css/presets/bloom.css'; */
```

Switch presets by commenting the active import and uncommenting another one. Alternatively, pass `--preset=bloom`
during installation; the command writes Bloom as the one active import and leaves Nova commented. Do not enable two
complete presets in the same CSS entrypoint; `hotwire:check` reports the entrypoint and active preset names when it
finds more than one.

That public entrypoint imports `foundation.css`, then aggregates the selected preset's ordered visual sources. Their
grouping and internal paths are implementation details; applications should keep importing the public
`presets/<name>.css` entrypoint rather than individual package files.

The available CSS artifacts have different ownership and upgrade behavior:

| Artifact | Ownership and maintenance |
|----------|---------------------------|
| Official preset import | Package-maintained; updates with Composer and is checked as a complete preset in package CI |
| `hotwire:make-preset` scaffold or clone | Application-owned snapshot; edit it, validate it and merge relevant package upgrade changes manually |
| `hotwire:styles` bundle | Package-generated subset; do not edit it, and regenerate it when its recorded plan becomes stale |

### CSS layers and ownership

The cascade has four ordered layers:

1. `foundation.css`, the package-managed public facade over shared `tokens.css`, `custom-variants.css` and
   `structural.css`;
2. the preset base, package-managed in an official preset and copied into an application-owned clone;
3. selectable visual modules owned by components, families or controllers;
4. application overrides loaded after the preset.

Shared foundations define the common semantic token contract, Tailwind variants, mechanics and accessibility baseline.
The facade is their only recommended public entrypoint and keeps their internal files and order private. Preset base
defines defaults belonging to one visual language and is included before modules even when a selective bundle resolves
no visual module. It may declare custom properties in supported light/dark/root scopes and aliases in `@theme inline`;
component selectors, structural rules and global visual properties remain outside it.

Official preset authors classify every registered additional property in `styles.php`: `global` requires a top-level
`:root` declaration, while `themed` requires the top-level unthemed default, explicit light and explicit dark selectors
across all ordered preset-base sources. A value inherited from another scope does not replace any declaration. Registered
Tailwind aliases must exist in `@theme inline` and reference their explicit targets. Multiple base sources may override an
earlier property value intentionally; validation checks the combined declarations of all base sources rather than
requiring one token file.

For properties owned by that metadata level, `global` and `themed` are exclusive: a global property cannot add theme
declarations, and a themed property cannot add `:root`. The latter would outrank the zero-specificity unthemed default and
silently leave that declaration unused.

A shared foundation property used in preset base is an optional override. It may target any subset of the supported
scopes allowed by its inherited classification: `default`, `light` and `dark` for a themed property, or only `root` for a
global property. It then inherits the foundation value elsewhere. Overrides do not require theme symmetry; because
ownership stays with the foundation, the preset must not register them as additions. Additional semantic
foreground/background pairs are registered explicitly and join the shared foundation pairs in browser contrast checks.
Scope, aliases and contrast pairs are declared independently; property stems do not create any of them implicitly.

A common semantic value such as `--background` belongs to the shared token foundation. Promote a value to a documented
preset knob only when it has stable preset-wide meaning, changes the language coherently and has at least two independent
consumers. Component-specific values stay in their visual module. This avoids turning preset base into a collection of
repeated literals; only documented knobs are public customization API.

## Generate a selective bundle

The complete preset is the safe default. For a layout that uses a known subset of components, generate an application
entrypoint without unrelated visual sources:

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
When a preset groups several logical modules in one source, selecting any of them conservatively keeps that complete
source; co-located styles may therefore remain in the bundle.
`--include` accepts additional component keys or Stimulus controller identifiers; use it for UI rendered dynamically
by PHP, Turbo Streams, vendor views or JavaScript when that UI is not represented by the layout's initial component
list. Controller identifiers with `--` may also use their publish form, such as `turbo/progress`. The output must stay
under `resources/css`, which is the same boundary `hotwire:check` audits. Paths elsewhere in the application, including
`vendor`, are rejected.

`foundation.css` and preset base are included exactly once, including when the module closure is empty. Only visual
modules are selective, so tokens, progressive enhancement and runtime utility coverage do not depend on which
components were listed.

The generated file starts with the package marker and should not be edited. Re-run the same command with `--force`
after changing the selection or upgrading Laravel Hotwire. Only an existing `hotwire:styles` bundle is replaceable;
application-owned files and other package-marked CSS are never replaced, even with `--force`. If the complete set of
dynamic components is not known, keep the selected public `presets/<name>.css` import as the fallback instead of
guessing.

Generated bundles also record their canonical component, controller and module selection in a versioned header.
`hotwire:check` inspects marked bundles under `resources/css` and reports visual components/controllers found in the
scanned Blade views when none of those bundles covers them. With multiple layout bundles this is deliberately a global
safety net, not layout inference: coverage in any generated bundle satisfies the check. If any CSS entrypoint under
`resources/css` retains an official complete preset import, or imports an application preset from
`resources/css/presets`, that local preset must pass the complete application-preset contract before it suppresses
missing-module reports. The check also reconstructs each generated bundle from its recorded plan, so stale or truncated
CSS fails even when its metadata remains intact. Generated selective bundles are validated only against their recorded
selection and dependency closure; intentionally omitted components are not completeness errors. `--fix` never changes a
CSS selection or application preset because it cannot know which layout should own a missing component or how an
application's visual language should implement it. Dynamic PHP, Turbo or JavaScript markup still requires `--include`
because static view scanning cannot see it.

## Generate a custom preset

Generate an empty preset scaffold when token overrides are not enough:

```bash
php artisan hotwire:make-preset brand
```

The command creates `resources/css/presets/brand.css`. It imports the live package `foundation.css` facade — including
the runtime utility safelist, so new package mechanics arrive on upgrade instead of freezing — and emits one empty base
rule for every visual slot projected by the registry. An application preset does not need its own preset-base layer;
depending only on shared defaults is valid. Component rules are grouped under the family that declares each slot;
controller-authored anatomy is grouped under its controller.
The scaffold does not copy selector decomposition from Nova or another shipped preset:

```css
@layer components {

    /* Accordion */
    [data-slot="accordion"] {}
    [data-slot="accordion-item"] {}
    [data-slot="accordion-trigger"] {}
    [data-slot="accordion-trigger-icon"] {}
    [data-slot="accordion-content"] {}
}
```

Rules for structural slots do not appear, nor does anything `structural.css` owns: presets are not expected to restate
mechanics such as the Accordion's `::details-content` collapse. The scaffold is an anatomy checklist, not a semantic
state specification. Consult component docs and official presets for relationships, states, at-rules and grouped rules,
then implement the selectors appropriate to the new visual language.

Replace the vendor preset import in `resources/css/app.css` with the line printed by the command:

```css
@import './presets/brand.css';
```

To customize an official preset instead of starting from empty selectors, clone Nova or Bloom into the application:

```bash
php artisan hotwire:make-preset brand --from=nova
php artisan hotwire:make-preset brand --from=bloom
```

The clone is one application-owned file: the live `foundation.css` import is rewritten to its vendor path, while the
selected preset's base and private modules are flattened as a snapshot in canonical order. It never leaves imports to
package-internal preset paths, regardless of how that preset groups or nests its sources.

Both scaffold and clone are application-owned. Their live `foundation.css` import continues to update, but copied preset
base and visual modules do not. Review upgrade notes and merge relevant changes manually; generating under a temporary
name is a safe comparison workflow. `--force` replaces any existing target file, including application customizations;
it does not merge or patch the existing CSS. Use it only when replacement is intentional. The command never edits
`resources/css/app.css`, leaving application styles and import ordering under your control.

A `--from` clone preserves the source preset's rule order, which is worth keeping. Between equal-specificity rules in
the same layer, the later one wins; reordering a clone can therefore change which declaration applies.

A visual preset is CSS over the package's shared semantic tree. It does not require application PHP and does not require
publishing package views. Publishing or overriding a component template is instead an application-owned fork of markup
and behavior: the application must track package changes and preserve the documented slots, targets, lifecycle and
accessibility relationships itself. Use that boundary only when the semantic component contract, rather than its visual
treatment, must change.

Tailwind converts underscores to spaces only inside arbitrary values. Keep the underscore in a utility such as
`bg-[color-mix(in_oklch,var(--background),transparent_20%)]`, but use a real space in raw CSS such as
`background: color-mix(in oklch, var(--background), transparent 20%)`. Raw `in_oklch`, `in_srgb` and equivalent
interpolation methods are invalid CSS and can silently discard the declaration in the browser; `hotwire:check --preset`
reports the source file and declaration when it finds one.

## Validate an application preset

`hotwire:check` automatically validates top-level application presets imported from `resources/css/presets`. Validate an
unimported preset explicitly by name (`brand` or `brand.css`) or by a path under `resources/css`; `--preset` is
repeatable:

```bash
php artisan hotwire:check --preset=brand --no-interaction
php artisan hotwire:check --preset=resources/css/presets/admin.css --no-interaction
```

For a complete application preset, static validation proves that:

- local imports resolve without cycles, conditional imports or escapes from `resources/css`;
- the entrypoint imports package `foundation.css` exactly once before local visual CSS;
- every visual slot declared by the component/controller registry participates in a rule with declarations;
- every `data-slot` reference, including Tailwind `data-[slot=...]` variants, is declared by the registry;
- Tailwind interpolation underscores remain inside arbitrary values instead of leaking into raw CSS declarations.

A definite contract violation is an error and returns exit code 1. If the CSS scanner cannot account for a slot
reference, it emits a `warning:` line and downgrades only that slot's unproven coverage; unrelated missing slots remain
errors. The command does not compare the preset with Nova, require explicit selectors for every axis value or reject
values that intentionally share one appearance. Base rules, grouped selectors, scoped roots, ancestor state and
equivalent selector organization remain valid.

When `--preset` points to a generated `hotwire:styles` bundle, complete-preset validation is skipped explicitly and the
file remains governed by its recorded selective plan. Selecting an unimported complete preset never suppresses missing
bundle coverage for the application's rendered components; only a complete preset imported by application CSS does.

Static validation cannot prove that Tailwind recognizes every utility, that minification succeeds, or that interactive
and accessibility states have the intended result in a browser. Run the application production build and focused tests
as separate CI steps:

```bash
npm run build
php artisan hotwire:check --preset=brand --no-interaction
```

Use the package-manager equivalent (`bun run build`, `pnpm run build` or `yarn build`) when appropriate. The build is the
authority for unresolved `@import`/`@apply` directives; browser or component tests remain the authority for focus,
Presence, reduced motion, forced colors, RTL, contrast and visual behavior. A valid static result is not a visual or
pixel-parity certification.

### A note on IDE warnings

PhpStorm reports hundreds of `'x' applies the same CSS properties as 'y'` warnings on a preset — for example
`has-[[data-variant=inset]]:bg-sidebar` against `bg-background`, or `[&>a:hover]:text-primary` against
`text-muted-foreground`. These are false positives: its Tailwind support does not model variants inside `@apply`, so it
reads a conditional utility as an unconditional declaration and sees a duplicate where there is none. Nova alone carries
552 variant-prefixed utilities. Rewriting each state as its own rule to silence the inspection would multiply the file
for no gain.

## Maintain an application preset

An application-owned scaffold or clone should record the Laravel Hotwire Composer versions it supports. On every
package upgrade that carries preset or markup notes:

1. Read the release's [upgrade notes](upgrade.md) and identify changed slots, DOM relationships, states, native/ARIA
   attributes, public custom properties and structural rules.
2. Generate a fresh scaffold or clone under a temporary name and compare it with the maintained preset. Adopt new visual
   slots and copied preset-base changes deliberately; do not overwrite the maintained file to discover changes.
3. Keep the single live `foundation.css` import before visual CSS. Preserve the clone's base and module order unless a
   cascade change is intentional.
4. Implement the documented contract with selectors appropriate to the preset. Do not copy Nova's lexical axes merely
   to obtain parity: base rules, grouped values and equivalent selector organizations are valid.
5. Compile the complete preset and exercise its real components in default, interactive, invalid and disabled states.
   The [contrast fixture](preset-expressiveness.md#executable-contrast-fixture) renders one semantic tree across
   several personalities and is a reusable starting point for that pass.
6. Verify keyboard and screen-reader behavior, light and dark themes, left-to-right and right-to-left direction, reduced
   motion, forced colors, floating surfaces and nested overlays where applicable.

Run `hotwire:check --preset=brand` after each package upgrade. It catches missing visual slots, a stale foundation facade,
unknown slot names and broken local import graphs. Then run `npm run build` and the focused browser/component checks from
the maintenance list above. Static validation cannot prove state semantics, accessibility behavior, contrast or visual
quality. Selective `hotwire:styles` bundles are different: regenerate them instead of merging changes because their
recorded plan is their source of truth.

## Structural and visual CSS

CSS that makes a component work — as opposed to CSS that gives it a look — lives in `resources/css/structural.css`. The
test is whether breaking the rule leaves the component broken rather than restyled. It owns the carousel's viewport
overflow, flex track, axis and slide sizing through `data-carousel-*` hooks, and the Accordion's `::details-content`
collapse, which needs `allow-discrete` and `calc-size(auto, size)` or the panel snaps shut instead of animating.

Every preset imports that file, so the behavior compiles into your stylesheet and holds on the first paint — no waiting
for the bundle to run — and no preset has to rediscover it. Its Accordion motion fallback lives in the `components`
layer: override the timing if you want (`transition-duration` on `::details-content`); you never restate the mechanism.
Side Panel and Read More similarly keep their animated geometry in the foundation while reading duration and easing from
custom properties declared on their visual roots. This lets a preset vary motion without overriding structural selectors
or using `!important`; their component docs list the supported properties and defaults.

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

The scaffold is an inventory, not a complete design. State relationships, motion, top-layer resets and compound
selectors cannot be inferred from slot names alone. Component docs and shared structural contracts are authoritative;
Nova is one implementation example rather than the required selector vocabulary.

See the [preset expressiveness study](preset-expressiveness.md) for the recommended preset-neutral conformance policy,
contrasting style matrix and reusable fixture. Complete preset checks allow base rules, shared appearances and
equivalent selector organizations without treating Nova's exact decomposition as the semantic contract. Slots that
share an appearance may be combined into one grouped rule after using the scaffold as a checklist.

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
`data-tooltip-motion-value="none"` on an application-styled standalone Tooltip, skips motion. The shared Presence helper
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
