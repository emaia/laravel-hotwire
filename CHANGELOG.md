# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

## 0.75.0 - 2026-08-31

### Shared Alert Dialog hosts

Adds a scoped shared Alert Dialog for collection actions, alongside accessible overlay naming and resilient deferred action replay.

#### Reuse one Alert Dialog across a collection

- Add `<hw:alert-dialog.host>` and `<hw:alert-dialog.trigger>` to share one overlay across collection actions.
- Support per-trigger content and variants while preserving focus, accessible names and first-action ownership.
- Keep pending presentation and guarded replay stable across nested overlays, reconnects, closing transitions and Turbo morphs.
- Scope the Blade trigger to the same component render tree as its host; independently rendered Turbo Stream rows remain outside this mode.

See [Alert Dialog shared hosts](https://github.com/emaia/laravel-hotwire/blob/0.75.0/docs/components/alert-dialog.md#shared-host).

#### Accessible overlay names

- Give Modal, Sheet, Drawer and Alert Dialog stable accessible names and descriptions.
- Reconcile frame-loaded labels before opening while preserving authored ARIA and nested overlay boundaries.
- Use `role="alertdialog"` for confirmation dialogs.

See [Modal accessibility](https://github.com/emaia/laravel-hotwire/blob/0.75.0/docs/components/modal.md#accessibility) and [Alert Dialog accessibility](https://github.com/emaia/laravel-hotwire/blob/0.75.0/docs/components/alert-dialog.md#accessibility).

#### Resilient confirmation actions

- Preserve deferred links, submitters and generic actions through lifecycle changes and Turbo morphs.
- Fail closed when action identity or navigation context changes and dispatch `alert-dialog:dropped`.
- Restore overlay and top-layer state across reconnects and nested overlay moves.

See [Alert Dialog automatic behavior](https://github.com/emaia/laravel-hotwire/blob/0.75.0/docs/components/alert-dialog.md#automatic-behavior).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.74.0...0.75.0

## 0.74.0 - 2026-08-27

### Selective CSS bundles and stable component identity

Adds dependency-closed CSS bundles and deterministic component identity across Turbo morphs, with scoped Sidebar styles and stricter Stimulus controller overrides.

#### Modular and selective CSS

- Keep Nova's complete public entrypoint while splitting its visual rules into ordered, auditable modules.
- Add `hotwire:styles` to generate a layout-specific bundle from selected components, controllers and their transitive visual dependencies.
- Make `hotwire:check` detect missing coverage, stale generated bundles and unsafe overwrite targets.
- Compile every public preset and a selective fixture through Tailwind CSS v4 as part of the package build contract.

See [Presets](https://github.com/emaia/laravel-hotwire/blob/0.74.0/docs/presets.md#generate-a-selective-bundle).

#### Stable identity across Turbo morphs

- Generate deterministic component ids within page and Turbo Frame response roots, with persisted-model identities for lists and cross-request fragments.
- Preserve stateful JavaScript wrappers across Turbo morphs and rebuild Carousel at its selected snap when server-rendered HTML replaces Embla-managed state.
- Add collision-safe Toggle input ids and the opt-in `dev--duplicate-ids` diagnostic controller.

See [Stable component ids](https://github.com/emaia/laravel-hotwire/blob/0.74.0/docs/recipes/stable-component-ids.md) and [`dev--duplicate-ids`](https://github.com/emaia/laravel-hotwire/blob/0.74.0/docs/controllers/dev/duplicate-ids.md).

#### Nested Sidebar styling

- Scope icon-collapsed Sidebar rules to their owning provider so an outer Sidebar no longer resizes or hides content inside a nested provider.

#### Stimulus controller identifier hardening

- Reject invalid custom controller identifiers before they can form HTML attribute names.
- Revalidate inherited and final-render identifiers so intermediate Blade components and named slots cannot bypass the guard.

**Upgrade note:** custom `controller` props must start with a lowercase letter or digit and contain only lowercase letters, digits, `_` or `-`. Invalid values now throw `InvalidArgumentException`.

See [The `controller` prop is validated](https://github.com/emaia/laravel-hotwire/blob/0.74.0/docs/upgrade.md#the-controller-prop-is-validated).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.73.1...0.74.0

## 0.73.1 - 2026-08-25

### Dependency and service provider housekeeping

Keeps runtime dependencies explicit and removes the package service-provider abstraction without changing the package's public integration points.

#### Dependency declarations

- Move every controller runtime dependency into `dependencies`, leaving development tooling in `devDependencies`.
- Add npm dependency monitoring to Dependabot.
- Remove the unused `spatie/laravel-package-tools` Composer dependency.

#### Service provider

- Register configuration, views, translations, commands and Blade integration directly through Laravel's service provider.
- Preserve the existing publish tags and destinations for configuration, views and translations.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.73.0...0.73.1

## 0.73.0 - 2026-08-25

### Session flash toasts and safe quoted attributes

Lets the Toaster own redirect flash notifications while preserving quoted values across every package-rendered HTML attribute.

#### Toaster session flash

- Render redirect flash notifications directly from `<hw:toaster />`, removing the need for a separate layout-level `<hw:toast />`.
- Add `RedirectResponse::toast()` with the same type, message, description and position arguments as the Turbo Stream macro.
- Support structured `toast` payloads and Laravel's `success`, `error`, `errors`, `warning` and `info` session keys, including named validation bags.
- Claim each flash notification once, omit empty messages and preserve buffered toasts across permanent and replaced Turbo Drive viewports.
- Keep the previous split available with `<hw:toaster :flash="false" />`.

See [Toaster session flash](https://github.com/emaia/laravel-hotwire/blob/0.73.0/docs/components/toaster.md#session-flash) and the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.73.0/docs/upgrade.md#the-toaster-reads-the-session-flash).

#### Quoted attribute values

- Encode double quotes before `ComponentAttributeBag` renders package-managed attributes.
- Preserve complete messages, titles, placeholders, ARIA labels and Stimulus values containing quotes without changing existing action syntax or double-encoding entities.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.72.0...0.73.0

## 0.72.0 - 2026-08-25

### Form, Progress and Stimulus identifier isolation

Completes the remaining Blade context isolation so nested application components cannot replace owner state, progress values or internal Stimulus wiring.

#### Breaking changes

- Form and Progress now publish family-scoped component data instead of generic state and percentage keys.
- Chart, Map, Rich Text and File Upload no longer expose the generic `identifier` property or descendant component data; use the public `controller` prop or pass identifiers explicitly.
- An empty `<hw:progress.value />` now requires an owning Progress unless `standalone` is set.
- Progress and its subcomponents now reserve their documented `data-slot` values.

See the [Form and Progress migration](https://github.com/emaia/laravel-hotwire/blob/0.72.0/docs/upgrade.md#form-and-progress-context-keys-are-scoped) and [Stimulus identifier migration](https://github.com/emaia/laravel-hotwire/blob/0.72.0/docs/upgrade.md#stimulus-component-identifiers-are-no-longer-duplicated).

#### Form and Progress

- Keep Conditional Field state and Progress percentages bound to their owning roots through nested Blade wrappers.
- Resolve explicit Progress tracks structurally, including raw tracks and nested Progress instances.
- Support intentional standalone Conditional Fields and dynamic standalone Progress values.

See [Conditional Field](https://github.com/emaia/laravel-hotwire/blob/0.72.0/docs/components/conditional-field.md) and [Progress](https://github.com/emaia/laravel-hotwire/blob/0.72.0/docs/components/progress.md).

#### Stimulus controller identifiers

- Preserve custom controller wiring across Chart, Map, Rich Text and File Upload without leaking generic identifier context.
- Keep component-owned values, targets, actions and outlets scoped to the configured controller.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.71.0...0.72.0

## 0.71.0 - 2026-08-22

### Field and selection group context isolation

Scopes Field, Radio Group, Checkbox Group and Toggle Group ownership so nested Blade composition preserves form identity, validation wiring and accessible labels.

#### Breaking changes

- Field and selection groups now expose family-scoped component-data keys instead of generic `name`, `id`, `errorKey`, `required`, selection and presentation keys.
- Selection group items must render under their owning Radio Group, Checkbox Group or Toggle Group root.
- Standalone controls no longer emit an automatic `aria-describedby="{id}-error"` without a Field owner.
- Overlay roots block inherited Field identity, required state and ownership context.
- Repeated standalone Checkbox controls with array names now append the value slug to generated ids.

See the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.71.0/docs/upgrade.md#field-context-keys-are-scoped).

#### Field ownership and accessibility

- Resolve control, label, error and validation identities through shared precedence rules.
- Associate automatic labels from the controls and selection groups actually rendered in the Field slot.
- Keep multi-control Fields and explicit sets named without dangling `for` attributes.
- Follow the sole registered control or selection group for automatic errors and required markers.

See [Field](https://github.com/emaia/laravel-hotwire/blob/0.71.0/docs/components/field.md).

#### Selection groups

- Isolate Radio Group, Checkbox Group and Toggle Group context from intermediate and nested components.
- Keep item ids, submitted names, old input, validation state and auto-submit configuration bound to the nearest owner.
- Generate stable unique label and control ids for repeated values.

See [Radio Group](https://github.com/emaia/laravel-hotwire/blob/0.71.0/docs/components/radio-group.md), [Checkbox Group](https://github.com/emaia/laravel-hotwire/blob/0.71.0/docs/components/checkbox-group.md) and [Toggle Group](https://github.com/emaia/laravel-hotwire/blob/0.71.0/docs/components/toggle-group.md).

#### Overlay boundaries

- Prevent Alert Dialog, Drawer, Dropdown, Hover Card, Modal, Popover and Sheet contents from inheriting an outer Field identity.
- Allow a nested Field inside an overlay to start a fresh context normally.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.70.0...0.71.0

## 0.70.0 - 2026-08-20

### Overlay context isolation

Scopes Blade-aware context across Modal, Sheet, Drawer, Dropdown, Popover and Hover Card so nested or intermediate components cannot override the owning overlay's state, ARIA wiring, placement, motion, frame or backdrop configuration.

#### Breaking changes

- Modal, Sheet and Drawer no longer expose overlay configuration through generic component-data keys.
- Dropdown, Popover and Hover Card now use family-scoped keys for root, trigger, content and dependent subcomponent context.
- Modal, Sheet and Drawer content must render under its owning root; their triggers can still render standalone.
- Dropdown, Popover and Hover Card triggers and content must render under their owning root.
- Application subcomponents consuming the previous internal `@aware` keys must migrate to the scoped names.

See the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/upgrade.md#floating-overlay-context-keys-are-scoped).

#### Modal overlays

- Isolate Modal, Sheet and Drawer context from intermediate Blade components.
- Preserve standalone trigger rendering for layout-shared overlays and Turbo Frame flows.
- Remove root-only Modal sizing helpers from descendant component data.

See [Modal](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/modal.md), [Sheet](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/sheet.md) and [Drawer](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/drawer.md).

#### Floating overlays

- Isolate Dropdown, Popover and Hover Card state, placement and presentation context.
- Keep nested overlays bound to their nearest owning root.
- Fail clearly when dependent triggers or content render without a usable controller owner.

See [Dropdown](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/dropdown.md), [Popover](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/popover.md) and [Hover Card](https://github.com/emaia/laravel-hotwire/blob/0.70.0/docs/components/hover-card.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.69.0...0.70.0

## 0.69.0 - 2026-08-19

### Component-aware context isolation

Scopes Blade aware context keys across composable components so nested Tabs, Accordion, Side Panel and Sidebar instances keep their own controller identifiers, ARIA relationships and state.

#### Breaking changes

- Tabs, Accordion and Side Panel no longer expose the generic `identifier` component-data key to subcomponents.
- Side Panel no longer exposes its resolved id as `panelId`; the public `panel-id` input prop is unchanged.
- Tabs triggers and panels, Accordion items, and Side Panel panels and triggers now throw when rendered without their owning root.
- Application subcomponents that consume these internal `@aware` keys must use the new scoped names documented in the upgrade guide.

See the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.69.0/docs/upgrade.md#component-aware-context-keys-are-scoped).

#### Sidebar reliability

- Scope Sidebar state, overlay targets and hyphenated provider identifiers to the owning provider.
- Keep icon-mode Sidebar content vertically reachable while preserving accessible navigation semantics.
- Add browser coverage for Turbo renders and Sidebar overflow behavior.

See [Sidebar](https://github.com/emaia/laravel-hotwire/blob/0.69.0/docs/components/sidebar.md).

#### Pagination accessibility

- Remove prohibited ARIA attributes from disabled previous/next pagination controls while preserving disabled-state styling hooks.

See [Pagination](https://github.com/emaia/laravel-hotwire/blob/0.69.0/docs/components/pagination.md).

#### Button Turbo history docs

- Document `data-turbo-action="advance"` usage for Button-driven Turbo Frame navigation.

See [Button](https://github.com/emaia/laravel-hotwire/blob/0.69.0/docs/components/button.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.68.0...0.69.0

## 0.68.0 - 2026-08-14

### Documentation consistency and Turbo polish

Refreshes package documentation and cookbook recipes around current APIs, adds typed registry categories, stabilizes controller tests, and removes Sidebar transition flicker.

#### Documentation and cookbook

The README is now a concise entry point with direct component, controller and documentation indexes. Component docs, controller docs and cookbook recipes now prioritize current package abstractions, including frame-or-page views, conditional forms, overlays, Carousel, Maps, Charts and Rich Text.

See the [README](https://github.com/emaia/laravel-hotwire/blob/0.68.0/README.md) and [Cookbook](https://github.com/emaia/laravel-hotwire/blob/0.68.0/docs/recipes/readme.md).

#### Typed registry categories

Components and controllers now share the `Registry\Category` enum, preventing invalid categories and keeping related entries aligned. `hotwire:docs --list` follows the canonical category order.

`ComponentDefinition::$category` and `ControllerDefinition::$category` now return `Registry\Category`; use `->value` when a string is required.

See [Registry](https://github.com/emaia/laravel-hotwire/blob/0.68.0/docs/registry.md).

#### Reliable controller tests

The Bun suite now runs serially to avoid excessive memory consumption. happy-dom globals are released between files, while behavior that depends on real MutationObserver, focus or browser timing is covered by Playwright.

#### Sidebar transition polish

Sidebar snapshots no longer use the browser's default crossfade during Turbo renders, preventing flicker when filters, forms or active navigation state change.

See [Sidebar](https://github.com/emaia/laravel-hotwire/blob/0.68.0/docs/components/sidebar.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.67.0...0.68.0

## 0.67.0 - 2026-08-14

### Reveal component and controller

Laravel Hotwire now ships a first-paint-safe Reveal component for entrance cascades, plus configurable controller loading policies for critical Stimulus controllers.

#### Reveal

- Add `<hw:reveal>` and `<hw:reveal.item>` for CSS-first entrance cascades that remain visible if JavaScript is disabled or delayed.
- Support direct-child item mode, explicit item mode, scroll-triggered reveal, nested Reveal isolation, reduced-motion handling, and a document-wide `data-reveal="off"` escape hatch.
- Integrate Reveal with Turbo cache/stream safeguards, Sidebar document chrome, Animated Number restarts, structural CSS, Nova motion presets, catalog metadata, IDE metadata, and public documentation.
- Harden related browser transition behavior for Sidebar view-transition naming, Toaster entries during Turbo renders, and Color Scheme transition cleanup.

See the Reveal component and controller docs for examples.

#### Controller loading policy

- Add configurable `controllers.preload` and `controllers.eager` policies for critical package and application Stimulus controllers.
- Keep lazy loading as the default while allowing selected controllers to be fetched early or included in the entry graph when execution order requires it.
- Update installation/check tooling and generated controller indexes to respect the configured loading policy.

See the Stimulus controller documentation for preload and eager-loading guidance.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.66.0...0.67.0

## 0.66.0 - 2026-08-12

### Read More and Side Panel

Adds progressive content expansion and composable inline workspace panels with persistent server-rendered state.

#### Read More

- Clamp overflowing content with progressive enhancement and no-script fallback.
- Animate expansion and collapse while handling resize, Turbo morphs and interrupted transitions.
- Expose accessible labels, state hooks and change events.

See [Read More](https://github.com/emaia/laravel-hotwire/blob/0.66.0/docs/components/read-more.md) for usage and examples.

#### Side Panel

- Compose inline panels with panel, trigger and inset primitives.
- Persist server-rendered state without first-paint flash.
- Support nesting, Turbo renders, accessible focus handling and physical left/right placement in LTR and RTL.
- Keep the collapsed rail and trigger visible without leaking or clipping panel content.

See [Side Panel](https://github.com/emaia/laravel-hotwire/blob/0.66.0/docs/components/side-panel.md) for usage and examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.65.0...0.66.0

## 0.65.0 - 2026-08-11

### Rich text content validation

Rich-text fields can now validate semantic content and visible text length without measuring serialized HTML markup.

#### Server-side rich text validation

- Added `RichTextContent` to normalize HTML entities, block boundaries, Unicode empty states, media, embeds, opaque fallback and SVG text.
- Added composable `RichText::required()`, `requiredIf()`, `min()` and `max()` Laravel validation rules.
- Added namespaced validation translations with automatic English defaults and optional locale publishing through `hotwire-translations`.
- Declared the DOM, libxml and mbstring PHP extensions required by server-side rich-text parsing.

See [Rich Text](https://github.com/emaia/laravel-hotwire/blob/0.65.0/docs/components/rich-text.md#server-side-content-validation) for usage and validation examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.64.0...0.65.0

## 0.64.0 - 2026-08-10

### Incremental pagination loading

Pagination now supports opt-in load-more and infinite loading flows while keeping the next-page link as a no-JavaScript fallback.

#### Pagination incremental loading

- Added a `pagination` Stimulus controller that fetches server-rendered next pages, appends only the configured target contents, and replaces/removes the pagination control.
- Added `<hw:pagination incremental>` and `<hw:pagination infinite>` props with `append-to`, `scroll-to`, loading/status labels, observer configuration and Stimulus merge support.
- Added loading, spinner, status and icon slots with structural CSS for loading-state content swaps.
- Documented response shape, retry behavior after failed infinite auto-loads, standalone controller values and styling hooks.

#### Turbo frame scroll preservation

- Added preserve-scroll support for Turbo Frames.
- Added controller coverage for restoring frame scroll positions across Turbo-driven updates.
- Documented frame preserve-scroll usage.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.63.0...0.64.0

## 0.63.0 - 2026-08-08

### Native toast stack, meta umbrella and back-to-top

This release replaces the Sonner-backed toast stack with native package controllers, adds reusable Hotwire meta components, and ships a Back To Top component.

#### Native toast stack

- Replaces `@emaia/sonner` with native `toast` and `toaster` controllers, removing the Sonner/React runtime dependency.
- Renames `<hw:flash-container>` to `<hw:toaster>` and `<hw:flash-message>` to `<hw:toast>` with no compatibility aliases.
- Adds `window.toaster` helpers and the package-level `turbo_stream()->toast(...)` macro.
- Moves toast stack geometry into structural CSS and Nova visual styling into preset slots.
- Documents migration notes, removed Sonner props and the new toast/toaster APIs.

#### Meta components

- Adds umbrella `<hw:meta>` plus granular meta components for Turbo prefetch, refresh, cache, visit control, root, view transition, CSRF and color scheme.
- Renders `<hw:color-scheme.script />` from `<hw:meta color-scheme>` so the umbrella applies `data-theme` before paint.
- Handles `refresh`/`scroll` as one validated meta pair with sensible defaults.

#### Back To Top

- Adds `<hw:back-to-top>` and the `back-to-top` controller.
- Includes browser coverage for scroll visibility and activation behavior.

#### Fixes and test stability

- Stabilizes checkbox select-all reset rebinding coverage under happy-dom.
- Fixes native toaster teardown, pause state and keyboard focus order before the toast PR was merged.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.62.0...0.63.0

## 0.62.0 - 2026-08-07

### Custom presets and the slot contract behind them

Laravel Hotwire 0.62.0 adds `hotwire:make-preset`, publishes the full slot inventory the package styles, separates component mechanics from appearance, and fixes the Accordion closing without an animation.

#### Generate a custom preset

`php artisan hotwire:make-preset brand` writes a scaffold holding every rule the shipped presets define with an empty body, grouped by the component that owns it — including the compound selectors whose state lives on an ancestor, which no list of slot names can express. `--from=nova` clones a shipped preset instead of starting empty.

The rules arrive in the order the source preset declares them, and that order is part of the contract: every rule sits in the same `@layer components`, so between equal specificities the later one wins.

See [Presets](https://github.com/emaia/laravel-hotwire/blob/0.62.0/docs/presets.md).

#### Slot inventory in the catalog

Every catalog entry now declares the `data-slot` values it emits, marked visual or structural, controllers that build their own DOM included. A slot that appears in a view, in rendered output or in package JavaScript without being declared fails the suite, and every visual slot must be styled by every preset.

See [Registry](https://github.com/emaia/laravel-hotwire/blob/0.62.0/docs/registry.md).

#### Structural stylesheet

`resources/css/structural.css`, imported by every preset, now carries the rules whose absence leaves a component broken rather than restyled: the Accordion collapse, the carousel track geometry, the top-layer `[popover]` reset and the runtime utility safelist. The carousel geometry used to ship from a controller import and only applied once the bundle had run; it now compiles into the application stylesheet and holds on the first paint.

Presets inherit the safelist rather than freezing a copy, so new package mechanics arrive with the upgrade.

#### Accordion collapse

Closing an Accordion item snapped shut instead of animating. `transition-behavior: allow-discrete` was a separate declaration, and the minifier reorders it ahead of the `transition` shorthand that resets it — correct in the source, wrong in every built stylesheet. It now rides inside the shorthand.

#### Carousel and oembed styling

The carousel navigation, dots, progress bar and counter shipped without a single rule and are now styled. The oembed controller emits slots instead of a utility class and inline styles, so its spacing survives the application's Tailwind build.

See [Carousel](https://github.com/emaia/laravel-hotwire/blob/0.62.0/docs/components/carousel.md) and [oembed](https://github.com/emaia/laravel-hotwire/blob/0.62.0/docs/controllers/oembed.md).

#### Breaking: `data-active` on Navbar items

The Navbar item current-state axis is now `data-active`, matching Pagination, Sidebar and shadcn. Applications targeting `[data-current]` in their own CSS need to update the selector.

See the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.62.0/docs/upgrade.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.61.0...0.62.0

## 0.61.0 - 2026-08-05

### Native Slider and resilient Turbo interactions

Laravel Hotwire 0.61.0 adds a native Slider, safer state preservation across Turbo morphs, lazy Frame Or Page branches, View Transitions and IME-aware form interactions.

#### Native Slider

Add the field-aware `<hw:slider>` component with native range semantics, horizontal and vertical orientations, RTL support, validation, old input restoration, auto-submit and progressive visual fill.

See [Slider](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/components/slider.md).

#### Lazy Frame Or Page branches

Frame Or Page now supports lazy `.frame` and `.page` contextual components, multiple frame targets and target-specific content without evaluating discarded branches.

The previous `frameContent` and `pageContent` slots were removed. See [Frame Or Page](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/components/frame-or-page.md) and the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/upgrade.md).

#### Resilient overlays

Modal, Alert Dialog, Drawer, Sheet and Sidebar now preserve controller-owned presence and top-layer state during Turbo morphs while allowing descendant content to update normally.

Frame-backed Modal, Drawer and Sheet instances can also opt into View Transitions for navigation inside an already-open overlay.

See the [overlay upgrade guidance](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/upgrade.md) and [View Transition controller](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/controllers/turbo/view-transition.md).

#### Morph Guard

Add `turbo--morph-guard` for preserving active editor and form state during external page morphs, with reference-counted ownership and cache-safe cleanup.

See [Morph Guard](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/controllers/turbo/morph-guard.md).

#### IME-safe forms and interactions

Auto Save, Auto Submit, Money Input, overlays, menus, hotkeys and other interactive controllers now avoid submitting, formatting, navigating or closing while an IME composition is active.

#### Color scheme transitions

Color Scheme toggles can opt into reduced-motion-aware View Transitions while initialization and programmatic synchronization remain instant.

See [Color Scheme](https://github.com/emaia/laravel-hotwire/blob/0.61.0/docs/components/color-scheme.md).

#### Maintenance

The obsolete `hotwire:ui` Basecoat installer was removed. Use `hotwire:install` and the shipped Nova preset instead.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.60.0...0.61.0

## 0.60.0 - 2026-08-03

### Reliable form state and Turbo Frame redirects

Laravel Hotwire now preserves form-control state across Turbo rendering lifecycles and tracks originating frame URLs for reliable validation redirects.

#### MultiSelect form lifecycle

MultiSelect now preserves reset and unsaved-change baselines across full-page, Turbo Frame and Turbo Stream responses, while reusing parsed response bodies. Indicators also align with the Nova checkbox visual language. See [Multi Select](https://github.com/emaia/laravel-hotwire/blob/0.60.0/docs/controllers/multi-select.md).

#### CheckboxGroup synchronization

CheckboxGroup now keeps select-all state synchronized across resets, form replacement and Turbo renders, with explicit control over indeterminate behavior through `disable-indeterminate`. See [Checkbox Group](https://github.com/emaia/laravel-hotwire/blob/0.60.0/docs/components/checkbox-group.md).

#### Originating Turbo Frame tracking

The Frame Src controller now scopes request handling to its mounted element, resolves the nearest originating frame URL and preserves explicit headers. Laravel Hotwire now requires `emaia/laravel-hotwire-turbo ^0.12.0`. See [Frame Src](https://github.com/emaia/laravel-hotwire/blob/0.60.0/docs/controllers/turbo/frame-src.md) and the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.60.0/docs/upgrade.md).

#### Interface polish

Breadcrumb icon alignment, bordered Card spacing and restricted Color Scheme toggle behavior now remain consistent across supported configurations.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.59.0...0.60.0

## 0.59.0 - 2026-07-31

### Consistent frame and polymorphic component contracts

Laravel Hotwire now standardizes Turbo Frame targeting and polymorphic Blade semantics across components, overlays and controllers.

#### Consistent Turbo Frame targets

Use the shared `frame` prop across forms, pagination, navigation controls and overlays, with model-backed `dom_id()` resolution and predictable native `data-turbo-frame` overrides. See [Frame](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/frame.md), [Form](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/form.md) and [Pagination](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/pagination.md).

#### Safer polymorphic components

Polymorphic `as` values and native button types now use explicit allowlists, disabled anchors stay inert and accessible, and Dropdown `as-child` composition requires exactly one button or anchor root. See [Button](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/button.md), [Conditional Field](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/conditional-field.md) and [Dropdown](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/dropdown.md).

#### Frame-aware overlays and controllers

Modal, Drawer and Sheet now own exactly one valid frame host. Frame-sensitive controllers isolate sibling and nested events, while frame-backed modals coordinate loading, focus restoration and close/reopen races. See [Modal](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/modal.md), [Drawer](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/drawer.md) and [Sheet](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/components/sheet.md).

#### Upgrade notes

Pagination now accepts only `frame`, polymorphic tags reject unsupported values, and strict `as-child` or overlay compositions may require template updates. See the [upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.59.0/docs/upgrade.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.58.0...0.59.0

## 0.58.0 - 2026-07-31

### Native file uploads and Attachment primitives

Laravel Hotwire now ships a native upload protocol and reusable Attachment primitives for managed and server-owned file workflows.

#### Attachment primitives

Build upload queues, media libraries, ticket attachments and download lists with the new composable Attachment component family. See [Attachment](https://github.com/emaia/laravel-hotwire/blob/0.58.0/docs/components/attachment.md).

#### Native file uploads

File Upload now uses native XHR requests with queueing, progress, retry, removal, cleanup, list and grid presentations, custom dropzones and geometry-neutral image previews. See [File Upload](https://github.com/emaia/laravel-hotwire/blob/0.58.0/docs/components/file-upload.md).

#### Managed and server-owned workflows

Choose managed JSON output, hybrid JSON with Turbo Streams, or raw server-owned Turbo Stream responses. The new documentation covers opaque upload tokens, signed private previews, safe promotion and orphan cleanup. See [File upload patterns](https://github.com/emaia/laravel-hotwire/blob/0.58.0/docs/recipes/file-upload-patterns.md).

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.57.0...0.58.0

## 0.57.0 - 2026-07-29

### Presence lifecycle for floating surfaces and modal overlays

Presence is the shared state-driven lifecycle that keeps exiting surfaces rendered but inert until CSS motion finishes, then hides them; this release applies it across floating and modal surfaces with resilient Turbo morphing and coordinated native top-layer behavior.

#### Floating surfaces

- Migrates Dropdown, Popover, Hover Card, Multi Select, and Tooltip from class-driven transitions to `data-state="open|closed"`, `hidden`, and `inert`.
- Waits for Floating UI placement before enter, protects against stale positioning writes, and preserves active anchors and focus through Turbo morphs.
- Supports interruptible rapid reopen, `motion="none"`, reduced motion, and coordinated top-layer cleanup.
- Replaces legacy `transition` options with the uniform motion API and adds Tooltip motion integration to Button and Color Scheme Toggle.

See the [Dropdown](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/dropdown.md), [Popover](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/popover.md), [Hover Card](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/hover-card.md), and [Multi Select](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/multi-select.md) documentation for examples.

#### Modal overlays

- Migrates Modal, Alert Dialog, Drawer, Sheet, and mobile Sidebar from duration timers and visual Stimulus classes to the shared Presence lifecycle.
- Preserves overlay stack priority, layer-scoped Escape and outside click, focus trap and return, body scroll lock, and deferred Turbo Stream behavior.
- Rebuilds Presence, focus ownership, and top-layer ordering around Turbo target morphs while synchronous cache teardown remains immediate.
- Keeps nested overlays independent through direct-child state selectors and supports motion cancellation during rapid reopen.

See the [Modal](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/modal.md), [Alert Dialog](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/alert-dialog.md), [Drawer](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/drawer.md), [Sheet](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/sheet.md), and [Sidebar](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/components/sidebar.md) documentation for examples.

#### Upgrade notes

This release removes legacy transition props, duration values, and visual lifecycle classes from the migrated surfaces. Applications with published or customized controllers and CSS should follow the [`0.57.0` upgrade guide](https://github.com/emaia/laravel-hotwire/blob/0.57.0/docs/upgrade.md) and run `php artisan hotwire:check --fix` where applicable.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.56.1...0.57.0

## 0.56.1 - 2026-07-18

### Color scheme toggle icon flicker

Fixes the initial color scheme toggle icon so it matches the stored mode before Stimulus connects.

#### Toggle icon rendering

- Store the pre-connect color scheme mode on `html[data-color-scheme-mode]` from `<hw:color-scheme.script>`.
- Keep `html[data-color-scheme-mode]` synchronized from the `color-scheme` controller.
- Update the Nova preset to choose the toggle icon from the global mode attribute before Stimulus connects, with local toggle state fallbacks when the head script is not used.

#### Documentation

- Document the global mode attribute and the no-flicker icon behavior in the color scheme component and controller docs.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.56.0...0.56.1

## 0.56.0 - 2026-07-18

### Color scheme controller and toggle components

Adds packaged light, dark and system color scheme support using `html[data-theme]`, with an anti-flash head script and a reusable toggle component.

#### Color scheme script and toggle

- Added `<hw:color-scheme.script>` for early `data-theme` and `color-scheme` application before CSS paints.
- Added `<hw:color-scheme.toggle>` with `light`, `dark` and `system` cycle support, optional tooltip integration and Nova preset styling hooks.
- Added embedded `sun`, `moon` and `monitor` icons for the toggle states.

#### Stimulus controller

- Added the `color-scheme` controller to persist the selected mode in `localStorage`.
- Resolves `system` through `prefers-color-scheme` and reacts to media query changes.
- Synchronizes multiple toggles on the same page and across tabs via `color-scheme:change` and `storage` events.

#### Documentation

- Added component and controller docs for color scheme usage.
- Updated theming docs, README catalog tables and IDE metadata.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.55.0...0.56.0

## 0.55.0 - 2026-07-18

### Dropdown semantic API and InputGroup form composition

Adds semantic Dropdown primitives and a new InputGroup composition component, while refining Nova form control density and overlay layering.

#### Dropdown

- Adds `<hw:dropdown.trigger>` and `<hw:dropdown.content>` subcomponents for explicit trigger/content composition.
- Supports `as-child` trigger composition for Sidebar and custom trigger markup without nested buttons.
- Moves placement configuration to dropdown content and adds mobile/collapsed placement overrides.
- Keeps trigger and content open state synchronized for custom trigger components.

Docs: `docs/components/dropdown.md` and `docs/controllers/dropdown.md`

#### InputGroup and form controls

- Adds `<hw:input-group>` and `<hw:input-group.addon>` for composing icons, prefixes, suffixes, shortcuts and custom controls around existing inputs.
- Reworks MultiSelect search to use InputGroup, including a `searchIcon` slot override.
- Removes generic Input icon props and keeps app-specific icons outside the package icon subset.
- Compacts Nova one-line form controls while preserving textarea and file input sizing.

Docs: `docs/components/input-group.md`, `docs/components/input.md` and `docs/components/multi-select.md`

#### Visual and interaction refinements

- Aligns CheckboxGroup spacing and disabled state behavior with RadioGroup.
- Smooths Sidebar icon-collapse labels and inset layout sizing.
- Keeps Sonner flash toasts above modal, drawer and sheet overlays via the browser top layer.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.54.0...0.55.0

## 0.54.0 - 2026-07-17

### Modal API and overlay composition

Redesigns Modal around semantic subcomponents and tightens nested overlay coordination across modal, dialog, drawer, sidebar and floating controls.

#### Modal

- Adds semantic `<hw:modal.trigger>`, `<hw:modal.content>` and `<hw:modal.close>` primitives.
- Aligns Modal Nova styling with the compact overlay scale.
- Removes the reopen delay guard and public prevent-reopen-delay API so backdrops can reopen cleanly.
- Updates Modal docs, IDE metadata and PHP/JS/browser coverage.

Docs: `docs/components/modal.md` and `docs/controllers/modal.md`

#### Overlay stack

- Adds shared top-layer and overlay stack helpers for nested overlays.
- Coordinates Modal, Alert Dialog, Drawer, Sidebar, Dropdown, Popover, Hover Card and MultiSelect Escape handling so nested layers close one at a time.
- Renames Alert Dialog message/body API to description/content and updates docs/tests.
- Documents nested overlay recipes and covers the regressions in browser tests.

Docs: `docs/recipes/nested-overlays.md`

#### Form controls

- Adds `icon-start` and `icon-end` props to the Input component.
- Uses generic search icon support in MultiSelect search.
- Renders a mixed select-all state for partial MultiSelect selections.

Docs: `docs/components/input.md` and `docs/components/multi-select.md`

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.53.0...0.54.0

## 0.53.0 - 2026-07-17

### Tooltip migration to Floating UI

Migrates Tooltip from Tippy to the shared Floating UI positioning stack and aligns the visual treatment with the shadcn tooltip pattern.

#### Tooltip

- Replaces `tippy.js` with `@floating-ui/dom`.
- Adds hover/focus lifecycle, Escape/click dismissal, Turbo cache cleanup, and `aria-describedby` management.
- Uses the final `side`/`align` positioning API.
- Adds Nova preset styles for tooltip content, caret, animation, and contextual `<kbd>` rendering.
- Updates docs, package metadata, registry entries, and tests.

Docs: `docs/controllers/tooltip.md`

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.52.0...0.53.0

## 0.52.0 - 2026-07-17

### Sticky primitive and Navbar component

Adds layout primitives for sticky surfaces and accessible link navigation.

#### Sticky

- Adds `<hw:sticky>` for top and bottom sticky surfaces.
- Supports `side`, `offset`, `surface`, and `as`.
- Useful for persistent section navigation and bottom action bars.

Docs: `docs/components/sticky.md`

#### Navbar

- Adds `<hw:navbar>` and `<hw:navbar.item>` for real link/button navigation.
- Supports horizontal and vertical orientation, `line` and `pills` variants, manual `current` state, disabled semantics, and native `aria-current="page"` for current links.
- Adds sticky navbar sugar via `sticky`, `sticky-side`, and `sticky-offset`.

Docs: `docs/components/navbar.md`

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.51.0...0.52.0

## 0.51.0 - 2026-07-17

### Hover Card

Adds a new Hover Card overlay for fast hover/focus previews.

#### Hover Card component and controller

- Adds `<hw:hover-card>` with `trigger` and `content` subcomponents for contextual previews.
- Supports hover/focus opening, delayed close, Escape dismissal with focus return and `turbo:before-cache` cleanup.
- Supports placement controls: `side`, `align`, `side-offset`, `align-offset`, `strategy`, `flip` and `shift`.
- Exposes trigger `as`, `variant` and `size` props so link-style triggers work without manual Stimulus wiring.
- See `docs/components/hover-card.md` and `docs/controllers/hover-card.md` for usage examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.50.0...0.51.0

## 0.50.0 - 2026-07-16

### Popover component with Floating UI positioning

Adds anchored rich-content popovers with composed Blade primitives and a dedicated Stimulus controller.

#### Popover

- Added `<hw:popover>` with explicit `popover.trigger`, `popover.content`, `popover.header`, `popover.title`, and `popover.description` primitives.
- Added the `popover` Stimulus controller with Floating UI positioning, outside-click and Escape dismissal, focus return, Turbo cache cleanup, and incomplete-markup guards.
- Added Nova preset styling, docs, catalog registration, IDE metadata, and PHP/JS/browser coverage.
- Defaults Popover positioning to `strategy="fixed"`, with `strategy="absolute"` available as an explicit override.

See `docs/components/popover.md` and `docs/controllers/popover.md` for examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.49.0...0.50.0

## 0.49.0 - 2026-07-16

### Radio Group component

Adds a composable radio group primitive for native single-value form selection with rich item content.

#### Radio Group

- Added `<hw:radio-group>` with options-array rendering, selected/old input restore, validation ARIA, and auto-submit wiring.
- Added `<hw:radio-group.item>` for rich composed radio item content.
- Registered Radio Group in aliases, catalog, IDE metadata, README and component docs, with Nova preset styling hooks and component coverage.

See `docs/components/radio-group.md` for examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.48.0...0.49.0

## 0.47.0 - 2026-07-16

### Conditional Fields form DX

Improves conditional-field setup by letting forms own the controller wiring and initial state.

#### Conditional Fields

- Added `<hw:form conditional-fields>` to mount the `conditional-fields` controller declaratively.
- Added form-level `state` so nested conditional fields can resolve initial server-rendered visibility.
- Added the `when="field=value"` string grammar with `|` OR, space-separated AND, and `:checked` / `:unchecked` support.
- Kept array-based `:when` rules for complex conditions and updated docs/catalog dependencies.

See `docs/components/form.md` and `docs/components/conditional-field.md` for examples.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.46.0...0.47.0

## 0.45.0 - 2026-07-16

### Frame-or-page contextual slots

Adds contextual rendering slots to `<hw:frame-or-page>` so one route can serve a focused Turbo Frame payload and a richer standalone page.

#### Frame-or-page

- Adds `frameContent` and `pageContent` slots with default-slot fallback for backwards compatibility.
- Uses `<hw:frame>` for frame responses so aliases like `lazy`, `advance`, `replace`, `poll` and `view-transition` work consistently.
- Resolves simple layout names like `layout="dashboard"` to `layouts.dashboard` when safe, without overriding existing aliases.
- Updates the component docs and frame-or-page recipe with package components and modal/page examples.

#### Auto-submit ergonomics

- Standardizes auto-submit wiring across form controls.
- Adds shared auto-submit support so components can expose consistent `auto-submit` and delay behavior.
- Updates form control docs and controller coverage for the new ergonomics.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.44.0...0.45.0

## 0.44.0 - 2026-07-16

### Toggle and frame ergonomics

Adds an accessible Toggle primitive, a first-class Turbo Frame component and tighter Turbo frame integrations for forms, polling and transitions.

#### Toggle

- Adds `<hw:toggle>` with `aria-pressed`, `data-state`, optional hidden input submission and `auto-submit` support.
- Adds the `toggle` Stimulus controller for pressed-state synchronization and bubbling `change` events.
- Adds Nova preset styling with stable default hover text color and named `group/toggle` icon-state ergonomics.
- Docs: `docs/components/toggle.md` and `docs/controllers/toggle.md`.

#### Turbo frames

- Adds `<hw:form frame="...">` as ergonomic sugar for `data-turbo-frame`.
- Adds `<hw:frame>` for rendering `<turbo-frame>` with `src`, `loading`, `target`, `autoscroll`, `lazy`, `advance`, `replace`, `poll` and `view-transition` helpers.
- Keeps native frame attributes explicit and predictable when aliases overlap.
- Docs: `docs/components/form.md` and `docs/components/frame.md`.

#### Turbo controller integrations

- Improves `turbo--polling` so it can be mounted directly on a `<turbo-frame>` and schedule repeated reloads.
- Documents the real Turbo controller identifiers for polling, view transitions, frame src and progress.
- Docs: `docs/controllers/turbo/polling.md`, `docs/controllers/turbo/view-transition.md`, `docs/controllers/turbo/frame-src.md` and `docs/controllers/turbo/progress.md`.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.43.0...0.44.0

## 0.43.0 - 2026-07-15

### Accordion, Checkbox and Switch primitives

Adds native Accordion, Checkbox and Switch form primitives, plus richer checkbox composition and overlay focus handling.

#### Accordion

- Adds composable `<hw:accordion>` primitives backed by native `<details>` / `<summary>`.
- Includes the `accordion` Stimulus controller for single, multiple, disabled and `accordion:change` behavior.
- Styles Accordion in the Nova preset with native `::details-content` animation.
- Docs: `docs/components/accordion.md` and `docs/controllers/accordion.md`.

#### Checkbox, Switch and Checkbox Group

- Adds standalone `<hw:checkbox>` and `<hw:switch>` components with native form behavior, Laravel `old()` restore, validation/ARIA wiring and `unchecked-value`.
- Adds the `checkbox` Stimulus controller for native indeterminate state.
- Evolves `<hw:checkbox-group>` with rich `<hw:checkbox-group.item>` composition while preserving the existing `options` API.
- Adds field disabled/invalid state hooks and Switch choice-card styling.
- Docs: `docs/components/checkbox.md`, `docs/components/switch.md`, `docs/components/checkbox-group.md` and `docs/controllers/checkbox.md`.

#### Overlay focus

- Fixes Modal focus trapping so enabled native Accordion summaries are treated as focusable controls.
- Adds browser coverage for Accordion summaries inside Modal focus traps.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.42.0...0.43.0

## 0.42.0 - 2026-07-15

### Floating UI MultiSelect and overlay refinements

Adds the new MultiSelect component and improves overlay behavior, Dropdown positioning, Spinner styling and documentation consistency.

#### MultiSelect

Add `<hw:multi-select>` and the `multi-select` controller with search, select-all, max selection, native form submission and Floating UI positioning. See `docs/components/multi-select.md` and `docs/controllers/multi-select.md`.

#### Dropdown

Add Floating UI positioning controls to Dropdown and keep it a disclosure-style popup with native Tab navigation and scoped Escape handling inside overlays. See `docs/components/dropdown.md` and `docs/controllers/dropdown.md`.

#### Overlay and theming fixes

Fix focus trap Tab entry when dynamic overlay content appears after opening, and improve dark-mode contrast for native date/time picker icons.

#### Component and catalog consistency

Align Spinner with the Nova reference, normalize internal package component tags to `<x-hw::...>`, keep public docs on `<hw:...>` syntax, and sync README catalog tables with the registry.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.41.0...0.42.0

## 0.41.0 - 2026-07-14

### Avatar, Aspect Ratio, Progress and Marker components

Adds four static Blade display primitives that extend the Nova preset with semantic-token styling and IDE metadata.

#### Avatar

Render user avatars with image/fallback, badge, square/circle shapes, sizes, grouped stacks and overflow counts. See `docs/components/avatar.md`.

#### Aspect Ratio

Preserve media boxes with a CSS-only `aspect-ratio` wrapper, including intrinsic `width` and `height` props. See `docs/components/aspect-ratio.md`.

#### Progress

Render accessible server-side progress bars with `value`, `max`, label/value and composable track/indicator slots. See `docs/components/progress.md`.

#### Marker

Render lightweight timeline/activity/list markers with icon/content slots and `default`, `separator` and `border` variants. See `docs/components/marker.md`.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.40.0...0.41.0

## 0.40.0 - 2026-07-13

### Dropdown subcomponents and Pagination

This release adds composed Dropdown menu primitives and a new Pagination component with Laravel paginator, Turbo and accessibility support.

#### Pagination

- Add a composed Pagination component with content, item, link, previous, next and ellipsis subcomponents.
- Render Laravel length-aware, simple and cursor paginators automatically with full, numbers, controls and icons display modes.
- Support Turbo Frame overrides, Turbo Stream pagination links, customizable previous/next aria labels and icon-only controls.
- Add Nova preset styles, catalog and alias registration, IDE metadata, documentation and component tests.
- See `docs/components/pagination.md`.

#### Dropdown menu subcomponents

- Add Dropdown group, label, item, separator and shortcut subcomponents for structured menu content.
- Support link and button items, destructive and disabled states, inset spacing and shortcut/helper text.
- Add Nova preset slot styles, catalog aliases, IDE metadata, documentation and tests while preserving the existing trigger API.
- See `docs/components/dropdown.md`.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.39.0...0.40.0

## 0.39.0 - 2026-07-13

### Breadcrumb

This release adds a semantic Breadcrumb component and refines Sheet and Drawer overlay transitions.

#### Breadcrumb

- Add a Breadcrumb component with root, list, item, link, page, separator and ellipsis subcomponents.
- Add an `items` shortcut for array-driven breadcrumbs with automatic separators, current-page handling and ellipsis entries.
- Add Nova preset styles, catalog and alias registration, IDE metadata, documentation and component tests.
- See `docs/components/breadcrumb.md`.

#### Sheet and Drawer refinements

- Fix Sheet close button styling.
- Refine Sheet and Drawer transition classes and dynamic frame handling so close animations and stream-driven updates stay consistent.
- See `docs/components/sheet.md`, `docs/components/drawer.md`, `docs/controllers/sheet.md` and `docs/controllers/drawer.md`.

#### Maintenance

- Update frontend dependencies.
- Refresh local contributor agent guidance.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.38.0...0.39.0

## 0.38.0 - 2026-07-11

### Drawer, Sheet and Sidebar

This release adds server-driven overlay and navigation components, plus richer rich-text toolbar presets.

#### Drawer and Sheet

- Add Drawer and Sheet components with trigger, close, content and semantic subcomponents.
- Add dynamic Turbo Frame hosts with loading templates, frame-driven opening and stream-driven close handling.
- Share frame overlay behavior with Modal so server-driven overlays handle loading, empty streams and refresh streams consistently.
- See `docs/components/drawer.md`, `docs/components/sheet.md`, `docs/controllers/drawer.md` and `docs/controllers/sheet.md`.

#### Sidebar

- Add the Sidebar component family, including provider, trigger, rail, menu parts, input, skeleton and `sidebar.brand`.
- Support desktop collapse persistence, separate mobile drawer state and mobile navigation close animations.
- Add conditional Tooltip support with `enabledWhen` for icon-collapsed Sidebar menus.
- See `docs/components/sidebar.md`, `docs/controllers/sidebar.md` and `docs/controllers/tooltip.md`.

#### Rich Text toolbar presets

- Add packaged rich-text toolbar presets with a compact default `basic` toolbar and a `classic` toolbar for the broader button set.
- Support custom packaged toolbar button lists through string or array values.
- Add the toolbar icons required by the bundled presets.
- See `docs/components/rich-text.md`.

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.37.0...0.38.0

## 0.37.0 - 2026-07-08

### Tabs component and Stimulus attribute merging

Adds first-class Tabs Blade primitives and standardizes how controller-backed components compose Stimulus attributes.

#### Tabs component

- Added `<hw:tabs>` with list, trigger, and panel primitives backed by the existing `tabs` controller

#### Stimulus attribute composition

- Added consistent `stimulus` prop support across controller-backed Blade components
- Standardized merging for `data-controller`, `data-action`, and `data-*-target`
- Preserved custom controller extensibility while protecting component-owned values
- Fixed JSON-valued Stimulus attributes for Carousel, Input masks, Chart, Map, FileUpload, and Toaster

#### Maintenance

- Bumped frontend dependencies

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.36.1...0.37.0

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
