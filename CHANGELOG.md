# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

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
