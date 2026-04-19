# Changelog

All notable changes to `laravel-hotwire-components` will be documented in this file.

## Unreleased

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

## 0.7.5 - 2026-04-17

### What's Changed

* feat: add optimistic UI primitives (component + form/link/dispatch controllers)  by @emaia in https://github.com/emaia/laravel-hotwire/pull/2

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.4...0.7.5

## 0.7.4 - 2026-04-13

**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/0.7.3...0.7.4
