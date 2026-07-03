# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

## 0.36.1 - 2026-07-03

### Laravel Idea component name fixes

Patch release aligning package component metadata with the public names Laravel Idea derives from PHP component classes.

#### Fixes

- Renamed the public Empty component API to `<hw:empty-state>` and `<hw:empty-state.*>` so PhpStorm/Laravel Idea completion matches the backing `EmptyState` classes.
- Backed `<hw:field.set>` with `Components\Field\Set` so the existing short field set tag remains concise while matching Laravel Idea metadata.
- Updated `ide.json`, docs, registry entries, semantic slots and Nova preset selectors for the renamed Empty State component.
- Preserved the clear input preset visibility fix from the release branch.

#### Docs

- See `docs/components/empty-state.md` and `docs/components/field.md` for updated examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.36.0...0.36.1

## 0.36.0 - 2026-07-03

### Short Hotwire tags and Laravel Idea metadata

This release adds the short `<hw:*>` Blade component syntax and Laravel Idea metadata for component and Stimulus helper completion.

#### Short Blade component tags

Laravel Hotwire now defaults to the `hw` prefix and supports the preferred short tag syntax:

```blade
<hw:button>Save</hw:button>
<hw:field.set>
    <hw:field.legend>Preferences</hw:field.legend>
</hw:field.set>


```
The configured `hotwire.prefix` remains customizable for apps that want another prefix.

#### Laravel Idea metadata

The package now ships `ide.json` metadata for Laravel Idea/PhpStorm so `<hw:*>` components can be completed and navigated.

Apps can also generate project-specific Stimulus helper metadata with:

```bash
php artisan hotwire:ide-json


```
`hotwire:install` runs this automatically for JS installs.

#### Documentation

The README, component docs and recipes now use the `<hw:*>` syntax throughout.

See the docs for examples:

- `docs/installation.md`
- `docs/components/button.md`
- `docs/components/field.md`

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.35.1...0.36.0

## 0.35.1 - 2026-07-03

### Overlay and clear input fixes

Patch release with interaction fixes for components introduced or affected by the semantic-slot preset migration.

### Fixes

- Fixed `Input` clear buttons so the `clear-input` controller can reveal them again after the initial hidden state.
- Fixed Modal and AlertDialog scroll locking to compensate for the removed scrollbar gutter and prevent page layout shift.
- Kept overlay scroll locking reference-counted so nested or concurrent overlays do not restore body scroll too early.

### Maintenance

- Added regression coverage for clear input visibility and overlay scrollbar compensation.
- Added persistent agent collaboration rules for confirming messages and avoiding local absolute paths in user-facing responses.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.35.0...0.35.1

## 0.35.0 - 2026-07-02

### Kbd, Empty, ButtonGroup, Skeleton and Card components

Adds five new semantic-slot Blade components to the Nova preset component set.

### Components

- Added `Kbd` and `Kbd.Group` for keyboard shortcut hints.
- Added `Empty` with header, media, title, description and content subcomponents.
- Added `ButtonGroup` with text and separator subcomponents.
- Added `Skeleton` for loading placeholders.
- Added `Card` with header, title, description, action, content and footer subcomponents, plus per-instance spacing customization via `--card-spacing`.

### Developer Experience

- Registered the new components in the catalog and service provider aliases.
- Added docs and README entries for the new components.
- Added Nova preset styling hooks and tests for the new `data-slot` contracts.
- Improved PHP test runtime by running the Pest suite in parallel.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.34.0...0.35.0

## 0.34.0 - 2026-07-02

### Field namespace and display primitives

This release expands the semantic-slot component set with field layout primitives, table/badge display components, and Alert, Item and Separator.

#### Field components

- Consolidates field-related primitives under the `field.*` namespace.
- Adds field layout slots for grouped fields, legends, descriptions, errors, separators, titles and content.
- Adds responsive field orientation using viewport breakpoints while preserving intrinsic-width surfaces like `modal size="auto"`.

#### Badge and Table

- Adds `<x-hwc::badge>` with semantic variants and configurable root element via `as`.
- Adds table primitives for header, body, footer, rows, cells, headings and captions.
- Adds Nova preset styling and docs for badge/table slots.

#### Alert, Item and Separator

- Adds `<x-hwc::alert>` with title, description and action slots, including destructive and custom color examples.
- Adds `<x-hwc::item>` with group, media, content, title, description, actions, header, footer and separator slots.
- Adds `<x-hwc::separator>` with horizontal/vertical orientation hooks.

#### Styling and docs

- Extends the Nova preset with new `data-slot` contracts for all added components.
- Updates README and component docs for the expanded component catalog.
- Adds render tests and preset contract coverage for the new primitives.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.33.0...0.34.0

## 0.33.0 - 2026-07-02

### Semantic slot preset infrastructure

This release moves shipped component styling out of Blade/PHP defaults and into the Nova CSS preset, adds the Button component, and establishes `data-slot`/`data-variant`/`data-size` as the package styling contract.

#### Button and preset foundation

- Adds `<x-hwc::button>` with semantic `variant`, `size`, `type`, `as`, and `stimulus` support.
- Adds `resources/css/tokens.css`, `resources/css/custom-variants.css`, and `resources/css/presets/nova.css` as the default preset stack.
- Replaces the installed CSS stub with a thin Tailwind v4 entrypoint that imports the package preset and scans package CSS.
- Keeps `Support\Variants` available for app code while shipped components now expose styling hooks through semantic attributes.

#### Semantic slots across shipped components

- Migrates form primitives, overlays, dropdown, rich text, file upload, carousel/chart/map wrappers, feedback components, icons, spinner, timeago, and optimistic UI to `data-slot`-based markup.
- Adds Modal and Alert-dialog sub-components for header, title, description, content, and footer composition.
- Renames Confirm-dialog to Alert-dialog to align with the shadcn/Radix naming model.
- Adds explicit dropdown trigger icon styling through `data-slot="dropdown-trigger-icon"` instead of requiring fragile group selectors.

#### Styling and interaction fixes

- Refines checkable input rendering so checkbox/radio states, invalid states, and indeterminate checkbox state render correctly in the Nova preset.
- Fixes `checked="false"` handling for checkable inputs and avoids empty `class=""` output on labels.
- Styles RichText wrapper, toolbar, toolbar buttons, editor content, placeholder, lists, blockquotes, code, and pre blocks in the Nova preset.
- Stabilizes timer-heavy JS tests and switches the JS suite to `bun test --isolate --parallel`.

#### Docs and references

- Adds Button and Alert-dialog docs and updates dropdown, rich text, file upload, theming, presets, install, and recipes for the semantic-slot direction.
- Updates component/controller registry entries so commands and docs resolve the new Button and Alert-dialog resources.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.32.0...0.33.0

## 0.32.0 - 2026-06-30

### Design system foundation + reworked install command with autoload

The release that brings the package to visual and structural parity with shadcn/ui — semantic tokens, dark mode, an `Icon` component with embedded Lucide SVGs, and a one-command install that auto-loads every controller from the vendor directory.

#### Design system foundation

OKLCH token palette aligned with shadcn v4 `globals.css`, dark mode via `[data-theme="dark"]`, and a `Support\Variants` helper (CVA-equivalent in PHP) used by repainted Modal, Confirm-dialog, Dropdown, Form primitives (Input, Label, Select, Textarea, File, Error, Description), Flash-message and Toaster. New `<x-hwc::icon>` ships ~21 embedded Lucide SVGs and replaces inline `<svg>` in the shipped components. `_overlay.js` shared lifecycle helper extracted from Modal + Confirm-dialog so future Sheet/Drawer/Sidebar nascem prontos.

See [`docs/theming.md`](docs/theming.md) for the token reference and [`docs/upgrade.md`](docs/upgrade.md) for the visual-change migration guide.

#### Zero-publish controller auto-load

Controllers now load directly from `vendor/emaia/laravel-hotwire/resources/js/controllers/` via `import.meta.glob` — no `php artisan hotwire:controllers <name>` step is required to make a `<x-hwc::*>` work. `@emaia/stimulus-dynamic-loader` bumped to `^1.0.3` so user controllers shadow vendor ones silently (`warnOnDuplicate: false` honored).

The loader stub `resources/js/controllers/index.js` is auto-generated by `hotwire:install` with a marker comment and explicit exclusion list tailored to the install flags — so `vite build` never resolves missing imports from controllers the user didn't opt into.

#### `@hotwire` Vite alias

`hotwire:install` injects a `@hotwire` alias pointing at `vendor/emaia/laravel-hotwire/resources/js/controllers/` so user code extends a vendor controller with a clean import:

```js
import CarouselController from "@hotwire/carousel_controller.js";

export default class extends CarouselController {
    // ...
}







```
Brace-aware injection respects an existing `resolve:` block. See [`docs/extending-controllers.md`](docs/extending-controllers.md).

#### Install / check command rework

Single canonical command for the greenfield case:

```bash
php artisan hotwire:install







```
Adds every catalog dep, wires the Vite alias, generates the loader stub, runs the package manager (auto-detected from the lockfile), and verifies view usage matches the install config. Three explicit dependency modes:

| Flag | Behaviour |
|---|---|
| (no flag) | Core deps + every catalog dep (echarts, leaflet, embla, tiptap, dropzone, maska, tippy, date-fns, sonner) |
| `--with-deps=carousel,chart` | Core + only the listed controllers' npm deps; loader stub excludes everything else |
| `--core-only` | Core deps only; every com-dep controller excluded from the stub |

`--skip-install` opts out of running the package manager. `--fix` forwards to the post-install `hotwire:check` so `hotwire:install --with-deps=<list> --fix --no-interaction` is end-to-end automation with zero prompts.

`hotwire:check` detects drift between the generated stub and the controllers actually referenced in views; `--fix` regenerates the stub + adds missing npm deps in one call. Interactive install prompts to apply `--fix` directly instead of forcing a re-run.

See [`docs/installation.md`](docs/installation.md) for the full flag reference, CI recipe and troubleshooting.

#### Breaking visual change

All shipped components consume semantic tokens — `bg-background`, `text-foreground`, `border-input`, `aria-invalid:ring-destructive/20`, etc. — instead of the previous raw colours. Apps relying visually on the prior appearance see a different paint; the API surface (props, slots) is unchanged. Migration steps and class substitution table in [`docs/upgrade.md`](docs/upgrade.md).

Apps installed via `hotwire:install` from this release onwards get the new stub automatically. Earlier-installed apps need to manually add `@source '../../vendor/emaia/laravel-hotwire/src/Components/**/*.php';` to their `resources/css/app.css` so the Tailwind v4 scanner picks up classes declared inside `Variants::make()` calls — without that line, those classes are silently omitted from the final CSS.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.31.1...0.32.0

## 0.31.1 - 2026-06-26

### Confirm-dialog and dropdown event-flow fixes

Fixes abrupt close when `<x-hwc::confirm-dialog>` is nested inside an `<x-hwc::dropdown>`, plus a dropdown listener loss after Turbo morph.

- `confirm-dialog`: Cancel/Confirm/Escape now stay contained inside the modal so a wrapping `dropdown` no longer closes in parallel and clips the close transition. `clickOutside` stops propagation unconditionally (cancel/confirm actions toggle `isOpen` during bubble, so the previous early-return skipped the stop); Escape moves to capture phase with `stopImmediatePropagation` (#59)
- `dropdown`: `onMenuClick` binds via `menuTargetConnected`/`Disconnected` instead of `connect()`. The manual `addEventListener` became orphan when a Turbo morph swapped the menu node while preserving the controller root, leaving a surviving-row dropdown unable to close after a confirmed delete (#59)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.31.0...0.31.1

## 0.31.0 - 2026-06-26

### File-upload Blade component (Dropzone wrapper)

New `<x-hwc::file-upload>` Blade component wrapping `@deltablot/dropzone` 7.x for drag-drop uploads with the endpoint, validation, storage and cleanup app-side. Also: JS tests now run in CI.

- `<x-hwc::file-upload>` + `file-upload` controller wrap Dropzone for drag-drop, multi-file queue, client-side preview/progress, aria-live announcer and keyboard operation. `:options` (raw Dropzone config) and `:messages` (short i18n keys → `dict*`) are validated for unknown keys at construction (#56)
- `<x-slot:preview_template>` lets you author Dropzone preview markup in Blade — rendered as a `<template>` target with `data-dz-*` hooks bound per file (#56)
- `controller=` swap prop for Stimulus subclass extensibility (mirrors `chart`/`map`); `data-*-value` and `data-*-target` follow the swapped identifier. Subclass hooks: `defaultOptions()` and `afterInit()` (#56)
- `:value` + `old()` redirect-back preservation; native `:turbo-stream="true"` opt-in (Accept header + `Turbo.renderStreamMessage` on success/error); error response normalisation so `{ message }` and 422 `{ errors: { field } }` render readable text instead of `[object Object]` (#56)
- Docs: full component page covering Setup, Edit forms, Turbo Streams, Keyboard, Validation, Messages/i18n, Options, Preview template, Controller swap. Plus [`file-upload-patterns.md`](docs/recipes/file-upload-patterns.md) (5 patterns: Spatie Media Library, async thumbnail via broadcast, stream-rendered gallery with EXIF, single-file edit form with stream-replaced card, rich media library list with rename and reorder) and [`draft-as-state-gallery.md`](docs/recipes/draft-as-state-gallery.md) for multi-step creation flows (#56)
- JS tests now run in CI (`bun run test` + `bun run test:browser`); fixes a modal Playwright bundler regression (#55)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.30.0...0.31.0

## 0.30.0 - 2026-06-15

### Rich text editor (Tiptap wrapper)

New `<x-hwc::rich-text>` Blade component pairing a Tiptap editor with a default toolbar — extensible via subclass for tables, task lists or any Tiptap extension. Also: FocusTrap and clear-input fixes surfaced during modal work.

- `<x-hwc::rich-text>` and the `rich-text` controller wrap Tiptap with StarterKit + Link + Underline (plus Placeholder when set). HTML or JSON output via the `output` prop; `value` accepts initial content and is restored from `old()` on validation failure; `:image-upload="true"` intercepts paste/drop and dispatches `rich-text:image-upload` for the app to handle (#53)
- Default `rich-text-toolbar` controller covers bold/italic/underline/headings/lists/blockquote/code-block/link/undo/redo — each tracked button reflects `editor.isActive(state)` via `is-active` + `aria-pressed` (#53)
- Toolbar is identifier-agnostic and subclass-friendly: editor events emit under a fixed `rich-text:` prefix, the toolbar finds its editor via the `editor` CSS-selector value + a `data-controller` walk, and subclasses spread `static targets`/`static activeStates` to add Tiptap extensions like Table. The `controller="..."` swap on the component works without outlet rewiring. See the [Table recipe](docs/controllers/rich-text-toolbar.md#extending-the-toolbar-table-recipe) (#53)
- FocusTrap drops the `priming` flag — `activate()` now focuses the first focusable element immediately if nothing inside is focused. Eliminates the "first Tab does nothing" UX inside modals and the Tab+Enter regression where Enter could submit the surrounding form (#54)
- `clear-input` controller swaps the CSS `:focus +` rule for explicit `focusin`/`focusout` listeners on the wrapper. Closes the gap where the input lost `:focus` before the clear button received focus and the button went `display:none` (#54)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.29.0...0.30.0

## 0.29.0 - 2026-06-12

### Turbo morph recovery for wrappers + chart/map reload action

The chart, map and carousel controllers now survive Turbo morph cleanly, and chart/map gain a `reload` action you can wire to any event your app dispatches.

- New shared helper attaches a `turbo:morph-element` listener on each wrapper's host element. When Turbo morph preserves the host but replaces its embedded DOM (common under `<meta name="turbo-refresh-method" content="morph">`), the controller re-initialises automatically. chart, map and carousel each define their own staleness check (canvas missing / `.leaflet-pane` missing / Embla slides no longer in the DOM) (#51)
- `chart#reload` and `map#reload` re-fetch the configured `url` and apply the response on the running instance — chart merges via `setOption` with animation, map adds a new GeoJSON layer. Wire to any custom event your app dispatches: `data-action="kanban:updated@window->chart#reload"`. The package owns the API; the app names the semantics (#51)
- `<x-hwc::frame-or-page>` no longer wraps the slot in a `<turbo-frame>` on direct navigation when a `layout` is set. The previous behaviour produced a duplicate `id` in the DOM whenever the layout already hosted a frame with the same id (e.g. `<x-hwc::modal frame="modal">`), causing Turbo to aim subsequent navigations at the wrong frame. The frame-request branch and the no-layout branch are unchanged (#52)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.28.0...0.29.0

## 0.28.0 - 2026-06-12

### Leaflet map component

New `<x-hwc::map>` Blade component and `map` Stimulus controller — a Leaflet wrapper that covers the 90% case of "show a pin on a map" with very little code, in the same wrapper style as `chart` (ECharts) and `carousel` (Embla).

```blade
{{-- Pin at an address --}}
<x-hwc::map
    :center="[-23.5505, -46.6333]"
    :zoom="12"
    :markers="[[-23.5505, -46.6333, 'São Paulo']]"
    height="400px"
/>

{{-- Multiple markers — no center needed, auto-fits to show all --}}
<x-hwc::map :markers="[
    [-23.5505, -46.6333, 'São Paulo'],
    [-22.9068, -43.1729, 'Rio de Janeiro'],
    [-30.0346, -51.2177, 'Porto Alegre'],
]" />

{{-- GeoJSON from an endpoint --}}
<x-hwc::map url="/api/locations" height="400px" />












```
- Default OpenStreetMap tiles with the required attribution automatically set
- Inline markers with optional popups, or a `url` returning a GeoJSON `FeatureCollection`
- **Auto-fit:** when `:markers` or `:url` is given without `:center`, the controller frames everything provided (20px padding, `maxZoom: 15`). `:fit="true"`/`:fit="false"` overrides the heuristic
- Subclass hooks for custom tile providers, plugins, and click handlers: `defaultView`, `tileLayerUrl`, `tileLayerOptions`, `afterInit`
- Includes two Leaflet bundler papercuts that are easy to miss: `delete L.Icon.Default.prototype._getIconUrl` so dev URLs don't get a duplicated prefix, and Vite-resolved marker icon imports so pins render as the standard blue marker out of the box
- Three doc pages: `docs/controllers/map.md`, `docs/components/map.md`, and a recipe at `docs/recipes/maps.md` with three patterns (inline markers, GeoJSON endpoint, custom tiles + click handlers + cluster note)

#### Other changes

- `docs/controllers/hotkey.md` gains a callout warning against putting `data-controller="hotkey"` on a common ancestor (`<body>` etc.) — the click/focus actions operate on `this.element` and silently no-op when mounted upstream from the intended target
- `CLAUDE.md` registers a PR body template (Summary + Test plan with automated checks and manual smoke checklist) to standardise verification across future PRs

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.27.0...0.28.0

## 0.27.0 - 2026-06-12

### Chart live polling

The `chart` controller now supports a `poll` value (milliseconds) — when set with `url`, the chart re-fetches the endpoint on every cycle and applies the response via partial `setOption` merge. No flicker, user interactions like zoom and brush survive between updates.

```blade
<x-hwc::chart url="/api/charts/sales" :poll="30_000" height="320px" />













```
- Recursive `setTimeout` design — the next cycle is only scheduled after the current fetch settles, so a slow endpoint can't queue overlapping requests (#49)
- `inflight` guard in `loadFromUrl()` prevents request overlap on any code path (connect, polling, manual call)
- Endpoint failures (404, 500, network) are logged to `console.error`; the loop keeps running. For unrecoverable errors, remove `:poll` from the component or subclass to add custom error handling

See `docs/components/chart.md` and `docs/controllers/chart.md` for the new section.

#### Full controller test coverage

Adds Bun tests for the last six controllers without coverage (`scroll_progress`, `turbo--progress`, `turbo--view-transition`, `turbo--polling`, `lazy_image`, `confirm_dialog` — 42 new cases). Every Stimulus controller the package ships now carries at least one main-behaviour test (#48).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.26.0...0.27.0

## 0.26.0 - 2026-06-12

<!-- Release notes generated using configuration in .github/release.yml at 0.26.0 -->
### What's Changed

#### Other Changes

* Add tests for optimistic controllers and _dispatch helper by @emaia in https://github.com/emaia/laravel-hotwire/pull/44
* Add make-controller catalog guard and _form_errors helper tests by @emaia in https://github.com/emaia/laravel-hotwire/pull/45
* Mark package-shipped controllers and refuse overwrites of user files by @emaia in https://github.com/emaia/laravel-hotwire/pull/46
* Expose user-owned files as a distinct status in publish/check output by @emaia in https://github.com/emaia/laravel-hotwire/pull/47

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.25.0...0.26.0

## 0.25.0 - 2026-06-11

### Controller bugfixes, tooltip placement, and full test coverage

Every shipped controller now has a Bun test of its main behaviour; three real bugs surfaced and were fixed along the way, and the suite gained file-level mock isolation.

- New Bun tests for 13 controllers: auto_select, gtm, modal_auto_close, remote_form, dev--log (#41), oembed, tooltip (#42), toaster (#43) — combined with the four added in #40, the catalog is now fully covered
- `auto_select`: focus listener handler is now stored, so `disconnect()` actually removes it (#41)
- `gtm`: lazy mode registers three document-level listeners; new `disconnect()` removes them (#41)
- `oembed`: when no `<figure>` wraps the `<oembed>`, the controller no longer replaces — and destroys — its own data-controller root (#42)
- `tooltip`: connect is idempotent (destroys the previous tippy instance); new `placement` value (default `"top"`) wired to tippy (#42)
- Suite runs with `bun test --isolate` (Bun 1.3.10+); each file gets its own JSGlobalObject so `mock.module` no longer leaks across files (#43). Drop the flag once Bun 1.4 makes isolation the default
- `modal_auto_close`: ancestor lookup anchored at `parentElement` to work around a happy-dom `[attr~="value"]` substring-match bug (#41)

See `docs/controllers/tooltip.md` for the new placement entry.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.24.0...0.25.0

## 0.24.0 - 2026-06-11

### TypeScript to JavaScript migration

Package-shipped Stimulus controllers are now standardised on plain JavaScript (`.js`). Users can still generate `.ts` controllers via `hotwire:make-controller --ts` — the convention applies only to what the package distributes.

- Six controllers migrated from `.ts` to `.js`: animated_number, char_counter, checkbox_select_all, copy_to_clipboard, hotkey, timeago
- 45 new Bun tests across four previously uncovered controllers
- Registry and `hotwire:check` PHP tests updated to reference `.js` extensions
- `CLAUDE.md` documents the `.js`-only convention for shipped controllers

See individual controller docs under `docs/controllers/` for usage.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.23.0...0.24.0

## 0.23.0 - 2026-06-10

### Chart controller and `<x-hwc::chart>` component (Apache ECharts)

Apache ECharts ^6.1.0 wrapper with server-rendered or URL-fetched options, ResizeObserver-driven resize, and subclass-friendly extensibility hooks that match the carousel pattern.

```blade
{{-- Inline option — the 80 % case --}}
<x-hwc::chart :option="[
    'title'  => ['text' => 'Sales by month'],
    'xAxis'  => ['type' => 'category', 'data' => ['Jan', 'Feb', 'Mar']],
    'yAxis'  => ['type' => 'value'],
    'series' => [['type' => 'bar', 'data' => [120, 200, 150]]],
]" height="320px" />

{{-- URL-fetched for heavy datasets --}}
<x-hwc::chart url="/api/charts/sales" height="320px" />

{{-- Subclass swap for custom defaults, extra chart types, or drill-down --}}
<x-hwc::chart controller="sales-chart" :option="$option" />

















```
#### Controller features

- **`setOption` action** — partial or full option updates via `event.detail`, with an optional `{ option, replace }` envelope that maps to ECharts' `notMerge` semantics
- **Hooks for subclasses** — `defaultOption()` (applied as the first `setOption` call) and `afterInit()` (post-init hook for event listeners), matching the carousel extensibility pattern
- **Base bundle** — bar/line/pie charts, grid/tooltip/legend/title/dataset components, and canvas renderer (~120 KB tree-shaken); subclasses register extras (scatter, gauge, map, SVG renderer, etc.) via `echarts.use([...])`
- **ResizeObserver** — `chart.resize()` on container dimension changes
- **Dev-mode warning** — in `local` environment, logs a `Log::warning` when the inline option JSON exceeds 500 KB, pointing to the `url` prop

#### Component

`<x-hwc::chart>` validates that at least one of `option` or `url` is provided, embeds the JSON-encoded option as a `data-*` attribute, applies inline sizing, and passes through extra HTML attributes and user `data-controller` identifiers. The `controller` prop swaps the Stimulus identifier so subclasses mount with zero additional wiring.

#### Recipe

Three patterns in `docs/recipes/charts.md` — inline, URL-fetched, and subclass extension — plus an advanced drill-down pattern with smooth universal transitions and a history stack.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.2...0.23.0

## 0.22.2 - 2026-06-10

### hotwire:check output, reorganized

The scan output now groups by category — component controllers, components without controllers, standalones, and shared helpers — each alphabetical. A new `Needs attention:` block collects every outdated, missing, and not-published item and prints right above the summary, so the actionable items sit next to the prompt instead of being buried mid-list.

Same exit codes, same behavior — only the order of emission changes.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.1...0.22.2

## 0.22.1 - 2026-06-10

### Focus trap helper

Internal refactor: the focus trap code that lived inline in `modal_controller` and `confirm_dialog_controller` is now a shared `FocusTrap` helper at `resources/js/controllers/_focus_trap.js`. Both controllers shed ~30 LOC each and delegate to the helper.

#### What changes for users

Nothing. Modal and confirm-dialog behave identically — same focusable selector, same priming-on-open semantics, same trigger-element focus restoration on close. When you publish either controller with `php artisan hotwire:controllers`, the publish pipeline now ships `_focus_trap.js` alongside it as a shared dependency (the same way `_transition.js` and `_form_errors.js` already work).

#### Why

A future bug fix in focus trap logic — Tab cycling, priming, the focusable selector — now applies to both consumers in one place, instead of having to be repeated. `hotwire:check` also flags the helper as not published / outdated when applicable, consistent with the rest of the shared-dep checks.

### CI

- Bumped `actions/cache` from 4 to 5 (#36)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.22.0...0.22.1

## 0.22.0 - 2026-06-10

### Conditional fields controller and `<x-hwc::conditional-field>` component

New `conditional-fields` Stimulus controller shows or hides dependent blocks based on the value of other form fields, with zero round-trips. The controller auto-detects triggers from `data-when-{name}` attributes on each dependent, and works on any container with named inputs — `<form>` is the common host, but filter bars, dashboards, and in-page configuration panels work too.

```html

<form data-controller="conditional-fields">
    <select name="ship_different">...</select>

    <fieldset data-conditional-fields-target="dependent"
              data-when-ship-different=":checked"
              hidden disabled>
        ...
    </fieldset>
</form>




















```
#### Rule grammar

- Values are pipe-separated within a single `data-when-*` attribute (OR). `data-when-reason="bug|feature"` matches when `reason` is `bug` or `feature`. Pipe (rather than whitespace) is the separator so trigger values containing spaces — full names like `"Kris Jhonson"`, country labels, statuses like `"In Progress"` — match literally.
- Multiple `data-when-*` attributes on the same dependent AND-match across fields.
- Tokens `:checked` / `:unchecked` for boolean checkboxes.
- Checkbox groups (`name[]`) supported: the dependent matches when any of the wanted values is checked.

#### `<x-hwc::conditional-field>` Blade component

Recommended path — encodes the rule once on the server, renders `hidden disabled` initially when the current state does not match, and emits the matching `data-when-*` attributes for the controller. Eliminates the client/server drift that would otherwise flash the wrong fields on first paint.

```blade
<form data-controller="conditional-fields" action="/feedback" method="POST">
    @csrf

    <x-hwc::select
        name="reason"
        placeholder="Pick one…"
        :options="['bug' => 'Bug', 'feature' => 'Feature', 'other' => 'Other']"
    />

    <x-hwc::conditional-field :when="['reason' => ['bug', 'feature']]">
        <x-hwc::field name="details" label="What happened?">
            <x-hwc::textarea name="details" />
        </x-hwc::field>
    </x-hwc::conditional-field>
</form>




















```
#### Edit forms — the `:model` prop

Pass the same model your `<x-hwc::input>` / `<x-hwc::select>` / `<x-hwc::textarea>` already read from. The component evaluates `old($field, data_get($model, $field))` for each trigger named in `when`, lining initial visibility up with the model on the first GET while keeping `old()` winning on validation retry.

```blade
<x-hwc::conditional-field :model="$message" :when="['reason' => 'other']">
    <x-hwc::input name="other_reason" :value="$message->other_reason" />
</x-hwc::conditional-field>




















```
When the trigger name does not match the model attribute (nested attributes like `$user->address->country`, camelCase models, foreign-key vs display-value pickers), define an accessor on the model or pass an associative `$state` array as `:model` — `data_get` accepts arrays, so a single `$state` map at the top of the form can resolve every `when` key to its real source.

#### Recipe

New cookbook entry at `docs/recipes/conditional-fields.md` covers five real-world patterns — "other" reason, ship-to-different-address, subscription tiers, NPS survey follow-ups, and newsletter preferences — plus an edit-form `:model` example.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.21.0...0.22.0

## 0.21.0 - 2026-06-09

### Disclosure controller

New `disclosure` Stimulus controller — collapsible inline content with proper ARIA, the base primitive for "read more" sections, FAQ items, and accordion patterns.

```html

<div data-controller="disclosure">
    <button type="button"
            data-disclosure-target="trigger"
            data-action="disclosure#toggle"
            aria-expanded="false">Read more</button>
    <div data-disclosure-target="content" hidden>...</div>
</div>





















```
Two-way `open` value (default `false`), idempotent `toggle` / `open` / `close` actions, and a `disclosure:change` event with `{ open: bool }` for hooking analytics, icon swaps, or chained UI off transitions. The `content` target is required; the `trigger` target is optional and receives `aria-expanded` sync when present.

#### Programmatic control via outlets

Open or close from another controller:

```js
static outlets = ["disclosure"];

revealHelp() {
    this.disclosureOutlet.open();
}





















```
Always call the methods (not `outlet.openValue = true`) — they sync DOM and dispatch synchronously, while raw value writes go through Stimulus's MutationObserver path and update asynchronously.

### Accordion recipe

New cookbook entry at `docs/recipes/accordion.md` covering both paths:

- **Native `<details>`** for static FAQ-style accordions — gets ARIA, keyboard handling, single-open via the native `toggle` event, and `::details-content` animation for free.
- **Controller-based patterns** — independent disclosures, single-open via Stimulus outlets, server-rendered initial state, and URL-driven sections — for when state needs to be JS- or server-driven.

Includes a "when is `<details>` not the right answer" checklist so the choice between native and controller stays explicit.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.20.0...0.21.0

## 0.20.0 - 2026-06-09

### Password visibility controller

New `password-visibility` Stimulus controller toggles a password input between hidden and visible, keeping the optional button target's `aria-pressed` and `aria-label` in sync.

```html

<div data-controller="password-visibility">
    <input type="password" name="password" data-password-visibility-target="input"/>
    <button
        type="button"
        data-password-visibility-target="button"
        data-action="password-visibility#toggle"
    >👁</button>
</div>






















```
`aria-label` is driven by the `show-label` / `hide-label` values (defaults `Show password` / `Hide password`). A `password-visibility:change` event with `{ visible: bool }` fires on every transition so a small companion controller — or another listener — can swap icons. `connect()` always forces `type="password"`: visibility is never persisted across Turbo morphs or Drive navigations.

### Autofocus controller

New `autofocus` Stimulus controller focuses the first matching field on `connect()` and on `turbo:frame-load`, filling the gap left by native HTML `[autofocus]` which does not fire on Drive visits or frame swaps.

```html

<form data-controller="autofocus" action="/messages" method="POST">
    <input type="text" name="title" autofocus/>
</form>






















```
Three strategies are available via `strategy-value`: `autofocus-attribute` (default — first `[autofocus]`), `first-focusable` (first `<input>` / `<select>` / `<textarea>` / `<button>`), and `target` (the `field` Stimulus target). All strategies skip `[disabled]`, `[type="hidden"]`, `[tabindex="-1"]`, and descendants of `[hidden]` / `[aria-hidden="true"]`. The controller never steals focus from an element already active inside its scope, and focuses with `{ preventScroll: true }` unless `scroll-into-view-value="true"` opts in.

### Back to top controller

New `back-to-top` Stimulus controller toggles `data-visible="true|false"` on its element as `window.scrollY` crosses a configurable threshold, and exposes a `scrollToTop` action that respects `prefers-reduced-motion`.

```html

<button
    type="button"
    data-controller="back-to-top"
    data-action="back-to-top#scrollToTop"
    class="fixed bottom-6 right-6 transition-opacity
           data-[visible=false]:opacity-0 data-[visible=false]:pointer-events-none
           data-[visible=true]:opacity-100"
    aria-label="Back to top"
>↑</button>






















```
Default threshold is `400` (strict greater-than). The scroll listener is throttled via `requestAnimationFrame` and cleaned up on disconnect. No styles are shipped — the controller only writes the `data-visible` attribute, so consumers drive the show/hide transition with Tailwind `data-[visible=...]` variants or plain CSS.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.19.1...0.20.0

## 0.19.1 - 2026-06-09

### 0.19.1

#### Eliminate the loading template race condition

The modal's loading template is now injected synchronously on `turbo:before-fetch-request` instead of being queued through `showLoading()` and a `setTimeout(0)` racing against `turbo:before-fetch-response`. Behavior is identical for users in every observed flow, with one quiet improvement: programmatic `frame.src` changes that previously skipped the template (because there was no click) now show it correctly.

The public `modal#showLoading` Stimulus action is removed — no code in the package referenced it and the Blade component never emitted `data-action="modal#showLoading"`. Custom markup that called it manually will need to drop the action.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.19.0...0.19.1

## 0.19.0 - 2026-06-09

### 0.19.0

#### Modal `size` prop

Single `size` prop replaces the previous `allow-small-width` and `allow-full-width` booleans. Presets follow a monotonically increasing scale (`sm < md < lg < xl`) at any viewport, so the chosen size is predictable regardless of screen width or browser zoom. Arbitrary CSS lengths are forwarded as inline `max-width`.

```blade
<x-hwc::modal size="sm">...</x-hwc::modal>      {{-- md:max-w-md, 448px --}}
<x-hwc::modal>...</x-hwc::modal>                 {{-- size=md default, md:max-w-xl, 576px --}}
<x-hwc::modal size="lg">...</x-hwc::modal>       {{-- md:max-w-3xl, 768px --}}
<x-hwc::modal size="xl">...</x-hwc::modal>       {{-- md:max-w-5xl, 1024px --}}
<x-hwc::modal size="full">...</x-hwc::modal>     {{-- fills the viewport, close button moves inside --}}
<x-hwc::modal size="auto">...</x-hwc::modal>     {{-- sizes to content, no width constraints --}}
<x-hwc::modal size="50vw">...</x-hwc::modal>     {{-- arbitrary CSS length --}}
























```
`allow-small-width` and `allow-full-width` are removed. Use `size="auto"` to keep the old "no width constraints" behavior, or `size="50vw"` to keep the old "half viewport" default. The migration table in `docs/components/modal.md` maps every previous combination to the new prop.

#### Modal scroll container clips horizontal overflow

`overflow-x-hidden` is now applied to the modal's inner scroll container. Without it, the CSS quirk that promotes `overflow-x: visible` to `auto` when `overflow-y: auto` is set could raise a spurious horizontal scrollbar whenever content was wider than the dialog.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.18.0...0.19.0

## 0.18.0 - 2026-06-08

### Frame-or-page Blade component

New `<x-hwc::frame-or-page>` component renders a view as a Turbo Frame payload or wrapped in a layout based on the `Turbo-Frame` request header — one view, two presentations.

#### Usage

```blade
<x-hwc::frame-or-page frame="modal" layout="layouts.dashboard">
    <form>...</form>
</x-hwc::frame-or-page>

























```
#### Model-aware frame ids

Pass a Model instead of a string; the component calls `dom_id()` to derive the frame id.

```blade
<x-hwc::frame-or-page :frame="$message" layout="layouts.dashboard">
    ...
</x-hwc::frame-or-page>

























```
**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.17.1...0.18.0

## 0.17.1 - 2026-06-08

* Bump deps (php/js)

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.17.0...0.17.1

## 0.17.0 - 2026-06-04

### Carousel progress bar and slide counter

The `<x-hwc::carousel>` component now supports an opt-in progress bar and slide counter.

#### Progress bar

```blade
<x-hwc::carousel :progress="true"
                 progress-class="h-1 bg-red-500"
                 progress-wrapper-class="max-w-xs bg-gray-200 rounded-md h-1">



























```
#### Slide counter

```blade
<x-hwc::carousel :counter="true"
                 counter-class="text-sm">



























```
**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.16.0...0.17.0

## 0.16.0 — Carousel extensibility via subclassing - 2026-06-03

### Carousel extensibility via subclassing

The `<x-hwc::carousel>` component now supports a `controller` prop that lets you swap the mounted Stimulus identifier so subclasses can inherit from `CarouselController` and supply Embla plugins.

#### Extending the controller

```js
// resources/js/controllers/gallery_controller.js
import CarouselController from "./carousel_controller";
import Autoplay from "embla-carousel-autoplay";

export default class extends CarouselController {
    emblaPlugins() {
        return [Autoplay({ delay: 4000 })];
    }
}




























```
```blade
<x-hwc::carousel controller="gallery">
    <div>slide 1</div>
    <div>slide 2</div>
</x-hwc::carousel>




























```
Plugin imports load lazily with the subclass chunk. `play()` and `stop()` delegate to `embla.plugins()?.autoplay` when present.

#### Identifier-independent structural hooks

Viewport and container are no longer Stimulus targets — they use `data-carousel-viewport` and `data-carousel-container` hooks so a subclass reuses the same CSS and layout without per-identifier attributes.

#### Subclass values pass through

The root element filters only the component`s own `data-{identifier}-*` prefixes (`options-`, `active-dot-class`, `disabled-nav-class`). Any additional value your subclass declares (e.g. `data-gallery-delay-value`) passes through freely.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.15.2...0.16.0

## 0.15.2 - 2026-06-03

Internal refactor — no behavior change.

- Centralize package-manager detection and package.json devDependency writes in the PackageInstaller service, removing duplicated logic across the install, ui and check commands (#22).

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.15.1...0.15.2

## 0.15.1 - 2026-06-03

Fixes hotwire:controllers --outdated missing drifted shared dependencies.

- A published controller now counts as outdated when its own file OR any of its already-published shared deps (e.g. carousel.css) differ from the package — so --outdated --force updates a stale dependency even when the controller file itself is unchanged (#21).
- Docs: README now lists the Carousel controller and documents hotwire:check's direct-controller detection.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.15.0...0.15.1

## 0.15.0 - 2026-06-03

hotwire:check now detects Stimulus controllers used directly, not just via components — data-controller attributes and the stimulus_controller() / stimulus()->controller()/controllers() / stimulus_action() / stimulus_target() helpers (#20).

- Only package-registered controllers are checked; user-defined ones are ignored.
- Comments, <script> and <style> blocks are stripped before scanning, so commented-out code is ignored.
- May surface new CI failures (exit 1): a package controller used via a raw data-controller, without its component and not yet published, is now reported.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.14.0...0.15.0

## 0.14.0 - 2026-06-03

Carousel for Hotwire — the Embla-powered `carousel` controller plus the `<x-hwc::carousel>` Blade component.

- Add carousel controller (Embla) (#18) — drag, loop, axis, breakpoints, reduced-motion, dot/nav wiring.
- Add Carousel Blade component (#19) — prev/next nav, pagination dots, responsive options, CSS-variable sizing, `prev_button`/`next_button`/`dot_template` slots, and a `nav-wrapper-class` prop to group the nav buttons.

Full Changelog: https://github.com/emaia/laravel-hotwire/compare/0.13.0...0.14.0

## 0.13.0 - 2026-06-02

### What's Changed

* Add Dropdown component by @emaia in https://github.com/emaia/laravel-hotwire/pull/17

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.6...0.13.0

## 0.12.6 - 2026-06-01

### What's Changed

* Add controllers() helper to Stimulus builder by @emaia in https://github.com/emaia/laravel-hotwire/pull/16

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.5...0.12.6

## 0.12.5 - 2026-06-01

### What's Changed

* Add dropdown controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/15

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.4...0.12.5

## 0.12.4 - 2026-06-01

### What's Changed

* Add slug controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/14

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.3...0.12.4

## 0.12.3 - 2026-06-01

### What's Changed

* Introduce `stimulus()` as the primary attribute-builder entry point by @emaia
* Add missing tabs controller reference to the README by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.2...0.12.3

## 0.12.2 - 2026-05-29

### What's Changed

* Add tabs controller by @emaia in https://github.com/emaia/laravel-hotwire/pull/13

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.1...0.12.2

## 0.12.1 - 2026-05-28

### What's Changed

* Improve the auto-submit controller by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.12.0...0.12.1

## 0.12.0 - 2026-05-28

### What's Changed

* add Stimulus attribute helpers for Blade by @emaia in https://github.com/emaia/laravel-hotwire/pull/12

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.11.0...0.12.0

## 0.11.0 - 2026-05-28

### What's Changed

* add per-toast position to flash-message by @emaia in https://github.com/emaia/laravel-hotwire/pull/11

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.10.0...0.11.0

## 0.10.0 - 2026-05-28

### What's Changed

* Update emaia/laravel-hotwire-turbo requirement from ^0.8.4 to ^0.9.2 by @dependabot[bot]
  in https://github.com/emaia/laravel-hotwire/pull/9
* add form components and controllers by @emaia in https://github.com/emaia/laravel-hotwire/pull/10

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.12...0.10.0

## 0.9.12 - 2026-04-30

### What's Changed

* Improve the modal component, controller, docs and recipes by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.11...0.9.12

## 0.9.11 - 2026-04-29

### Added

* `hotwire:check` now detects the npm dependencies required by the Stimulus controllers of used components (e.g.
  `@emaia/sonner` for `<x-hwc::flash-message>`) and reports those missing from the app's `package.json`.
  `--fix` additionally adds them to `devDependencies` alongside publishing controllers.
* `<x-hotwire::...>` is now recognized globally as an alias for the configured Blade component prefix, regardless of
  the value of `hotwire.prefix`.

### Fixed

* `hotwire:check` now recognizes the `hotwire::` alias alongside the configured prefix, so components written as
  `<x-hotwire::...>` are no longer silently skipped.
* `<x-hotwire::flash-message />` (and any other component) no longer renders without its backing PHP class when the
  configured prefix differs from `hotwire` — the service provider now registers both prefixes.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.2...0.9.11

## 0.9.2 - 2026-04-29

### What's Changed

* Add docs cli by @emaia in https://github.com/emaia/laravel-hotwire/pull/8

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.1...0.9.2

## 0.9.1 - 2026-04-28

### What's Changed

* Add `input-mask` and `money-input` controllers by @emaia
* Add an `--outdated` flag to `hotwire:controllers` to update only published controllers that changed by @emaia
* Improve the clean-query-params controller by @emaia
* Standardize controller names and refactor the docs by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.9.0...0.9.1

## 0.9.0 - 2026-04-27

### What's Changed

* Add global registry for components/controllers by @emaia in https://github.com/emaia/laravel-hotwire/pull/5
* Modal refactor by @emaia in https://github.com/emaia/laravel-hotwire/pull/6
* Confirm dialog refactor by @emaia in https://github.com/emaia/laravel-hotwire/pull/7

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.8.0...0.9.0

## 0.8.0 - 2026-04-22

### What's Changed

* Move controllers to flat structure by @emaia in https://github.com/emaia/laravel-hotwire/pull/3
* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot]
  in https://github.com/emaia/laravel-hotwire/pull/4

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/emaia/laravel-hotwire/pull/4

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.6...0.8.0

## 0.7.5 - 2026-04-17

### What's Changed

* feat: add optimistic UI primitives (component + form/link/dispatch controllers)  by @emaia
  in https://github.com/emaia/laravel-hotwire/pull/2

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.4...0.7.5

## 0.7.4 - 2026-04-13

### What's Changed

* Bump dependencies and update the README by @emaia

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.3...0.7.4
