# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components.

## Package Structure

- `src/` — PHP source (commands, components, service provider)
- `resources/js/controllers/` — Stimulus controllers shipped with the package (published to user's app)
- `resources/views/components/` — Blade component views
- `stubs/resources/` — Scaffolding files copied by `hotwire:install`
- `config/hotwire.php` — Package configuration (component prefix)
- `tests/` — Pest PHP tests with Orchestra Testbench
- `docs/` — Documentation

## Artisan Commands

| Command                   | Description                                                       |
|---------------------------|-------------------------------------------------------------------|
| `hotwire:install`         | Scaffold JS/CSS setup, add npm deps to package.json               |
| `hotwire:make-controller` | Create a new Stimulus controller (interactive scaffolding)        |
| `hotwire:controllers`     | Publish package Stimulus controllers to the app                   |
| `hotwire:components`      | List available Blade components and their controller dependencies |
| `hotwire:check`           | Verify required controllers are published (CI-friendly)           |

## Conventions

### Stimulus Controllers

- File naming: `{namespace}/{name}_controller.{js|ts}` (snake_case)
- Identifier: `{namespace}--{name}` (hyphens, `--` as namespace separator)
- Example: `form/autosubmit_controller.js` → `data-controller="form--autosubmit"`
- Loaded via `@emaia/stimulus-dynamic-loader` with Vite's `import.meta.glob`

### Blade Components

- Registered with configurable prefix (default: `hwc`)
- Usage: `<x-hwc::modal>`, `<x-hwc::confirm-dialog>`, `<x-hwc::flash-message>`, `<x-hwc::loader>`
- Components that need JS declare their controllers via `HasStimulusControllers` interface

### Turbo

- Provided by `emaia/laravel-hotwire-turbo` dependency
- Fluent stream builder: `turbo_stream()->append(...)` (the builder is `Responsable` — return it directly from the controller; use `->withResponse(...)` when you need a custom status or headers)
- Request detection: `request()->wantsTurboStream()`, `request()->wasFromTurboFrame()`
- DOM helpers: `dom_id($model)`, `dom_class($model)`

## Development

```bash
composer test          # Run Pest tests
composer analyse       # Run PHPStan
composer format        # Run Pint code formatter
```

### Testing (TDD)

Follow test-driven development: write tests first, then implement.

1. **Write the failing test** in `tests/` using Pest + Orchestra Testbench
2. **Run only that test** to confirm it fails: `vendor/bin/pest --filter='test name'`
3. **Implement** the minimum code to make it pass
4. **Run the test again** to confirm it passes
5. **Repeat** for the next behavior

Conventions:

- Test files mirror `src/` structure: `src/Commands/FooCommand.php` → `tests/Commands/FooCommandTest.php`
- Use Pest syntax (`it()`, `test()`, `expect()`) — no PHPUnit classes
- Group related tests with comment headers: `// --- Section name ---`
- Use `beforeEach`/`afterEach` for shared setup and cleanup (temp files, directories)
- For artisan commands: use `$this->artisan('command')->assertSuccessful()` and `expectsQuestion`/`expectsChoice`/
  `expectsOutput` for interactive flows
- Always run `composer test` at the end to ensure nothing else broke

## Dependencies

- PHP: `emaia/laravel-hotwire-turbo`, `spatie/laravel-package-tools`
- JS: `@hotwired/stimulus`, `@hotwired/turbo`, `@emaia/stimulus-dynamic-loader`
- Optional JS: `maska`, `tippy.js`, `@emaia/sonner` (for specific controllers)
