# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

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
