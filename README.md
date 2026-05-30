[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)

# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers, and Blade components out of
the box.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
    - [Quick Start](#quick-start)
    - [Explore the Docs](#explore-the-docs)
- [Turbo](#turbo)
- [Blade Components](#blade-components)
- [Stimulus Controllers](#stimulus-controllers-standalone)
    - [Publish Controllers](#publish-stimulus-controllers)
        - [Top-level controllers](#top-level-controllers)
        - [Turbo](#turbo-1)
        - [Optimistic](#optimistic)
        - [Dev](#dev)
- [Stimulus Attribute Helpers](#stimulus-attribute-helpers)
- [Verify Your Setup](#verify-your-setup)
- [Configuration](#configuration)
- [View Customization](#view-customization)
- [Extending](#extending-the-package)
- [Testing](#testing)
- [Manual Installation](#manual-installation)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Requirements

- PHP 8.3+
- Laravel 12+
- [Stimulus](https://stimulus.hotwired.dev/) with a loader compatible with `import.meta.glob` (
  e.g. [@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader))
- Tailwind CSS
- Vite.js

## Installation

```bash
composer require emaia/laravel-hotwire
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=hotwire-config
```

### Quick Start

The installation command scaffolds the Hotwire setup in your Laravel application — JS entry points, Stimulus loader,
Turbo imports, and CSS custom variants:

```bash
php artisan hotwire:install
```

This will:

1. Copy JS and CSS scaffolding to `resources/`
2. Add `@hotwired/stimulus`, `@hotwired/turbo` and `@emaia/stimulus-dynamic-loader` to your `package.json`
3. Show instructions for the next steps

> Only the three core dependencies above are added at installation time. Extra npm packages required by specific
> components (e.g. `tippy.js`, `@emaia/sonner`) are published on demand by
> [`hotwire:check`](#verify-your-setup) once you actually use a component that depends on them.

Options:

```bash
# Overwrite existing files without prompting
php artisan hotwire:install --force

# Install only JS or CSS scaffolding
php artisan hotwire:install --only=js
php artisan hotwire:install --only=css
```

> If a target file already exists and is identical, it is skipped. If it differs, the command asks for confirmation
> before overwriting (unless `--force` is used).

After installation, a good next step is:

1. Browse the package docs in the terminal to see what is available
2. Publish the Stimulus controllers you actually want to use
3. Run `hotwire:check` to verify controllers and npm dependencies used by your views

### Explore the Docs

You can browse the package docs directly in the terminal:

```bash
php artisan hotwire:docs
```

This opens an interactive search across all controllers and components. Type a name, category, or keyword to filter:

```
 ┌ Search controllers and components ───────────────────────────────┐
 │ form                                                              │
 ├───────────────────────────────────────────────────────────────────┤
 │   auto-save           [forms]    Automatically saves a form…      │
 │ › auto-submit         [forms]    Submits a form automatically…    │
 │   clean-query-params  [forms]    Strips empty fields from the…    │
 │   optimistic--form    [turbo]    Dispatches optimistic UI…        │
 └───────────────────────────────────────────────────────────────────┘
```

Read a specific controller or component directly:

```bash
php artisan hotwire:docs auto-submit
php artisan hotwire:docs turbo/progress
php artisan hotwire:docs modal --component
```

List everything with category and description:

```bash
php artisan hotwire:docs --list
php artisan hotwire:docs --list --controller
php artisan hotwire:docs --list --component
```

## Turbo

This package includes [emaia/laravel-hotwire-turbo](https://github.com/emaia/laravel-hotwire-turbo) as a dependency,
providing full Turbo integration for Laravel:

- **Turbo Streams** — fluent builder for append, prepend, replace, update, remove, morph, refresh, and more
- **Turbo Frames** — `<x-turbo::frame>` Blade component with lazy loading support
- **DOM helpers** — `dom_id()` and `dom_class()` for consistent element identification
- **Request detection** — `wantsTurboStream()` and `wasFromTurboFrame()` macros
- **Blade directives** — `@turboNocache`, `@turboRefreshMethod('morph')`, etc.
- **Testing utilities** — `InteractsWithTurbo` trait with `assertTurboStream()` assertions

```php
// Example: return Turbo Streams
return turbo_stream()
    ->append('messages', view('messages.item', compact('message')))
    ->remove('modal');
```

See the full documentation at [emaia/laravel-hotwire-turbo](https://github.com/emaia/laravel-hotwire-turbo).

## Blade Components

| Component                                             | Blade                      | Category   | Stimulus Identifier(s)                                                 | Docs                                         |
|-------------------------------------------------------|----------------------------|------------|------------------------------------------------------------------------|----------------------------------------------|
| [Form](docs/components/form.md)                       | `<x-hwc::form>`            | `forms`    | `auto-submit`, `unsaved-changes`, `error-scroll`, `clean-query-params` | [readme](docs/components/form.md)            |
| [Field](docs/components/field.md)                     | `<x-hwc::field>`           | `forms`    | —                                                                      | [readme](docs/components/field.md)           |
| [Input](docs/components/input.md)                     | `<x-hwc::input>`           | `forms`    | `auto-select`, `clear-input`, `input-mask`                             | [readme](docs/components/input.md)           |
| [Label](docs/components/label.md)                     | `<x-hwc::label>`           | `forms`    | —                                                                      | [readme](docs/components/label.md)           |
| [Select](docs/components/select.md)                   | `<x-hwc::select>`          | `forms`    | —                                                                      | [readme](docs/components/select.md)          |
| [Textarea](docs/components/textarea.md)               | `<x-hwc::textarea>`        | `forms`    | `auto-resize`, `char-counter`                                          | [readme](docs/components/textarea.md)        |
| [File](docs/components/file.md)                       | `<x-hwc::file>`            | `forms`    | `file-preserve`, `reset-files`                                         | [readme](docs/components/file.md)            |
| [Checkbox Group](docs/components/checkbox-group.md)   | `<x-hwc::checkbox-group>`  | `forms`    | `checkbox-select-all`                                                  | [readme](docs/components/checkbox-group.md)  |
| [Description](docs/components/description.md)         | `<x-hwc::description>`     | `forms`    | —                                                                      | [readme](docs/components/description.md)     |
| [Error](docs/components/error.md)                     | `<x-hwc::error>`           | `forms`    | —                                                                      | [readme](docs/components/error.md)           |
| [Flash Container](docs/components/flash-container.md) | `<x-hwc::flash-container>` | `feedback` | `toaster`                                                              | [readme](docs/components/flash-container.md) |
| [Flash Message](docs/components/flash-message.md)     | `<x-hwc::flash-message>`   | `feedback` | `toast`                                                                | [readme](docs/components/flash-message.md)   |
| [Spinner](docs/components/spinner.md)                 | `<x-hwc::spinner>`         | `feedback` | —                                                                      | [readme](docs/components/spinner.md)         |
| [Modal](docs/components/modal.md)                     | `<x-hwc::modal>`           | `overlay`  | `modal`                                                                | [readme](docs/components/modal.md)           |
| [Confirm Dialog](docs/components/confirm-dialog.md)   | `<x-hwc::confirm-dialog>`  | `overlay`  | `confirm-dialog`                                                       | [readme](docs/components/confirm-dialog.md)  |
| [Optimistic](docs/components/optimistic.md)           | `<x-hwc::optimistic>`      | `turbo`    | —                                                                      | [readme](docs/components/optimistic.md)      |
| [Scroll Progress](docs/components/scroll-progress.md) | `<x-hwc::scroll-progress>` | `utility`  | `scroll-progress`                                                      | [readme](docs/components/scroll-progress.md) |
| [Timeago](docs/components/timeago.md)                 | `<x-hwc::timeago>`         | `utility`  | `timeago`                                                              | [readme](docs/components/timeago.md)         |

## Stimulus Controllers (standalone)

Stimulus controllers without an associated Blade component. Used directly via `data-controller` and `data-action`.

Controllers live flat at the top level (`resources/js/controllers/<name>_controller.{js,ts}`). Substrate folders
(`turbo/`, `optimistic/`, `dev/`) group controllers tied to a specific technical layer and use Stimulus' `--` separator
in the identifier.

```bash
php artisan hotwire:controllers auto-select auto-submit turbo/progress
```

### Publish Stimulus Controllers

Publish the controllers you want to use in your app so they can be discovered by the bundler (Vite).

**Interactive** — select which controllers to publish:

```bash
php artisan hotwire:controllers
```

**By name** — publish a specific controller:

```bash
php artisan hotwire:controllers auto-select
```

**Substrate namespace** — publish every controller under a substrate folder (`turbo`, `optimistic`, `dev`):

```bash
php artisan hotwire:controllers turbo
```

**Multiple arguments** — mix names and substrate namespaces:

```bash
php artisan hotwire:controllers modal turbo/progress auto-submit
```

**All at once:**

```bash
php artisan hotwire:controllers --all
```

**List available controllers (with publication status):**

```bash
php artisan hotwire:controllers --list
```

**Update only controllers that are already published but differ from the package source:**

```bash
php artisan hotwire:controllers --outdated

# Non-interactive — useful after composer update in CI or deploy scripts
php artisan hotwire:controllers --outdated --force
```

`--outdated` never installs controllers that haven't been published yet, and skips those that are already up to date.

**Overwrite existing files:**

```bash
php artisan hotwire:controllers auto-select --force
```

Top-level controllers are copied flat to `resources/js/controllers/` (e.g. `modal` →
`resources/js/controllers/modal_controller.js`, identifier `modal`). Controllers under a substrate folder preserve
that folder and use Stimulus' `--` separator (e.g. `turbo/progress` →
`resources/js/controllers/turbo/progress_controller.js`, identifier `turbo--progress`).
[@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader) discovers and loads
them automatically via `import.meta.glob`.

> If a controller already exists and is identical to the package version, the command reports it as up to date. If it
> differs, it asks for confirmation before overwriting.

### Top-level controllers

| Controller                                                     | Identifier            | Category   | Dependencies    | Docs                                              |
|----------------------------------------------------------------|-----------------------|------------|-----------------|---------------------------------------------------|
| [Animated Number](docs/controllers/animated-number.md)         | `animated-number`     | `utility`  | —               | [readme](docs/controllers/animated-number.md)     |
| [Auto Save](docs/controllers/auto-save.md)                     | `auto-save`           | `forms`    | —               | [readme](docs/controllers/auto-save.md)           |
| [Auto Resize](docs/controllers/auto-resize.md)                 | `auto-resize`         | `forms`    | —               | [readme](docs/controllers/auto-resize.md)         |
| [Auto Select](docs/controllers/auto-select.md)                 | `auto-select`         | `forms`    | —               | [readme](docs/controllers/auto-select.md)         |
| [Auto Submit](docs/controllers/auto-submit.md)                 | `auto-submit`         | `forms`    | —               | [readme](docs/controllers/auto-submit.md)         |
| [Char Counter](docs/controllers/char-counter.md)               | `char-counter`        | `forms`    | —               | [readme](docs/controllers/char-counter.md)        |
| [Checkbox Select All](docs/controllers/checkbox-select-all.md) | `checkbox-select-all` | `forms`    | —               | [readme](docs/controllers/checkbox-select-all.md) |
| [Clean Query Params](docs/controllers/clean-query-params.md)   | `clean-query-params`  | `forms`    | —               | [readme](docs/controllers/clean-query-params.md)  |
| [Clear Input](docs/controllers/clear-input.md)                 | `clear-input`         | `forms`    | —               | [readme](docs/controllers/clear-input.md)         |
| [Confirm Dialog](docs/controllers/confirm-dialog.md)           | `confirm-dialog`      | `overlay`  | —               | [readme](docs/controllers/confirm-dialog.md)      |
| [Copy To Clipboard](docs/controllers/copy-to-clipboard.md)     | `copy-to-clipboard`   | `utility`  | —               | [readme](docs/controllers/copy-to-clipboard.md)   |
| [Error Scroll](docs/controllers/error-scroll.md)               | `error-scroll`        | `forms`    | —               | [readme](docs/controllers/error-scroll.md)        |
| [File Preserve](docs/controllers/file-preserve.md)             | `file-preserve`       | `forms`    | —               | [readme](docs/controllers/file-preserve.md)       |
| [GTM](docs/controllers/gtm.md)                                 | `gtm`                 | `utility`  | —               | [readme](docs/controllers/gtm.md)                 |
| [Hotkey](docs/controllers/hotkey.md)                           | `hotkey`              | `utility`  | —               | [readme](docs/controllers/hotkey.md)              |
| [Input Mask](docs/controllers/input-mask.md)                   | `input-mask`          | `forms`    | `maska`         | [readme](docs/controllers/input-mask.md)          |
| [Lazy Image](docs/controllers/lazy-image.md)                   | `lazy-image`          | `utility`  | —               | [readme](docs/controllers/lazy-image.md)          |
| [Modal](docs/controllers/modal.md)                             | `modal`               | `overlay`  | —               | [readme](docs/controllers/modal.md)               |
| [Modal Auto Close](docs/controllers/modal-auto-close.md)       | `modal-auto-close`    | `overlay`  | —               | [readme](docs/controllers/modal-auto-close.md)    |
| [Money Input](docs/controllers/money-input.md)                 | `money-input`         | `forms`    | —               | [readme](docs/controllers/money-input.md)         |
| [OEmbed](docs/controllers/oembed.md)                           | `oembed`              | `utility`  | —               | [readme](docs/controllers/oembed.md)              |
| [Remote Form](docs/controllers/remote-form.md)                 | `remote-form`         | `forms`    | —               | [readme](docs/controllers/remote-form.md)         |
| [Reset Files](docs/controllers/reset-files.md)                 | `reset-files`         | `forms`    | —               | [readme](docs/controllers/reset-files.md)         |
| [Scroll Progress](docs/controllers/scroll-progress.md)         | `scroll-progress`     | `utility`  | —               | [readme](docs/controllers/scroll-progress.md)     |
| [Timeago](docs/controllers/timeago.md)                         | `timeago`             | `utility`  | `date-fns`      | [readme](docs/controllers/timeago.md)             |
| [Toast](docs/controllers/toast.md)                             | `toast`               | `feedback` | `@emaia/sonner` | [readme](docs/controllers/toast.md)               |
| [Toaster](docs/controllers/toaster.md)                         | `toaster`             | `feedback` | `@emaia/sonner` | [readme](docs/controllers/toaster.md)             |
| [Tooltip](docs/controllers/tooltip.md)                         | `tooltip`             | `utility`  | `tippy.js`      | [readme](docs/controllers/tooltip.md)             |
| [Unsaved Changes](docs/controllers/unsaved-changes.md)         | `unsaved-changes`     | `forms`    | —               | [readme](docs/controllers/unsaved-changes.md)     |

### Turbo

Controllers tied to Turbo Drive / Turbo Frames.

| Controller                                                   | Identifier               | Dependencies      | Docs                                                |
|--------------------------------------------------------------|--------------------------|-------------------|-----------------------------------------------------|
| [Frame Src](docs/controllers/turbo/frame-src.md)             | `turbo--frame-src`       | `@hotwired/turbo` | [readme](docs/controllers/turbo/frame-src.md)       |
| [Polling](docs/controllers/turbo/polling.md)                 | `turbo--polling`         | `@hotwired/turbo` | [readme](docs/controllers/turbo/polling.md)         |
| [Progress](docs/controllers/turbo/progress.md)               | `turbo--progress`        | `@hotwired/turbo` | [readme](docs/controllers/turbo/progress.md)        |
| [View Transition](docs/controllers/turbo/view-transition.md) | `turbo--view-transition` | —                 | [readme](docs/controllers/turbo/view-transition.md) |

### Optimistic

| Controller                                          | Identifier             | Dependencies      | Docs                                              |
|-----------------------------------------------------|------------------------|-------------------|---------------------------------------------------|
| [Dispatch](docs/controllers/optimistic/dispatch.md) | `optimistic--dispatch` | `@hotwired/turbo` | [readme](docs/controllers/optimistic/dispatch.md) |
| [Form](docs/controllers/optimistic/form.md)         | `optimistic--form`     | `@hotwired/turbo` | [readme](docs/controllers/optimistic/form.md)     |
| [Link](docs/controllers/optimistic/link.md)         | `optimistic--link`     | `@hotwired/turbo` | [readme](docs/controllers/optimistic/link.md)     |

### Dev

| Controller                         | Identifier | Dependencies | Docs                                  |
|------------------------------------|------------|--------------|---------------------------------------|
| [Log](docs/controllers/dev/log.md) | `dev--log` | —            | [readme](docs/controllers/dev/log.md) |

## Stimulus Attribute Helpers

Build Stimulus `data-*` attributes from Blade without hand-writing the verbose markup. The primary
`stimulus()` entry point returns a fluent, chainable builder that is `Htmlable` (renders directly in
`{{ }}`) and `Arrayable` (merges into a component's attribute bag):

```blade
<div {{ stimulus()
        ->controller('chart', ['name' => 'Likes', 'data' => [1, 2, 3, 4]])
        ->action('chart', 'refresh', 'click')
        ->target('chart', 'canvas') }}>
```

```php
stimulus();
stimulus_controller($name, $values = [], $classes = [], $outlets = []);
stimulus_action($controller, $method, $event = null, $params = []);
stimulus_target($controller, $target);
```

`stimulus_controller()` is an alias for `stimulus()->controller(...)`; `stimulus_action()` and
`stimulus_target()` are shortcuts for `stimulus()->action(...)` and `stimulus()->target(...)`.

See [Stimulus attribute helpers](docs/stimulus-helpers.md) for values/classes/outlets, action params,
stacking multiple controllers, attribute-bag merging and the escaping rules.

## Verify Your Setup

**List components and their required controllers:**

```bash
php artisan hotwire:components
```

Shows each Blade component, its tag, and the Stimulus controllers it depends on — with publication status for each.

**Check controllers for components used in your views:**

```bash
php artisan hotwire:check
```

Scans `resources/views` for Hotwire components, then verifies two things:

1. **Stimulus controllers** — every controller required by a used component is published and up to date.
2. **npm dependencies** — every external package imported by those controllers (e.g. `@emaia/sonner`, `tippy.js`)
   is declared in your `package.json` (`dependencies` or `devDependencies`).

Exits with code `1` if either has pending items (useful for CI).

Both the configured prefix (`hwc` by default) and the literal `hotwire` alias are recognized, so views like
`<x-hwc::flash-message />` and `<x-hotwire::flash-message />` are detected equally.

```bash
# Auto-publish missing/outdated controllers AND add missing npm deps to devDependencies
php artisan hotwire:check --fix

# Also run the detected package manager install command after adding deps
php artisan hotwire:check --fix --install

# Scan a custom path
php artisan hotwire:check --path=resources/views/app
```

Example output:

```
  ✓  toaster  up to date  (used by <x-hwc::flash-container>)
  ✓  toast    up to date  (used by <x-hwc::flash-message>)

Required npm dependencies:
  ✓  @emaia/sonner ^2.1.0  (used by toaster, toast)
  ✗  tippy.js ^6.3.7       missing from package.json (used by tooltip)
```

> In interactive mode, `hotwire:check` asks whether to run the detected package manager install command after adding
> dependencies. In non-interactive scripts, use `--fix --install` to run it automatically.

## Configuration

```php
// config/hotwire.php

return [
    'prefix' => 'hwc', // <x-hwc::modal>
];
```

Change `prefix` to use a different prefix for Blade components. E.g. `'prefix' => 'hotwire'` → `<x-hotwire::modal>`.

## View Customization

To customize the HTML/Tailwind of the components:

```bash
php artisan vendor:publish --tag=hotwire-views
```

Views published to `resources/views/vendor/hotwire/` will take precedence over the package defaults.

## Extending the package

Laravel Hotwire uses a single registry as the source of truth for:

- Blade components
- Stimulus controllers
- external npm dependencies
- docs paths
- public categories

When adding a new component or controller to this package, update the registry entry in
[`src/Registry/catalog.php`](src/Registry/catalog.php).

Example component entry:

```php
'modal' => [
    'class' => \Emaia\LaravelHotwire\Components\Modal::class,
    'view' => 'hotwire::component-views.modal',
    'docs' => 'docs/components/modal.md',
    'category' => 'overlay',
    'controllers' => ['modal'],
],
```

Example controller entry:

```php
'tooltip' => [
    'source' => 'resources/js/controllers/tooltip_controller.js',
    'docs' => 'docs/controllers/tooltip.md',
    'category' => 'utility',
    'npm' => ['tippy.js' => '^6.3.7'],
],
```

More details: [docs/registry.md](docs/registry.md)

## Testing

```bash
composer test
```

## Manual Installation

If you prefer to set things up manually instead of using `hotwire:install`, follow the steps below.

### Project setup (using Vite)

```js
// resources/js/app.js
import "./libs";

// resources/js/libs/index.js
import "./turbo";
import "./stimulus";
import "../controllers";

// resources/js/libs/turbo.js
import * as Turbo from "@hotwired/turbo";

export default Turbo;

// resources/js/libs/stimulus.js
import {Application} from '@hotwired/stimulus'

const Stimulus = Application.start()
window.Stimulus = Stimulus
export {Stimulus}

// resources/js/controllers/index.js
import {Stimulus} from "../libs/stimulus";
import {registerControllers} from "@emaia/stimulus-dynamic-loader";

const controllers = import.meta.glob("./**/*_controller.{js,ts}", {
    eager: false,
});

registerControllers(Stimulus, controllers);
```

Install the required js dependencies:

```bash
bun add @hotwired/stimulus @hotwired/turbo @emaia/stimulus-dynamic-loader
```

### TailwindCSS (v4)

Add these settings to your CSS entrypoint `resources/css/app.css`:

```css
@source '../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php';
@custom-variant turbo-preview (html[data-turbo-preview] &);
@custom-variant turbo-visit (html[aria-busy="true"] &);
@custom-variant form-busy (form[aria-busy="true"] &);
@custom-variant frame-busy (turbo-frame[busy] &);
@custom-variant in-turbo-frame (turbo-frame &);
@custom-variant in-remote-turbo-frame (turbo-frame[src] &);
@custom-variant modal ([data-modal-target="dialog"] &);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome via pull requests.

## Security Vulnerabilities

Please review [our security policy](https://github.com/emaia/laravel-hotwire/security/policy) on how to report security
vulnerabilities.

## Credits

- [Ednilson Maia](https://github.com/emaia)
- [All Contributors](https://github.com/emaia/laravel-hotwire/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
