# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components.

## Package Structure

- `src/` — PHP source (commands, components, service provider)
- `src/Components/Concerns/` — shared component traits (e.g. `StripsNullProps` for omitting null props from rendered attributes)
- `src/Support/` — framework-agnostic helpers (`FieldKey` for id/errorKey derivation, `MaskPresets`, `ControllerImports` for resolving a controller's shared JS deps, doc search/render/paging, installer)
- `src/Registry/catalog.php` — single source of truth mapping every component and controller to its class/source, view, docs, category and dependencies
- `resources/js/controllers/` — Stimulus controllers shipped with the package (published to user's app)
- `resources/views/component-views/` — Blade component views
- `stubs/resources/` — Scaffolding files copied by `hotwire:install`
- `config/hotwire.php` — Package configuration (component prefix)
- `tests/` — Pest PHP tests with Orchestra Testbench
- `tests/Controllers/` — Bun JS tests for Stimulus controllers
- `docs/` — Documentation

## Artisan Commands

| Command                   | Description                                                       |
|---------------------------|-------------------------------------------------------------------|
| `hotwire:install`         | Scaffold JS/CSS setup, add npm deps to package.json               |
| `hotwire:make-controller` | Create a new Stimulus controller (interactive scaffolding)        |
| `hotwire:controllers`     | Publish package Stimulus controllers to the app (`--outdated` to update only published+changed ones) |
| `hotwire:components`      | List available Blade components and their controller dependencies |
| `hotwire:check`           | Verify required controllers (and their shared deps) are published (CI-friendly) |
| `hotwire:docs`            | Browse and read controller/component docs in the terminal         |

## Conventions

### Stimulus Controllers

- **Flat layout** at the top level. File naming: `{name}_controller.{js|ts}` (snake_case).
- **Identifier** matches the file name converted to kebab-case: `auto_submit_controller.js` →
  `data-controller="auto-submit"`.
- **Substrate folders** (`turbo/`, `optimistic/`, `dev/`) group controllers tied to a specific technical layer.
  Files inside keep Stimulus' `--` separator in the identifier: `turbo/progress_controller.js` →
  `data-controller="turbo--progress"`.
- **No UI-role folders** (no `form/`, `modal/`, `utils/`, `lib/`, `media/`, `notification/`). Names themselves
  describe intent — prefer compound names (`copy-to-clipboard`, `lazy-image`, `input-mask`) over namespace buckets.
- Loaded via `@emaia/stimulus-dynamic-loader` with Vite's `import.meta.glob`.
- **Controllers must be mutually compatible.** Blade components stack several controllers on the same element
  (`<x-hwc::form>` → `auto-submit unsaved-changes clean-query-params`; `<x-hwc::file>` →
  `file-preserve reset-files` plus the user's own `data-controller`). A controller must therefore never assume it
  owns the element exclusively:
  - Scope DOM reads/writes to `this.element` (and prefer `this.targets`); don't clobber attributes other controllers
    set, especially shared `data-controller`.
  - Multiple controllers commonly listen to the same Turbo events (`turbo:submit-end`, `turbo:morph`,
    `turbo:before-render`/`turbo:render`). Coordinate on shared semantics (e.g. submit `success`) and make each
    handler idempotent and order-independent rather than mutating state another controller relies on.
  - Clean up every listener and timer in `disconnect()` so re-renders/morphs don't leave duplicates behind.
  - When a controller is activated by a component prop, expose it as an explicit prop and let the component merge the
    identifier — see the Blade Components `data-*` filtering rule below.

### Blade Components

- Registered with configurable prefix (default: `hwc`)
- Usage: `<x-hwc::modal>`, `<x-hwc::confirm-dialog>`, `<x-hwc::flash-message>`, `<x-hwc::loader>`, `<x-hwc::scroll-progress>`, `<x-hwc::timeago>`
- Components that encapsulate a Stimulus controller merge user-provided `data-controller` with internal controllers
  on the element. User-provided `data-{identifier}-*` for internal controllers active via props is filtered to prevent
  conflicts; for other controllers these pass through freely. Expose supported controller configuration as explicit
  Blade props instead of relying on user-provided `data-*` attributes.
- Components that derive field ids/error keys (Input, File, Select, etc.) use `Support\FieldKey`; components that omit
  null props from the rendered tag use the `StripsNullProps` concern. Reuse these instead of reimplementing.

### Registry

`src/Registry/catalog.php` is the single source of truth for everything the package ships — `hotwire:components`,
`hotwire:controllers`, `hotwire:check` and `hotwire:docs` all read from it. **Any new component or controller must be
registered here**, or the commands won't see it.

- `components` entries: `class`, `view`, `docs`, `category`, `description`, and `controllers` (the list of Stimulus
  identifiers the component depends on — keep it in sync with what the Blade view actually mounts, since
  `hotwire:check` verifies these are published).
- `controllers` entries: `source` (path to the `.js`/`.ts` file), `docs`, `category`, `description`, and optional
  `npm` (a `package => version` map for third-party deps like `maska`, `tippy.js`, `date-fns`, `@emaia/sonner`).
- Identifiers follow the Stimulus naming rules above — substrate-folder controllers use the `--` separator
  (`turbo--progress`, `optimistic--form`, `dev--log`).
- Every registered component/controller should ship a matching doc file under `docs/` at the path given in the entry.

### Turbo

- Provided by `emaia/laravel-hotwire-turbo` dependency
- Fluent stream builder: `turbo_stream()->append(...)` (the builder is `Responsable` — return it directly from the
  controller; use `->withResponse(...)` when you need a custom status or headers)
- Request detection: `request()->wantsTurboStream()`, `request()->wasFromTurboFrame()`
- DOM helpers: `dom_id($model)`, `dom_class($model)`

## Release Workflow

### Commits

- **Subject**: Imperative mood, no period at end (e.g. `Add progress bar and counter targets to carousel`)
- **Body**: Bullet points prefixed with `-`, each describing a specific change
- **PR reference**: Appended as `(#N)` in the subject when applicable
- Always signed (GPG)

### Pull Requests

- Push feature branch, open PR on GitHub
- Branch naming: descriptive kebab-case (e.g. `carousel-extras`, `confirm-dialog`)
- PR title matches commit subject convention; body summarizes changes
- Review required; merge manually (do not squash-merge from the CLI)
- Remote merge (GitHub UI) squashes the branch into a single commit on `main`

### Tag and Release

- Tags are created on `main` after the PR is merged remotely
- Versioning follows `0.X.Y` semver:
  - Patch (`0.16.1`): bugfixes
  - Minor (`0.17.0`): new features
- Annotated tag: `git tag -a 0.17.0 -m "0.17.0"`
- Release created via `gh release create 0.17.0 --title "0.17.0" --notes-file /tmp/release-notes.md`
- Release notes format (following the `0.16.0` template):
  - Markdown title with feature name (e.g. `## Carousel progress bar and slide counter`)
  - One-sentence summary
  - Section per feature with Blade code block showing usage
  - `**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/<prev>...<version>` at the end
- CHANGELOG.md is updated automatically by the release workflow; do not edit manually

## Development

```bash
composer test          # Run Pest tests
composer analyse       # Run PHPStan
bun test               # Run JS unit tests (Bun + happy-dom)
bun run test:browser   # Run browser tests (Playwright)
composer format        # Run Pint code formatter
```

### Testing (TDD)

Follow test-driven development: write tests first, then implement.

There are three test suites:

- **PHP** (`composer test`) — Pest + Orchestra Testbench. Covers commands, components, registry.
- **JS** (`bun test`) — Bun test runner + happy-dom. Covers Stimulus controllers in `tests/Controllers/*.test.js`.
- **Browser JS** (`bun run test:browser`) — Playwright. Covers browser-dependent Stimulus behavior in
  `tests/Browser/*.pw.js`.

TDD flow:

1. **Write the failing test** in the appropriate suite
2. **Run only that test** to confirm it fails:
   - PHP: `vendor/bin/pest --filter='test name'`
   - JS: `bun test tests/Controllers/<name>_controller.test.js`
   - Browser JS: `bun run test:browser -- tests/Browser/<name>.pw.js`
3. **Implement** the minimum code to make it pass
4. **Run the test again** to confirm it passes
5. **Repeat** for the next behavior

PHP conventions:

- Test files mirror `src/` structure: `src/Commands/FooCommand.php` → `tests/Commands/FooCommandTest.php`
- Use Pest syntax (`it()`, `test()`, `expect()`) — no PHPUnit classes
- Group related tests with comment headers: `// --- Section name ---`
- Use `beforeEach`/`afterEach` for shared setup and cleanup (temp files, directories)
- For artisan commands: use `$this->artisan('command')->assertSuccessful()` and `expectsQuestion`/`expectsChoice`/
  `expectsOutput` for interactive flows
- Always run `composer test` at the end to ensure nothing else broke

JS conventions:

- One test file per controller: `tests/Controllers/<name>_controller.test.js`
- Use `mountController` from `resources/js/helpers/test_stimulus.js` to set up the DOM and Stimulus
- Always call `mounted?.cleanup()` in `afterEach`
- Always run `bun test` at the end to ensure nothing else broke
- Use Playwright (`tests/Browser/*.pw.js`) for controller behavior that depends on real browser semantics:
  `MutationObserver`, focus, `requestAnimationFrame`, layout, Turbo frame-like DOM changes or other complex event timing.
- Keep Playwright tests focused and few; prefer `bun test` for deterministic controller unit behavior.
- Run `bun run test:browser` after changing browser-dependent behavior.

## Dependencies

- PHP: `emaia/laravel-hotwire-turbo`, `spatie/laravel-package-tools`
- JS: `@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-dynamic-loader`
- Optional JS: `maska`, `tippy.js`, `@emaia/sonner` (for specific controllers)
