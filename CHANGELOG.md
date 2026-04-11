# Changelog

All notable changes to `laravel-hotwire` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Updated documentation and added agent skills (`.claude/skills/`) for Turbo and Stimulus

---

## [0.6.7] - 2026-04-10

### Added

- `hotwire:make-controller` command — interactive scaffolding to generate Stimulus controllers in `resources/js/controllers/`
  - Choose JS or TypeScript
  - Optionally include targets, values, and classes with detail prompts
  - TypeScript generates typed `declare readonly` declarations for targets
  - Validates `namespace/name` format and converts hyphens to underscores in filenames
  - Shows Stimulus identifier and `data-controller` usage hint after creation

---

## [0.6.6] - 2026-04-10

### Added

- `hotwire:install` command — scaffolds the Hotwire JS/CSS setup in the Laravel application
  - Copies JS entry points, Stimulus loader, Turbo imports, and CSS custom variants to `resources/`
  - Reads npm dependencies from the package's own `package.json` to keep the list up to date
  - Core deps (`@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-dynamic-loader`) always added
  - Optional deps (`maska`, `tippy.js`, `@emaia/sonner`) offered via interactive multiselect
  - Detects package manager (bun, pnpm, yarn, npm) from lock files
  - Supports `--force`, `--only=js`, `--only=css`
  - Skips identical files, prompts before overwriting modified ones

---

## [0.6.5] - 2026-04-10

### Fixed

- Test suite fixes for `hotwire:check` command

---

## [0.6.4] - 2026-04-10

### Added

- `hotwire:check` command — scans `resources/views` for used Hotwire components and checks whether their required Stimulus controllers are published
  - Reports missing and outdated controllers with color-coded output
  - Exits with code `1` when attention is needed (CI-friendly)
  - `--fix` flag to auto-publish missing/outdated controllers without prompting
  - `--path` option to scan a custom views directory

---

## [0.6.3] - 2026-04-10

### Added

- `hotwire:components` command — lists all registered Blade components with their Stimulus controller dependencies and publish status (up to date / outdated / not published)
- `HasStimulusControllers` interface with `stimulusControllers(): array` — implemented on Modal, ConfirmDialog, and FlashMessage components
- `COMPONENTS` registry constant in `LaravelHotwireServiceProvider` — used for both Blade registration and the new command

---

## [0.6.2] - 2026-04-10

### Added

- Confirm Dialog component (`<x-hwc-confirm>`) with `dialog--confirm` Stimulus controller
  - Supports `lock-scroll` and `close-on-click-outside` options
  - Full accessibility support

---

## [0.6.1] - 2026-04-10

### Changed

- `hotwire:controllers` list view now includes a status column showing whether each controller is published, outdated, or missing

---

## [0.6.0] - 2026-04-10

### Changed

- Renamed PSR namespace from `LaravelHotwireComponents` to `LaravelHotwire`
- Renamed service provider to `LaravelHotwireServiceProvider`

---

## [0.5.0] - 2026-04-10

### Added

- Added `emaia/laravel-hotwire-turbo` as a dependency, providing full Turbo integration (Streams, Frames, `dom_id()`, `wantsTurboStream()`, `InteractsWithTurbo`, etc.)

### Changed

- Internal references renamed from `hwc` to `hotwire` (component prefix default remains `hwc`)

---

## [0.4.0] - 2026-04-10

### Fixed

- Various bugfixes in `hotwire:controllers` command
- Readme and config improvements

---

## [0.3.1] - 2026-04-09

### Fixed

- Bugfix in `hotwire:controllers` publish command

---

## [0.3.0] - 2026-04-09

### Added

- Full documentation for all controllers and components under `docs/`
  - Covers: dialog, form, frame, dev, lib, media, notification controllers
  - Covers: flash-message, loader, modal components

### Changed

- Updated controller name conventions

---

## [0.2.0] - 2026-04-09

### Added

- TypeScript controller support in `hotwire:controllers`

### Changed

- Code refactor of `hotwire:controllers` command
- Improved interactive list menu

---

## [0.1.0] - 2026-04-09

### Added

- Initial release
- Blade components: `<x-hwc-modal>`, `<x-hwc-flash-message>`, `<x-hwc-loader>`
- `hotwire:controllers` command — publishes Stimulus controllers to `resources/js/controllers/`
  - Interactive selection, by namespace, by specific controller, `--all`, `--list`, `--force`
  - Skips identical files, prompts before overwriting modified ones
- Stimulus controllers: form, frame, dialog, dev, lib, media, notification namespaces
- Pest test suite with Orchestra Testbench

[Unreleased]: https://github.com/emaia/laravel-hotwire/compare/0.6.7...HEAD
[0.6.7]: https://github.com/emaia/laravel-hotwire/compare/0.6.6...0.6.7
[0.6.6]: https://github.com/emaia/laravel-hotwire/compare/0.6.5...0.6.6
[0.6.5]: https://github.com/emaia/laravel-hotwire/compare/0.6.4...0.6.5
[0.6.4]: https://github.com/emaia/laravel-hotwire/compare/0.6.3...0.6.4
[0.6.3]: https://github.com/emaia/laravel-hotwire/compare/0.6.2...0.6.3
[0.6.2]: https://github.com/emaia/laravel-hotwire/compare/0.6.1...0.6.2
[0.6.1]: https://github.com/emaia/laravel-hotwire/compare/0.6.0...0.6.1
[0.6.0]: https://github.com/emaia/laravel-hotwire/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/emaia/laravel-hotwire/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/emaia/laravel-hotwire/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/emaia/laravel-hotwire/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/emaia/laravel-hotwire/compare/0.2.0...0.3.0
[0.2.0]: https://github.com/emaia/laravel-hotwire/compare/0.1.0...0.2.0
[0.1.0]: https://github.com/emaia/laravel-hotwire/releases/tag/0.1.0
