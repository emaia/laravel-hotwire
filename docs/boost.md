# Laravel Boost

Laravel Hotwire ships package guidelines and agent skills for
[Laravel Boost](https://laravel.com/docs/boost). They give AI agents a concise map of the package while keeping
`hotwire:docs` and the versioned documentation as the exhaustive API reference.

## Installation

Install Boost in the application, then run its installer:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Select `emaia/laravel-hotwire` when Boost asks which third-party guidelines and skills to install. Boost only installs
third-party assets from packages selected in that prompt.

## Included guidance

The always-loaded guideline provides the configured component prefix, the current component/controller catalog and the
few rules needed to avoid invalid package usage.

Four focused skills load on demand:

- `laravel-hotwire-forms` - fields, controls, validation, filters, conditional fields and uploads.
- `laravel-hotwire-turbo-workflows` - Frames, Streams, morphing, cache and optimistic updates.
- `laravel-hotwire-ui-development` - UI composition, overlays, accessibility, themes and presets.
- `laravel-hotwire-stimulus-controllers` - writing, extending and publishing Stimulus controllers.

The forms and UI skills also install focused references for control selection and styling. Blade-backed assets resolve
`config('hotwire.prefix')` while Boost installs them, so generated examples use the application's actual component
prefix instead of assuming `hw`.

## Verification

Use the package commands before accepting generated code:

```bash
php artisan hotwire:docs
php artisan hotwire:components
php artisan hotwire:check
```

Re-run `php artisan boost:install` after changing the selected third-party packages or when you need to regenerate the
installed guidance.
