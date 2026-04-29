# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components.

## Package Structure

- `src/` — PHP source (commands, components, service provider)
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
| `hotwire:check`           | Verify required controllers are published (CI-friendly)           |
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

### Blade Components

- Registered with configurable prefix (default: `hwc`)
- Usage: `<x-hwc::modal>`, `<x-hwc::confirm-dialog>`, `<x-hwc::flash-message>`, `<x-hwc::loader>`, `<x-hwc::scroll-progress>`, `<x-hwc::timeago>`
- Components and Controllers needs to be registered in Registry catalog `src/Registry/catalog.php`
- Components that encapsulate a Stimulus controller must not merge user-provided `data-controller`, `data-action`, or
  `data-{identifier}-*` attributes. Expose supported controller configuration as explicit Blade props instead.

### Turbo

- Provided by `emaia/laravel-hotwire-turbo` dependency
- Fluent stream builder: `turbo_stream()->append(...)` (the builder is `Responsable` — return it directly from the
  controller; use `->withResponse(...)` when you need a custom status or headers)
- Request detection: `request()->wantsTurboStream()`, `request()->wasFromTurboFrame()`
- DOM helpers: `dom_id($model)`, `dom_class($model)`

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
