[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)

# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components out of
the box.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
    - [Quick Start](#quick-start)
    - [Stimulus Controllers](#stimulus-controllers)
    - [View Customization](#view-customization)
    - [Manual Installation](#manual-installation)
- [Configuration](#configuration)
- [Turbo](#turbo)
- [Components](#components)
- [Stimulus Controllers (standalone)](#stimulus-controllers-standalone)
    - [Top-level controllers](#top-level-controllers)
    - [Turbo](#turbo-1)
    - [Optimistic](#optimistic)
    - [Dev](#dev)
- [Testing](#testing)
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

The install command scaffolds the Hotwire setup in your Laravel application — JS entry points, Stimulus loader,
Turbo imports and CSS custom variants:

```bash
php artisan hotwire:install
```

This will:

1. Copy JS and CSS scaffolding to `resources/`
2. Add `@hotwired/stimulus`, `@hotwired/turbo` and `@emaia/stimulus-dynamic-loader` to your `package.json`
3. Show instructions for the next steps

> Only the three core dependencies above are added at install time. Extra npm packages required by specific
> components (e.g. `maska`, `tippy.js`, `@emaia/sonner`) are published on demand by
> [`hotwire:check`](#stimulus-controllers) once you actually use a component that depends on them.

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

### Stimulus Controllers

The components depend on Stimulus controllers that must be published to your project so they can be discovered by the
bundler (Vite).

**Interactive** — select which controllers to publish:

```bash
php artisan hotwire:controllers
```

**By name** — publish a specific controller:

```bash
php artisan hotwire:controllers autoselect
```

**Substrate namespace** — publish every controller under a substrate folder (`turbo`, `optimistic`, `dev`):

```bash
php artisan hotwire:controllers turbo
```

**Multiple arguments** — mix names and substrate namespaces:

```bash
php artisan hotwire:controllers dialog turbo/progress auto-submit
```

**All at once:**

```bash
php artisan hotwire:controllers --all
```

**List available controllers (with publish status):**

```bash
php artisan hotwire:controllers --list
```

**Overwrite existing files:**

```bash
php artisan hotwire:controllers autoselect --force
```

Top-level controllers are copied flat to `resources/js/controllers/` (e.g. `dialog` →
`resources/js/controllers/dialog_controller.js`, identifier `dialog`). Controllers under a substrate folder preserve
that folder and use Stimulus' `--` separator (e.g. `turbo/progress` →
`resources/js/controllers/turbo/progress_controller.js`, identifier `turbo--progress`).
[@emaia/stimulus-dynamic-loader](https://www.npmjs.com/package/@emaia/stimulus-dynamic-loader) discovers and loads
them automatically via `import.meta.glob`.

> If a controller already exists and is identical to the package version, the command reports it as up to date. If it
> differs, it asks for confirmation before overwriting.

**List components and their required controllers:**

```bash
php artisan hotwire:components
```

Shows each Blade component, its tag, and the Stimulus controllers it depends on — with publish status for each.

**Check controllers for components used in your views:**

```bash
php artisan hotwire:check
```

Scans `resources/views` for Hotwire components, then verifies two things:

1. **Stimulus controllers** — every controller required by a used component is published and up to date.
2. **npm dependencies** — every external package imported by those controllers (e.g. `@emaia/sonner`, `tippy.js`, `maska`)
   is declared in your `package.json` (`dependencies` or `devDependencies`).

Exits with code `1` if either has pending items (useful for CI).

Both the configured prefix (`hwc` by default) and the literal `hotwire` alias are recognized, so views like
`<x-hwc::flash-message />` and `<x-hotwire::flash-message />` are detected equally.

```bash
# Auto-publish missing/outdated controllers AND add missing npm deps to devDependencies
php artisan hotwire:check --fix

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

> When `--fix` adds packages to `devDependencies`, run your package manager's install command afterwards
> (`bun install`, `pnpm install`, etc.) to actually fetch them.

### View Customization

To customize the HTML/Tailwind of the components:

```bash
php artisan vendor:publish --tag=hotwire-views
```

Views published to `resources/views/vendor/hotwire/` will take precedence over the package defaults.

### Manual Installation

If you prefer to set things up manually instead of using `hotwire:install`, follow the steps below.

#### Project setup (using Vite)

```js
// resources/js/app.js
import "./libs";

// resources/js/libs/index.js
import "./turbo";
import "./stimulus";
import "../controllers";

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

#### TailwindCSS (v4)

Add these settings to your CSS entrypoint `resources/css/app.css`:

```css
@source '../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php';
@custom-variant turbo-frame (turbo-frame[src] &);
@custom-variant modal ([data-dialog-target="dialog"] &);
@custom-variant aria-busy (form[aria-busy="true"] &);
@custom-variant self-aria-busy (html[aria-busy="true"] &);
@custom-variant turbo-frame-aria-busy (turbo-frame[aria-busy="true"] &);
```

## Configuration

```php
// config/hotwire.php

return [
    'prefix' => 'hwc', // <x-hwc::dialog>
];
```

Change `prefix` to use a different prefix for Blade components. E.g. `'prefix' => 'hotwire'` → `<x-hotwire::dialog>`.

## Turbo

This package includes [emaia/laravel-hotwire-turbo](https://github.com/emaia/laravel-hotwire-turbo) as a dependency,
providing full Turbo integration for Laravel:

- **Turbo Streams** — fluent builder for append, prepend, replace, update, remove, morph, refresh and more
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

## Components

| Component                                                  | Blade                      | Stimulus Identifier | Docs                                               |
|------------------------------------------------------------|----------------------------|---------------------|----------------------------------------------------|
| [Dialog](docs/components/dialog/readme.md)                 | `<x-hwc::dialog>`          | `dialog`            | [readme](docs/components/dialog/readme.md)         |
| [Confirm Dialog](docs/components/confirm-dialog/readme.md) | `<x-hwc::confirm-dialog>`  | `confirm-dialog`    | [readme](docs/components/confirm-dialog/readme.md) |
| [Flash Container](docs/components/flash-message/readme.md) | `<x-hwc::flash-container>` | `toaster`           | [readme](docs/components/flash-message/readme.md)  |
| [Flash Message](docs/components/flash-message/readme.md)   | `<x-hwc::flash-message>`   | `toast`             | [readme](docs/components/flash-message/readme.md)  |
| [Loader](docs/components/loader/readme.md)                 | `<x-hwc::loader>`          | —                   | [readme](docs/components/loader/readme.md)         |
| [Optimistic](docs/components/optimistic/readme.md)         | `<x-hwc::optimistic>`      | —                   | [readme](docs/components/optimistic/readme.md)     |
| [Timeago](docs/controllers/timeago.md)                     | `<x-hwc::timeago>`         | `timeago`           | [readme](docs/controllers/timeago.md)              |

## Stimulus Controllers (standalone)

Stimulus controllers without an associated Blade component. Used directly via `data-controller` and `data-action`.

Controllers live flat at the top level (`resources/js/controllers/<name>_controller.{js,ts}`). Substrate folders
(`turbo/`, `optimistic/`, `dev/`) group controllers tied to a specific technical layer and use Stimulus' `--` separator
in the identifier.

```bash
php artisan hotwire:controllers autoselect auto-submit turbo/progress
```

### Top-level controllers

| Controller                                                     | Identifier            | Dependencies    | Docs                                              |
|----------------------------------------------------------------|-----------------------|-----------------|---------------------------------------------------|
| [Animated Number](docs/controllers/animated-number.md)         | `animated-number`     | —               | [readme](docs/controllers/animated-number.md)     |
| [Auto Save](docs/controllers/auto-save.md)                     | `auto-save`           | —               | [readme](docs/controllers/auto-save.md)           |
| [Autoresize](docs/controllers/autoresize.md)                   | `autoresize`          | —               | [readme](docs/controllers/autoresize.md)          |
| [Autoselect](docs/controllers/autoselect.md)                   | `autoselect`          | —               | [readme](docs/controllers/autoselect.md)          |
| [Auto Submit](docs/controllers/auto-submit.md)                 | `auto-submit`         | —               | [readme](docs/controllers/auto-submit.md)         |
| [Char Counter](docs/controllers/char-counter.md)               | `char-counter`        | —               | [readme](docs/controllers/char-counter.md)        |
| [Checkbox Select All](docs/controllers/checkbox-select-all.md) | `checkbox-select-all` | —               | [readme](docs/controllers/checkbox-select-all.md) |
| [Clean Query Params](docs/controllers/clean-query-params.md)   | `clean-query-params`  | —               | [readme](docs/controllers/clean-query-params.md)  |
| [Clear Input](docs/controllers/clear-input.md)                 | `clear-input`         | —               | [readme](docs/controllers/clear-input.md)         |
| [Copy To Clipboard](docs/controllers/copy-to-clipboard.md)     | `copy-to-clipboard`   | —               | [readme](docs/controllers/copy-to-clipboard.md)   |
| [Dialog](docs/controllers/dialog.md)                           | `dialog`              | —               | [readme](docs/controllers/dialog.md)              |
| [GTM](docs/controllers/gtm.md)                                 | `gtm`                 | —               | [readme](docs/controllers/gtm.md)                 |
| [Hotkey](docs/controllers/hotkey.md)                           | `hotkey`              | —               | [readme](docs/controllers/hotkey.md)              |
| [Input Mask](docs/controllers/input-mask.md)                   | `input-mask`          | `maska`         | [readme](docs/controllers/input-mask.md)          |
| [Lazy Image](docs/controllers/lazy-image.md)                   | `lazy-image`          | —               | [readme](docs/controllers/lazy-image.md)          |
| [OEmbed](docs/controllers/oembed.md)                           | `oembed`              | —               | [readme](docs/controllers/oembed.md)              |
| [Remote Form](docs/controllers/remote-form.md)                 | `remote-form`         | —               | [readme](docs/controllers/remote-form.md)         |
| [Reset Files](docs/controllers/reset-files.md)                 | `reset-files`         | —               | [readme](docs/controllers/reset-files.md)         |
| [Timeago](docs/controllers/timeago.md)                         | `timeago`             | `date-fns`      | [readme](docs/controllers/timeago.md)             |
| [Toast](docs/controllers/toast.md)                             | `toast`               | `@emaia/sonner` | [readme](docs/controllers/toast.md)               |
| [Toaster](docs/controllers/toaster.md)                         | `toaster`             | `@emaia/sonner` | [readme](docs/controllers/toaster.md)             |
| [Tooltip](docs/controllers/tooltip.md)                         | `tooltip`             | `tippy.js`      | [readme](docs/controllers/tooltip.md)             |
| [Unsaved Changes](docs/controllers/unsaved-changes.md)         | `unsaved-changes`     | —               | [readme](docs/controllers/unsaved-changes.md)     |

### Turbo

Controllers tied to Turbo Drive / Turbo Frames.

| Controller                                                   | Identifier               | Dependencies      | Docs                                                |
|--------------------------------------------------------------|--------------------------|-------------------|-----------------------------------------------------|
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

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome via pull requests.

## Security Vulnerabilities

Please review [our security policy](https://github.com/emaia/laravel-hotwire/security/policy) on how to report security vulnerabilities.

## Credits

- [Ednilson Maia](https://github.com/emaia)
- [All Contributors](https://github.com/emaia/laravel-hotwire/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
