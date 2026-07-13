# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components.

## Package Structure

- `src/` — PHP source (commands, components, service provider)
- `src/Components/Concerns/` — shared component traits (e.g. `StripsNullProps` for omitting null props from rendered
  attributes)
- `src/Support/` — framework-agnostic helpers (`FieldKey` for id/errorKey derivation, `MaskPresets`, `ControllerImports`
  for resolving a controller's shared JS deps, doc search/render/paging, installer)
- `src/Registry/catalog.php` — single source of truth mapping every component and controller to its class/source, view,
  docs, category and dependencies
- `resources/js/controllers/` — Stimulus controllers shipped with the package (published to user's app)
- `resources/views/component-views/` — Blade component views
- `stubs/resources/` — Scaffolding files copied by `hotwire:install`
- `config/hotwire.php` — Package configuration (component prefix)
- `tests/` — Pest PHP tests with Orchestra Testbench
- `tests/Controllers/` — Bun JS tests for Stimulus controllers
- `docs/` — Documentation

## Artisan Commands

| Command                   | Description                                                                                          |
|---------------------------|------------------------------------------------------------------------------------------------------|
| `hotwire:install`         | Scaffold JS/CSS setup, add npm deps to package.json                                                  |
| `hotwire:make-controller` | Create a new Stimulus controller (interactive scaffolding)                                           |
| `hotwire:controllers`     | Publish package Stimulus controllers to the app for customization (`--outdated` to update only published+changed ones) |
| `hotwire:components`      | List available Blade components and their controller dependencies                                    |
| `hotwire:check`           | Verify required npm dependencies are installed and report outdated/diverged published controllers (CI-friendly)                      |
| `hotwire:docs`            | Browse and read controller/component docs in the terminal                                            |

## Conventions

### Stimulus Controllers

- **Flat layout** at the top level. File naming: `{name}_controller.js` (snake_case). The user can generate `.ts` controllers via `hotwire:make-controller --ts`; the package ships `.js` only.
- **Identifier** matches the file name converted to kebab-case: `auto_submit_controller.js` →
  `data-controller="auto-submit"`.
- **Substrate folders** (`turbo/`, `optimistic/`, `dev/`) group controllers tied to a specific technical layer.
  Files inside keep Stimulus' `--` separator in the identifier: `turbo/progress_controller.js` →
  `data-controller="turbo--progress"`.
- **No UI-role folders** (no `form/`, `modal/`, `utils/`, `lib/`, `media/`, `notification/`). Names themselves
  describe intent — prefer compound names (`copy-to-clipboard`, `lazy-image`, `input-mask`) over namespace buckets.
- **Internal helpers** prefixed with `_` (e.g. `_focus_trap.js`, `_transition.js`, `_form_errors.js`) are shared
  utility modules imported by controllers. They are **not** Stimulus controllers and are never registered via
  `data-controller`.
- **Package marker.** Every controller, helper, and shared dependency shipped by the package begins with
  `// @hotwire-package` on its first non-empty line (or `/* @hotwire-package */` for `.css`). The marker lets
  `hotwire:controllers` (with `--force` or `--outdated --force`) and `hotwire:check --fix` distinguish files that
  came from the package from files written by the user — without the marker, those commands refuse to overwrite.
  `hotwire:make-controller` deliberately does **not** emit the marker, so generated user controllers stay protected.
  Drop the marker when reading `Support\PackageMarker`.
- Loaded via `@emaia/stimulus-lazy-loader` with Vite's `import.meta.glob`.
- **Controllers must be mutually compatible.** Blade components stack several controllers on the same element
  (`<hw:form>` → `auto-submit unsaved-changes clean-query-params`; `<hw:file>` →
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

- Registered with configurable prefix (default: `hw`)
- See all available components with `php artisan hotwire:components`
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

## Collaboration Rules

- Do not expose local absolute paths in user-facing messages.
- Always ask the user to confirm commit, PR, tag and release messages before pushing, publishing or creating them.

## Release Workflow

### Commits

- **Subject**: Imperative mood, no period at end (e.g. `Add progress bar and counter targets to carousel`)
- **Body**: Bullet points prefixed with `-`, each describing a specific change
- **PR reference**: Appended as `(#N)` in the subject when applicable
- Always signed (GPG)
- Ask to confirm the message is correct before pushing

### Pull Requests

- Push feature branch, open PR on GitHub
- Branch naming: descriptive kebab-case (e.g. `radio-group`, `alert-dialog`)
- PR title matches commit subject convention; body summarizes changes
- Review required; merge from the GitHub UI (not the CLI)
- Remote merge (GitHub UI):
    - **Default: squash-merge** — for PRs where iterative review/fixup commits should collapse into a single clean commit reflecting the deliverable
    - **Merge commit (no squash)** — for PRs that bundle multiple isolated changes that each deserve their own commit in history
- Ask to confirm the message is correct before pushing
- **PR body template** — `## Summary` (bullet points) + `## Test plan`. The Test plan combines automated checks
  with a manual smoke section covering what tests can't verify (visual, browser-specific behavior, real
  interaction). Each item is a checkbox so the reviewer can tick it as they go.

  Example:

  ```markdown
  ## Test plan

  - [ ] `composer test` — N/N passing
  - [ ] `bun run test` — N/N passing
  - [ ] Manual smoke: <render this component in a fresh app, click X, expect Y; tweak prop Z, expect Y'>
  - [ ] Manual smoke: <one scenario per non-trivial code path — error path, edge case, prop variant>
  - [ ] Manual smoke: <accessibility / keyboard / screen reader if relevant>
  ```

  Skip the manual lines that don't apply (a pure internal refactor with full test coverage may legitimately
  have only the automated lines). For Stimulus controllers and Blade components, default to including manual
  smokes — DOM observability, visual rendering, and event flow are exactly what unit tests can't fully cover.

### Tag and Release

- Tags are created on `main` after the PR is merged remotely
- Versioning follows `X.Y.Z` semver:
    - Patch (`X.Y.Z+1`): bugfixes
    - Minor (`X.Y+1.Z`): new features
- Annotated tag: `git tag -a X.Y.Z -m "X.Y.Z"`
- Release created via `gh release create X.Y.Z --title "X.Y.Z" --notes-file /tmp/release-notes.md`
- Release notes format (following the `X.Y.Z` template):
    - Markdown title with feature name (e.g. `## Carousel progress bar and slide counter`)
    - One-sentence summary
    - Section per feature and referer docs for examples
    - `**Full Changelog**: https://github.com/emaia/laravel-hotwire/compare/<prev>...<version>` at the end
- CHANGELOG.md is updated automatically by the release workflow; do not edit manually
- Ask to confirm the message is correct before pushing


## Development

```bash
composer test          # Run Pest tests
composer analyse       # Run PHPStan
bun run test           # Run JS unit tests (Bun + happy-dom). Use `bun run test`, not `bun test` — the npm script wires up --isolate --parallel so mocks don't leak across files and files run concurrently
bun run test:browser   # Run browser tests (Playwright)
composer format        # Run Pint code formatter
```

### Testing (TDD)

Follow test-driven development: write tests first, then implement.

There are three test suites:

- **PHP** (`composer test`) — Pest + Orchestra Testbench. Covers commands, components, registry.
- **JS** (`bun run test`) — Bun test runner + happy-dom. Covers Stimulus controllers in `tests/Controllers/*.test.js`.
- **Browser JS** (`bun run test:browser`) — Playwright. Covers browser-dependent Stimulus behavior in
  `tests/Browser/*.pw.js`.

TDD flow:

1. **Write the failing test** in the appropriate suite
2. **Run only that test** to confirm it fails:
    - PHP: `vendor/bin/pest --filter='test name'`
    - JS: `bun test --isolate tests/Controllers/<name>_controller.test.js`
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
- Always run `bun run test` at the end to ensure nothing else broke (the script applies `--isolate --parallel`)
- The suite runs with `bun test --isolate --parallel` (Bun ≥1.3.10): each file gets its own JSGlobalObject, so
  `mock.module` registrations don't leak between files. Drop the flag once Bun 1.4 makes isolation the default.
- Use Playwright (`tests/Browser/*.pw.js`) for controller behavior that depends on real browser semantics:
  `MutationObserver`, focus, `requestAnimationFrame`, layout, Turbo frame-like DOM changes or other complex event
  timing.
- Keep Playwright tests focused and few; prefer `bun run test` for deterministic controller unit behavior.
- Run `bun run test:browser` after changing browser-dependent behavior.

## Dependencies

- PHP: `emaia/laravel-hotwire-turbo`, `spatie/laravel-package-tools`
- JS: `@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-lazy-loader`
- Optional JS: third-party libs required by specific controllers — the `npm` maps in `src/Registry/catalog.php` are
  the source of truth (don't list them here)

## Docblock convention

**Selective documentation** — docblocks must add information; if they don't, they're noise. Default to skipping; document deliberately.

### Decision tree

```
1. Is this method part of the public API that app code calls?
   (Facades, fluent builders, model accessors/scopes, trait methods exposed to consumers,
    interface contracts, public methods on PHP classes)
   → YES  → docblock required (single-line summary at minimum)
   → NO   → step 2

2. Is the purpose non-obvious from name + parameter types + return type?
   (Cross-class interaction, hidden constraint, unusual side effect, surprising return semantics)
   → YES  → docblock focused on WHY, not what
   → NO   → skip — let the code speak
```

### What to write

- **Imperative mood**, single sentence ending in a period: `Persist the upload and return the new Media.`
- **Multi-line only** when 2-3 sentences are required to capture a non-obvious constraint or rationale. If you need more, the docblock is masking a design smell — refactor or move the explanation to `docs/*.md`.
- **WHY over what** when explaining: "Extracted so the per-iteration try/catch covers every step" — not "Encodes and persists a conversion" (that's the name).

### Native types first

Always declare native PHP types on properties, parameters, and return values when possible. Drop redundant `@var` / `@param` / `@return` once the native type carries the contract. Untyped signatures combined with a docblock that names the type are an anti-pattern (the type system can't enforce what the docblock says).

Constructors don't need a return type. PHP's `resource` pseudo-type has no native equivalent — use `@param resource $stream` / `@return resource` there.

### When to add `@param` / `@return` / `@throws`

- **`@param` / `@return`**: only when they carry information **beyond** the native type (`array<string, callable>`, `string[]`, `Collection<int, Media>`, `array{conversion: string, exception: \Throwable}`, semantic constraint like "empty array returns all"). Never add when they merely repeat the type.
- **`@throws`**: list exceptions that are part of the method's contract (caller is expected to handle them). Skip generic `RuntimeException` of the "if it breaks it broke" variety.
- **Property `@var`**: only when the native type loses information (`array<int, array{conversion: string, exception: \Throwable}>`, `MediaChannel[]`, `array<string, Collection<int, Media>>`). Plain typed properties don't need it.

### What to always remove

- Auto-generated IDE docblocks: `/** Get the X. */` on `getX()` — pure noise
- `@author`, `@version`, `@since`, `@package` — git/Composer resolve these
- Multi-paragraph essays — move to `docs/*.md` and link
- `@param` / `@return` that just repeat the type-hint
- Comments inside method bodies explaining WHAT the next line does (rename a variable instead); keep only WHY when non-obvious
- Stale TODO/FIXME — convert to an issue or delete

## Roadmap

Operational roadmap and execution notes are maintained outside this repository. Treat local planning exports as temporary context that may be stale.

## CSS / Theming (since `0.32.0`)

- **Semantic tokens only.** Component views use `bg-background`, `text-foreground`, `border-border`, etc.
  Never hardcode raw colors (`bg-white`, `text-gray-700`, `bg-zinc-900`). Tokens are defined in
  `stubs/resources/css/app.css` via `@theme inline` with OKLCH values for light and dark mode.
- **Dark mode via `data-theme`** on `<html>`, not `class="dark"`. Default is `:root` (light);
  `[data-theme="dark"]` activates the dark palette.
- **Tailwind v4 scanner constraint.** Shipped component styling lives in CSS presets under `resources/css/**`,
  and the installed stub scans those CSS files with `@source`. Do not add Tailwind utility defaults back to
  package Blade/PHP unless they are also represented by preset CSS or an explicit `@source inline(...)` safelist.
- **`Variants` helper.** Use `Support\Variants` when a component has **two or more variant groups** (e.g.
  `variant × size`) or **compound rules**. For zero or one variant group, stick with `@class([...])`.
  Variants configuration lives in the Component class (`classNames()` method), not in the Blade view.
  See `src/Support/Variants.php` for the API.
- Theming docs for app developers: `docs/theming.md` — token reference, override instructions, colour
  space notes. Upgrade notes for existing apps: `docs/upgrade.md` (deliverable of `0.32.0`).

## PR Checklist (since `0.32.0`)

Every PR that introduces a new component or controller must include:

- [ ] **Smoke matrix.** Component tested: (a) in isolation, (b) inside a Modal, (c) inside a Dropdown,
  (d) inside a Turbo Frame. Overlay components additionally tested nested inside each other.
- [ ] **Listeners and timers** verified cleaned up in `disconnect()` — Turbo morph re-connects
  controllers; duplicate listeners are a recurring bug source.
- [ ] **Integration opt-in evaluation.** For each new component, assess whether exposing a prop that
  activates an existing controller turns a common user setup into a one-liner. Model: Button + `hotkey`
  (`hotkey="cmd+s"`). Document the decision (adopted or discarded, with rationale) in the PR body.

## New internal helpers (since `0.32.0`)

- `_overlay.js` — shared overlay lifecycle (open/close class toggling, FocusTrap, body scroll lock,
  outside-click dismiss, Escape key, focus return, configurable durations). Consumed by `modal_controller`,
  `alert_dialog_controller` and future Sheet/Drawer/Sidebar controllers. Exports `createOverlay(controller, options)`
  returning `{ open(), close(), cleanup(), isOpen }`.

## Controller auto-loading (since `0.32.0`)

All package controllers are loaded directly from the vendor directory via `import.meta.glob` in
`resources/js/controllers/index.js`. No `hotwire:controllers` step is required — controllers
work out of the box.

The loader index is a static file copied from `stubs/resources/js/controllers/index.js` by
`hotwire:install`. There is no dynamic generation or version tracking — the stub is the single
source of truth.

`hotwire:check` verifies npm dependencies (declared in the catalog `npm` key) and reports
outdated/diverged published controllers. It no longer flags "not published" as a problem since
controllers auto-load from vendor. `hotwire:controllers` remains available for users who want to
publish a controller to customize it.
