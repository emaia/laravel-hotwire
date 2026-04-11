[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)

# Laravel Hotwire

The complete Hotwire stack for Laravel — Turbo Drive, Turbo Streams, Stimulus controllers and Blade components out of the box.

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
  - [Dialog](#dialog)
  - [Form](#form)
  - [Frame](#frame)
  - [Dev](#dev)
  - [Lib](#lib)
  - [Media](#media)
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

**By namespace** — publish all controllers in a namespace:

```bash
php artisan hotwire:controllers form
```

**By specific controller** — `namespace/name` notation:

```bash
php artisan hotwire:controllers form/autoselect
```

**Multiple arguments** — mix namespaces and specific controllers:

```bash
php artisan hotwire:controllers form dialog/modal
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
php artisan hotwire:controllers form/autoselect --force
```

Controllers are copied to `resources/js/controllers/` preserving the folder structure. The `namespace/name` argument
mirrors the directory structure: `form/autoselect` → `resources/js/controllers/form/autoselect_controller.js`.
The Stimulus identifier follows the same convention: `dialog/modal` → `data-controller="dialog--modal"`.
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

Scans `resources/views` for Hotwire components, checks whether their required Stimulus controllers are published, and
reports any missing or outdated ones. Exits with code `1` if attention is needed (useful for CI).

```bash
# Auto-publish missing/outdated controllers without prompting
php artisan hotwire:check --fix

# Scan a custom path
php artisan hotwire:check --path=resources/views/app
```

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
bun install @hotwired/stimulus @hotwired/turbo @emaia/stimulus-dynamic-loader
```

#### TailwindCSS (v4)

Add these settings to your CSS entrypoint `resources/css/app.css`:

```css
@source '../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php';
@custom-variant turbo-frame (turbo-frame[src] &);
@custom-variant modal ([data-dialog--modal-target="dialog"] &);
@custom-variant aria-busy (form[aria-busy="true"] &);
@custom-variant self-aria-busy (html[aria-busy="true"] &);
@custom-variant turbo-frame-aria-busy (turbo-frame[aria-busy="true"] &);
```

## Configuration

```php
// config/hotwire.php

return [
    'prefix' => 'hwc', // <x-hwc-modal>
];
```

Change `prefix` to use a different prefix for Blade components. E.g. `'prefix' => 'hotwire'` → `<x-hotwire-modal>`.

## Turbo

This package includes [emaia/laravel-hotwire-turbo](https://github.com/emaia/laravel-hotwire-turbo) as a dependency, providing full Turbo integration for Laravel:

- **Turbo Streams** — fluent builder for append, prepend, replace, update, remove, morph, refresh and more
- **Turbo Frames** — `<x-turbo::frame>` Blade component with lazy loading support
- **DOM helpers** — `dom_id()` and `dom_class()` for consistent element identification
- **Request detection** — `wantsTurboStream()` and `wasFromTurboFrame()` macros
- **Blade directives** — `@turboNocache`, `@turboRefreshMethod('morph')`, etc.
- **Testing utilities** — `InteractsWithTurbo` trait with `assertTurboStream()` assertions

```php
// Example: responding with Turbo Streams
return turbo_stream()
    ->append('messages', view('messages.item', compact('message')))
    ->remove('modal')
    ->respond();
```

See the full documentation at [emaia/laravel-hotwire-turbo](https://github.com/emaia/laravel-hotwire-turbo).

## Components

| Component                                                  | Blade                   | Stimulus Identifier                            | Docs                                               |
|------------------------------------------------------------|-------------------------|------------------------------------------------|----------------------------------------------------|
| [Modal](docs/components/modal/readme.md)                   | `<x-hwc-modal>`         | `dialog--modal`                                | [readme](docs/components/modal/readme.md)          |
| [Confirm Dialog](docs/components/confirm-dialog/readme.md) | `<x-hwc-confirm>`       | `dialog--confirm`                              | [readme](docs/components/confirm-dialog/readme.md) |
| [Flash Message](docs/components/flash-message/readme.md)   | `<x-hwc-flash-message>` | `notification--toaster`, `notification--toast` | [readme](docs/components/flash-message/readme.md)  |
| [Loader](docs/components/loader/readme.md)                 | `<x-hwc-loader>`        | —                                              | [readme](docs/components/loader/readme.md)         |

## Stimulus Controllers (standalone)

Stimulus controllers without an associated Blade component. Used directly via `data-controller` and `data-action`.

```bash
php artisan hotwire:controllers form/autoselect form/autosubmit frame/progress
```

### Dialog

| Controller                                          | Identifier        | Dependencies | Docs                                               |
|-----------------------------------------------------|-------------------|--------------|----------------------------------------------------|
| [Modal](docs/controllers/dialog/modal.md)           | `dialog--modal`   | —            | [readme](docs/controllers/dialog/modal.md)         |
| [Confirm](docs/components/confirm-dialog/readme.md) | `dialog--confirm` | —            | [readme](docs/components/confirm-dialog/readme.md) |

### Form

| Controller                                                      | Identifier                | Dependencies | Docs                                                 |
|-----------------------------------------------------------------|---------------------------|--------------|------------------------------------------------------|
| [Autoselect](docs/controllers/form/autoselect.md)               | `form--autoselect`        | —            | [readme](docs/controllers/form/autoselect.md)        |
| [Autosubmit](docs/controllers/form/autosubmit.md)               | `form--autosubmit`        | —            | [readme](docs/controllers/form/autosubmit.md)        |
| [Clean Querystring](docs/controllers/form/clean-querystring.md) | `form--clean-querystring` | —            | [readme](docs/controllers/form/clean-querystring.md) |
| [Clear Input](docs/controllers/form/clear-input.md)             | `form--clear-input`       | —            | [readme](docs/controllers/form/clear-input.md)       |
| [Remote](docs/controllers/form/remote.md)                       | `form--remote`            | —            | [readme](docs/controllers/form/remote.md)            |
| [Reset Files](docs/controllers/form/reset-files.md)             | `form--reset-files`       | —            | [readme](docs/controllers/form/reset-files.md)       |
| [Textarea Autogrow](docs/controllers/form/textarea-autogrow.md) | `form--textarea-autogrow` | —            | [readme](docs/controllers/form/textarea-autogrow.md) |
| [Unsaved Changes](docs/controllers/form/unsaved-changes.md)     | `form--unsaved-changes`   | —            | [readme](docs/controllers/form/unsaved-changes.md)   |

### Frame

| Controller                                                   | Identifier               | Dependencies      | Docs                                                |
|--------------------------------------------------------------|--------------------------|-------------------|-----------------------------------------------------|
| [Polling](docs/controllers/frame/polling.md)                 | `frame--polling`         | `@hotwired/turbo` | [readme](docs/controllers/frame/polling.md)         |
| [Progress](docs/controllers/frame/progress.md)               | `frame--progress`        | `@hotwired/turbo` | [readme](docs/controllers/frame/progress.md)        |
| [View Transition](docs/controllers/frame/view-transition.md) | `frame--view-transition` | —                 | [readme](docs/controllers/frame/view-transition.md) |

### Dev

| Controller                         | Identifier | Dependencies | Docs                                  |
|------------------------------------|------------|--------------|---------------------------------------|
| [Log](docs/controllers/dev/log.md) | `dev--log` | —            | [readme](docs/controllers/dev/log.md) |

### Lib

| Controller                             | Identifier   | Dependencies | Docs                                    |
|----------------------------------------|--------------|--------------|-----------------------------------------|
| [GTM](docs/controllers/lib/gtm.md)     | `lib--gtm`   | —            | [readme](docs/controllers/lib/gtm.md)   |
| [Maska](docs/controllers/lib/maska.md) | `lib--maska` | `maska`      | [readme](docs/controllers/lib/maska.md) |
| [Tippy](docs/controllers/lib/tippy.md) | `lib--tippy` | `tippy.js`   | [readme](docs/controllers/lib/tippy.md) |

### Media

| Controller                                   | Identifier       | Dependencies | Docs                                        |
|----------------------------------------------|------------------|--------------|---------------------------------------------|
| [OEmbed](docs/controllers/media/oembed.md)   | `media--oembed`  | —            | [readme](docs/controllers/media/oembed.md)  |
| [Pending](docs/controllers/media/pending.md) | `media--pending` | —            | [readme](docs/controllers/media/pending.md) |

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ednilson Maia](https://github.com/emaia)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
