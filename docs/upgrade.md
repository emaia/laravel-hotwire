# Upgrade guide

Manual steps required when upgrading to a release that introduces a breaking change. The package follows semver `X.Y.Z`; **breaking visual changes** are also called out here because they aren't enforceable by code but can surprise apps relying on the prior appearance.

---

## Upgrading to `0.57.0`

`0.57.0` unifies floating surfaces and modal overlays on the state-driven Presence lifecycle, with actual finite CSS
motion, interruptible rapid reopen, `motion="none"`, and reduced motion.

**Modal overlays.** Modal, Alert Dialog, Drawer, Sheet and mobile Sidebar no longer use fixed JavaScript duration timers
or visual Stimulus classes.

### Replace duration props

Alert Dialog, Drawer and Sheet no longer accept `open-duration` or `close-duration`. Modal and mobile Sidebar no longer
support their equivalent raw Stimulus values. Use the uniform motion API to disable motion:

```diff
- <hw:alert-dialog :open-duration="500" :close-duration="100">
+ <hw:alert-dialog motion="default">

- <hw:drawer :open-duration="450" :close-duration="450">
+ <hw:drawer>

- <hw:sheet :open-duration="300" :close-duration="300">
+ <hw:sheet>

+ <hw:modal motion="none">
+ <hw:sidebar motion="none">
```

`default` is implied. Customize speed in CSS on the animated backdrop or panel instead of passing milliseconds to
JavaScript.

### Replace raw overlay state and classes

Modal, Alert Dialog, Drawer and Sheet overlay targets now start with:

```html
data-state="closed"
data-motion="default"
hidden
inert
```

Replace custom `data-open` selectors with `data-state` selectors. Remove the following visual class attributes for each
controller identifier:

```text
data-*-hidden-class
data-*-visible-class
data-*-backdrop-hidden-class
data-*-backdrop-visible-class
data-*-dialog-hidden-class
data-*-dialog-visible-class
data-*-open-duration-value
data-*-close-duration-value
```

Keep `data-*-lock-scroll-class` when body scroll locking is enabled. Presence owns native `hidden` and `inert`; closed
CSS must define only a visual state and must not use `display: none`.

Scope state rules to the overlay's direct animated children. Broad descendant selectors leak parent state into nested
overlays and can make a child Modal or Alert Dialog open without motion:

```diff
- [data-slot="modal-overlay"][data-state="open"] [data-slot="modal-positioner"] {
+ [data-slot="modal-overlay"][data-state="open"] > [data-slot="modal-positioner"] {
      opacity: 1;
  }
```

Mobile Sidebar preserves `data-state="expanded|collapsed"` for desktop state and uses
`data-mobile-state="open|closed"` for Presence. Put `data-motion="default|none"` on the sidebar surface.

### Review lifecycle timing

- `opened` and `closed` events now follow actual finite CSS motion instead of configured milliseconds.
- Alert Dialog replays the confirmed click only after actual exit motion settles.
- Deferred Turbo Streams and mobile Sidebar navigation also wait for actual exit motion.
- `turbo:before-cache` closes synchronously without restoring trigger focus.
- Target replacement during Turbo morph rebuilds Presence, focus trap and top-layer ownership around the new nodes.
- Rapid reopen invalidates stale close callbacks and top-layer teardown.
- With no transition or finite animation, completion is immediate.

### Refresh published controllers

Vendor-loaded controllers update automatically. Refresh package-owned published copies and transitive helpers with:

```bash
php artisan hotwire:check --fix
```

The command will not overwrite marker-free customized controllers. Port those manually, including the new `_presence.js`
dependency reached through `_overlay.js`.

**Floating surfaces.** Dropdown, Popover, Hover Card, Multi Select and Tooltip replace the class-driven transition engine
with Presence. Exit motion is interruptible, enter waits for resolved Floating UI placement, and the lifecycle coordinates
`hidden`, `inert`, and native top-layer cleanup.

### Replace floating `data-open` selectors

The floating content of all five surfaces now uses `data-state="open|closed"`; Tooltip applies it to its generated
floating element. Replace selectors scoped to floating content:

```diff
- [data-slot="popover-content"][data-open="false"] {
+ [data-slot="popover-content"][data-state="closed"] {
      opacity: 0;
  }
```

Closed server-rendered content starts with `hidden inert`. During exit it is already `data-state="closed"` and inert, but
remains without `hidden` until motion finishes. Do not add `display: none`, a `hidden` utility, or equivalent hiding to a
floating closed-state selector; Presence owns the `hidden` attribute.

Trigger state is namespaced so composing a Dropdown trigger with Toggle, Sidebar, or another controller does not overwrite
that component's generic `data-state`:

| Surface | Trigger state |
|---|---|
| Dropdown | `data-dropdown-state="open|closed"` |
| Popover | `data-popover-state="open|closed"` |
| Hover Card | `data-hover-card-state="open|closed"` |
| Multi Select | `data-multi-select-state="open|closed"` |

`aria-expanded` remains synchronized on each trigger. Replace any trigger-only `data-state` selectors with the matching
namespaced attribute; keep `data-state` selectors on floating content.

This state migration is limited to Dropdown, Popover, Hover Card, Multi Select and Tooltip. Modal-style overlays continue
to use their existing overlay state contract.

### Replace component motion options

Boolean `transition` props have been removed. Use the semantic `default|none` motion API instead:

| Surface | Before | After |
|---|---|---|
| Dropdown content | `<hw:dropdown.content :transition="false">` | `<hw:dropdown.content motion="none">` |
| Popover content | `<hw:popover :transition="false">` | `<hw:popover.content motion="none">` |
| Hover Card content | `<hw:hover-card :transition="false">` | `<hw:hover-card.content motion="none">` |
| Multi Select root | No per-instance option | `<hw:multi-select motion="none" />` |
| Tooltip controller | Fixed built-in timing | `data-tooltip-motion-value="default|none"` |

`default` is the default and can be omitted. Tooltip remains a standalone controller API; use its Stimulus value rather
than a Blade content prop.

### Migrate custom floating CSS

The `_transition.js` helper and all `data-transition-*` attributes have been removed. Delete those attributes from custom
markup and move visual states into CSS keyed by `data-state`:

```css
[data-slot="dropdown-menu"] {
    opacity: 1;
    scale: 1;
    translate: 0 0;
    transition: opacity 150ms ease, scale 150ms ease, translate 150ms ease;
}

[data-slot="dropdown-menu"][data-state="closed"] {
    opacity: 0;
    scale: .95;
    translate: 0 -.25rem;
    pointer-events: none;
}
```

The Nova preset transitions only `opacity`, `scale`, and `translate`; it no longer transitions `display`. Custom finite
CSS animations are also supported. Presence suppresses transition and animation while preparing the first placement,
temporarily enforces that suppression for `motion="none"` and `prefers-reduced-motion: reduce`, detects the actual CSS
duration otherwise, and invalidates stale teardown on rapid reopen. CSS transitions reverse naturally from their current
interpolated state.

The Stimulus `hidden` classes were also removed from Dropdown, Popover, Hover Card, and Multi Select. Remove
`data-dropdown-hidden-class`, `data-popover-hidden-class`, `data-hover-card-hidden-class`, and
`data-multi-select-hidden-class` from custom roots, and remove their corresponding class from floating content. Presence
now owns the native `hidden` attribute; a leftover class such as `class="hidden"` will prevent the surface from opening.

### Refresh published controllers

Applications using controllers directly from `vendor` receive the new helpers automatically after Composer updates. If
you published any of the five controllers for customization, refresh package-owned copies and their shared helpers:

```bash
php artisan hotwire:check --fix
```

The command replaces outdated files that still carry the package marker. It refuses to overwrite user-owned files without
that marker; manually port custom changes in those files, remove imports of `_transition.js`, and add `_presence.js` plus
`_top_layer.js` where the updated controller requires them. Delete any stale published `_transition.js` after its imports
are gone.

### Review Dropdown mobile placement

Mobile placement now has priority over collapsed placement as a complete `(side, align)` profile. While `mobile-media`
matches, a missing `mobile-side` falls back to normal `side`, and a missing `mobile-align` falls back to normal `align`.
Neither missing value falls through to `collapsed-side` or `collapsed-align`; collapsed overrides apply only outside the
mobile viewport.

For example, `side="top" align="start" mobile-side="bottom" collapsed-side="right" collapsed-align="end"` resolves to
`bottom-start` on mobile, including inside a collapsed Sidebar.

### Update collapsed Sidebar tooltip selectors

If an icon-only Sidebar uses conditional tooltips, include the mobile state so a persisted desktop collapse does not show
redundant tooltips over visible labels in the mobile drawer:

```html
<!-- Before -->
data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon]"

<!-- After -->
data-tooltip-enabled-when-value="[data-slot=sidebar][data-collapsible=icon][data-mobile-state=closed]"
```

### Placement and top-layer behavior

Floating content remains closed and inert until its first placement resolves. Even `open="true"` is server-rendered as
`data-state="closed" hidden inert` to avoid an unpositioned flash; the controller then opens it without enter motion.
Triggers still reflect the configured logical open state. These Floating UI surfaces require Stimulus and do not provide
a no-JavaScript expanded fallback.
`data-side` and `data-align` always describe the resolved placement after any flip, and stale asynchronous results are
ignored. All five surfaces use native top-layer promotion when supported; Tooltip can therefore render above Modal and
Drawer. Toaster remains separate.

`popover:opened` and `hover-card:opened` now fire after the first placement, as soon as content becomes interactive and
enter motion begins. They do not wait for the CSS transition to finish. Their `closed` events continue to fire when
closing begins.

Top-layer promotion changes the containing block. `strategy="fixed"` uses viewport-relative coordinates;
`strategy="absolute"` uses page/document coordinates while native top layer is active, not the nearest positioned
ancestor. In browsers without native Popover support, `absolute` falls back to normal offset-parent behavior and may be
clipped by ancestors.

When the Nova preset is not loaded, reset the browser's native Popover positioning defaults with
`[data-hotwire-top-layer][popover] { inset: auto; margin: 0; }` and define border and padding for each floating surface.
Standalone Tooltip CSS must also set `overflow: visible` so its arrow is not clipped.

Target replacement and `turbo:before-cache` now clean up Presence, positioning, and top-layer state immediately.

---

## Upgrading to `0.32.0`

`0.32.0` introduces the design system foundation (semantic tokens, OKLCH palette, dark mode via `data-theme`, `Variants` helper, embedded icon subset). All shipped components were repainted to consume the new tokens — visible without code changes in the host app, but the painted result is different.

### What changes automatically (no action required)

- Modal, Confirm-dialog, Dropdown, Form primitives (Input, Label, Select, Textarea, File, Error, Description), Flash-message, Toaster, Spinner and the auxiliary components ship with the new token-aligned palette and spacing.
- All controllers ship from the vendor directory via `import.meta.glob` — no `php artisan hotwire:controllers <name>` step is required to make a `<hw:*>` work in a fresh app.
- `hotwire:install` adds a `@hotwire` Vite alias to your `vite.config.{ts,mjs,js}` so user code can extend a vendor controller via a clean import (`import CarouselController from '@hotwire/carousel_controller.js'`). The alias is added idempotently — re-running `hotwire:install` is a no-op when the key is already present. If your config doesn't match the Laravel-stock shape, the command prints the snippet for manual paste instead of writing the file. See [extending-controllers.md](extending-controllers.md).
- The `Icon` component (`<hw:icon name="..." />`) replaces inline SVGs in the shipped components.

### hotwire:install dependency modes

The `hotwire:install` command exposes three modes for adding npm dependencies to your app's `package.json`. The default favours zero-friction DX (every component works out of the box); the other two are for projects that want to opt into a leaner footprint.

| Command | What it adds | Loader stub shape |
|---|---|---|
| `php artisan hotwire:install` | Core deps (`@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-lazy-loader`) **plus every catalog dep** declared by package controllers (Floating UI, echarts, leaflet, embla-carousel, tiptap stack, dropzone, maska, date-fns, sonner). Everything works without further setup. | Globs every package controller — no exclusions |
| `php artisan hotwire:install --with-deps=carousel,chart,map` | Core deps **plus only the npm deps required by the listed controllers**. Accepts comma-separated values or repeated `--with-deps=X` flags. | Globs zero-dep controllers + only the opted-in com-dep controllers; everything else is excluded so `vite build` never resolves their missing imports |
| `php artisan hotwire:install --core-only` | Core deps **only**. No catalog deps. | Globs zero-dep controllers only; every com-dep controller excluded |

End-user runtime cost is identical across the three modes: Vite's dynamic-import code-splitting ships only the chunks for controllers that actually mount in the DOM. The trade-off is purely on the dev side — `node_modules` size, install time and `vite build` time scale with what's installed.

`--core-only` and `--with-deps` are mutually exclusive — the command fails if both are passed.

`--with-deps=<name>` validates each controller name against the catalog and fails fast on a typo.

### Package manager install runs by default

`hotwire:install` runs your package manager (bun / pnpm / yarn / npm, auto-detected from the lockfile) right after writing `package.json`. In interactive mode it prompts with `Run bun install now?` (default yes); in `--no-interaction` mode it runs without prompting.

| Flag | Effect |
|---|---|
| (no flag) | Default behaviour — runs the package manager (prompted in interactive mode, automatic in `--no-interaction`) |
| `--skip-install` | Skip the package manager step entirely; leaves dep fetching to the caller (useful in CI pipelines that wrap their own `npm ci` step) |
| `--fix` | Auto-apply `hotwire:check --fix` during the post-install verification — pairs with `--no-interaction` for end-to-end automation |

Fully-automated install for CI:

```bash
php artisan hotwire:install --with-deps=modal,dropdown --fix --no-interaction
```

This: scaffolds, adds deps + `@hotwire` alias, runs `bun install`, runs `hotwire:check --fix` (regenerates the loader stub and adds any drifted npm deps), runs `bun install` again if needed, and never prompts. The previous `--install` flag has been removed — install is now the default. The previous `hotwire:check --install` is similarly inverted to `--skip-install`.

### Loader stub is now generated

Starting `0.32.0`, `resources/js/controllers/index.js` is **generated** by `hotwire:install` (and re-generated by `hotwire:check --fix`) rather than copied bit-for-bit from a stub. The file starts with this marker:

```js
// AUTO-GENERATED by hotwire:install — DO NOT EDIT MANUALLY.
// Re-run `php artisan hotwire:install` (or `hotwire:check --fix`) to regenerate.
```

`hotwire:install` recognises the marker and silently regenerates the file (no `--force` prompt) when you re-run install with different flags. A hand-written `index.js` without the marker is treated as user-owned and never touched without explicit `--force`.

### Detecting drift between install config and view usage

When you install with `--with-deps=carousel` and later add `<hw:chart>` to a view, the build will succeed (chart is excluded from the stub) but Stimulus won't register the chart controller — the component renders inert. To catch this:

- `hotwire:check` reports `chart  excluded from loader stub  (used in views; re-run install with --with-deps including chart, or hotwire:check --fix)`.
- `hotwire:check --fix` regenerates the stub to include `chart`, and adds the missing npm dep to `package.json`. Run your package manager install command (`bun install`, etc.) afterwards.
- `hotwire:install` automatically runs `hotwire:check` after a `--with-deps` or `--core-only` install, so any drift in pre-existing views surfaces immediately.

### What you must do manually

#### 1. Add the `@source` directive for package CSS

Package styles now live in CSS preset files. Tailwind v4 needs to scan those package CSS files so utilities used in presets and runtime safelists are generated.

Open your application's `resources/css/app.css` and add the package CSS source:

```diff
+ @source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';
```

Apps installed via `hotwire:install` from `0.33.0` onwards get this automatically — the change applies only to apps installed on an earlier version.

#### 2. Re-publish the CSS stub if you customised it

If you ran `hotwire:install` before `0.32.0` and have *not* customised `resources/css/app.css`, the simplest path is:

```bash
php artisan hotwire:install --only=css --force
```

If you *have* customised the file, copy the new pieces manually:

- `@import "tailwindcss";`
- `@custom-variant turbo-*` / `form-busy` / `frame-busy` / `in-turbo-frame` / `modal` / `dark` directives.
- `@theme inline { … }` block mapping `--color-*` tokens to the underlying CSS variables (used by Tailwind utilities like `bg-primary`, `text-muted-foreground`).
- `@layer base { * { border-color: var(--border); outline-color: var(--ring); } body { background-color: var(--background); color: var(--foreground); } }`.
- `:root { … }` light palette and `[data-theme="dark"] { … }` dark overrides.

Full reference: [`docs/theming.md`](theming.md).

#### 3. Wire up the dark mode trigger (optional)

`[data-theme="dark"]` on `<html>` activates the dark palette. There is no packaged toggle yet. If you want dark mode now, set the attribute yourself (server-side, inline script, or via your own toggle).

```html
<html data-theme="dark">
```

### Visual diff — what apps relying on the old paint will see

If your app *relied* on the prior appearance of shipped components (e.g. screenshots, design specs), expect these substitutions in the rendered HTML:

| Component area | Before (`0.31.x`) | After (`0.32.0`) |
|---|---|---|
| Body background | not styled by the package | `var(--background)` via `@layer base` |
| Modal panel | `bg-white` + `bg-gray-50` borders | `bg-background ring-1 ring-foreground/10` |
| Modal backdrop | `bg-slate-600/80` | `bg-black/10 backdrop-blur-xs` |
| Confirm-dialog confirm | `bg-red-600 hover:bg-red-700 text-white` | `bg-destructive text-destructive-foreground hover:bg-destructive/90` |
| Confirm-dialog cancel | `bg-white border-gray-300 text-gray-700` | `bg-background border-input text-secondary-foreground hover:bg-accent` |
| Input / Textarea / Select | `border-gray-300 bg-white text-gray-900` | `border-input bg-background text-foreground focus-visible:border-ring focus-visible:ring-ring/50` |
| Input error state | `border-red-500` | `aria-invalid:border-destructive aria-invalid:ring-destructive/20` |
| Label | `text-gray-700` | `text-foreground` |
| Description | `text-gray-600` | `text-muted-foreground` |
| Error message | `text-red-600` | `text-destructive` |
| Spinner / Scroll-progress | hardcoded hues | semantic tokens (`text-foreground/50`, `bg-primary`) |
| Inline SVG close buttons | one-off `<svg>` per component | `<hw:icon name="x" />` |

Custom classes you pass through `class="..."` on the component are unaffected — only the package's own defaults moved.

### Verifying the upgrade

1. Run `php artisan hotwire:check` — confirms catalog npm deps are present, reports any controller files diverging from the vendor's `// @hotwire-package` marker.
2. Run `bun run build` (or `vite build`) and visually inspect the resulting `dist/assets/*.css`. Confirm that semantic tokens (`--background`, `--foreground`, `--primary`, …) are defined.
3. Open the components in a browser:
   - Light mode: `<html>` with no `data-theme` attribute.
   - Dark mode: set `data-theme="dark"` on `<html>` and confirm the palette inverts.

### Rollback

If the visual change is disruptive and you need to ship before adopting:

- Pin to `^0.31.0` in `composer.json` until you can schedule the visual migration.
- The class substitutions are not one-way — you can keep overriding the package classes per-component via the `class="..."` attribute on each `<hw:*>` instance if a holistic re-theme is not yet feasible.

---

## Upgrading to `0.33.0`

`0.33.0` moves shipped component styling from inline Tailwind classes in Blade views to CSS presets based on semantic attributes.

### Update `resources/css/app.css`

Re-run the CSS installer if your app has not customised the file:

```bash
php artisan hotwire:install --only=css --force
```

If you customised it, keep your app CSS and add the preset source/import shape manually:

```css
@import "tailwindcss";

@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
```

Available preset: `nova`.

### Update component CSS overrides

Overrides that targeted package Tailwind classes should move to semantic selectors:

```css
/* Before: coupled to internal classes */
.my-page .bg-primary { ... }

/* After: coupled to component intent */
.my-page [data-slot="button"][data-variant="default"] { ... }
```

Props and public HTML attributes are preserved. Custom `class="..."` values still pass through to the rendered element.
