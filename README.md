[![Latest Version on Packagist](https://img.shields.io/packagist/v/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/emaia/laravel-hotwire/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/emaia/laravel-hotwire/actions?query=workflow%3A"Fix+PHP+Code+Style+Issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/emaia/laravel-hotwire.svg?style=flat-square)](https://packagist.org/packages/emaia/laravel-hotwire)

# Laravel Hotwire

Laravel Hotwire is a server-driven UI toolkit for Laravel applications. It combines Turbo Drive, Turbo Frames, Turbo
Streams, Stimulus controllers and Blade components so you can build fast, reactive interfaces with server-rendered HTML
and focused client-side behavior.

## Requirements

Laravel Hotwire requires PHP 8.3+, Laravel 12+, Vite, Tailwind CSS v4 and the DOM, libxml and mbstring PHP extensions.

## Installation

Install the package with Composer:

```bash
composer require emaia/laravel-hotwire
```

Then scaffold the JavaScript, CSS and package dependencies:

```bash
php artisan hotwire:install
```

The installer adds Stimulus, Turbo, `@emaia/stimulus-lazy-loader`, controller-specific npm dependencies, the `@hotwire`
Vite alias, the controller loader, CSS preset imports and Laravel Idea metadata. Components work immediately after
installation. You only publish controllers when you want to customize their source.

Publish configuration only when you need to change the component prefix or controller loading policy:

```bash
php artisan vendor:publish --tag=hotwire-config
```

For lean installs, CI flags and loader details, see [Advanced installation](docs/installation.md).

## Documentation

|                                                                     |                                                                                   |
| ------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| [**Basic usage**](#components)                                      | Blade components and auto-loaded Stimulus controllers                             |
| [**Components**](#components)                                       | Composable Blade primitives for forms, overlays, navigation, feedback and display |
| [**Controllers**](#controllers)                                     | Standalone Stimulus behavior with direct links to each controller contract        |
| [**Turbo Streams**](https://github.com/emaia/laravel-hotwire-turbo) | Request detection, DOM helpers and fluent stream responses                        |
| [**Frame-backed modals**](docs/recipes/server-driven-modals.md)     | Shared modal hosts driven by Turbo Frames and regular Blade responses             |
| [**Stimulus helpers**](docs/stimulus-helpers.md)                    | Fluent helpers for controllers, actions, targets, values, classes and outlets     |
| [**Styling and theming**](docs/theming.md)                          | Semantic tokens, preset hooks, dark mode and application overrides                |
| [**Extending controllers**](docs/extending-controllers.md)          | Subclass package controllers or publish their source for customization            |
| [**Recipes**](docs/recipes/readme.md)                               | Practical patterns for Turbo, overlays, forms, streams and component composition  |
| [**Registry**](docs/registry.md)                                    | Catalog metadata, categories, dependencies, docs paths and styling hooks          |
| [**Advanced installation**](docs/installation.md)                   | Lean installs, critical controller loading, CI flags and loader details           |
| [**Upgrade guide**](docs/upgrade.md)                                | Version-specific migration notes and compatibility changes                        |

Browse the same catalog from the terminal:

```bash
php artisan hotwire:docs
php artisan hotwire:docs modal --component
php artisan hotwire:docs auto-submit
```

## Components

Laravel Hotwire ships composable Blade components for common server-rendered UI patterns:

|                                                               |                                                         |                                                        |                                                           |
| ------------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------ | --------------------------------------------------------- |
| [Accordion](docs/components/accordion.md)                     | [Alert](docs/components/alert.md)                       | [Alert Dialog](docs/components/alert-dialog.md)        | [Aspect Ratio](docs/components/aspect-ratio.md)           |
| [Attachment](docs/components/attachment.md)                   | [Avatar](docs/components/avatar.md)                     | [Back to Top](docs/components/back-to-top.md)          | [Badge](docs/components/badge.md)                         |
| [Breadcrumb](docs/components/breadcrumb.md)                   | [Button](docs/components/button.md)                     | [Button Group](docs/components/button-group.md)        | [Card](docs/components/card.md)                           |
| [Carousel](docs/components/carousel.md)                       | [Chart](docs/components/chart.md)                       | [Checkbox](docs/components/checkbox.md)                | [Checkbox Group](docs/components/checkbox-group.md)       |
| [Checkbox Group Item](docs/components/checkbox-group.md)      | [Color Scheme Script](docs/components/color-scheme.md)  | [Color Scheme Toggle](docs/components/color-scheme.md) | [Conditional Field](docs/components/conditional-field.md) |
| [Controller Preloads](docs/components/controller-preloads.md) | [Drawer](docs/components/drawer.md)                     | [Dropdown](docs/components/dropdown.md)                | [Empty State](docs/components/empty-state.md)             |
| [Field](docs/components/field.md)                             | [Field Error](docs/components/field.md)                 | [Field Group](docs/components/field.md)                | [Field Label](docs/components/field.md)                   |
| [File](docs/components/file.md)                               | [File Upload](docs/components/file-upload.md)           | [Form](docs/components/form.md)                        | [Frame](docs/components/frame.md)                         |
| [Frame or Page](docs/components/frame-or-page.md)             | [Frame or Page Frame](docs/components/frame-or-page.md) | [Frame or Page Page](docs/components/frame-or-page.md) | [Hover Card](docs/components/hover-card.md)               |
| [Icon](docs/components/icon.md)                               | [Input](docs/components/input.md)                       | [Input Group](docs/components/input-group.md)          | [Item](docs/components/item.md)                           |
| [Kbd](docs/components/kbd.md)                                 | [Map](docs/components/map.md)                           | [Marker](docs/components/marker.md)                    | [Meta](docs/components/meta.md)                           |
| [Meta Cache](docs/components/meta.md)                         | [Meta Color Scheme](docs/components/meta.md)            | [Meta CSRF](docs/components/meta.md)                   | [Meta Prefetch](docs/components/meta.md)                  |
| [Meta Refresh](docs/components/meta.md)                       | [Meta Root](docs/components/meta.md)                    | [Meta View Transition](docs/components/meta.md)        | [Meta Visit Control](docs/components/meta.md)             |
| [Modal](docs/components/modal.md)                             | [Multi Select](docs/components/multi-select.md)         | [Navbar](docs/components/navbar.md)                    | [Navbar Item](docs/components/navbar.md)                  |
| [Optimistic](docs/components/optimistic.md)                   | [Pagination](docs/components/pagination.md)             | [Popover](docs/components/popover.md)                  | [Progress](docs/components/progress.md)                   |
| [Radio Group](docs/components/radio-group.md)                 | [Radio Group Item](docs/components/radio-group.md)      | [Read More](docs/components/read-more.md)              | [Reveal](docs/components/reveal.md)                       |
| [Reveal Item](docs/components/reveal.md)                      | [Rich Text](docs/components/rich-text.md)               | [Scroll Progress](docs/components/scroll-progress.md)  | [Select](docs/components/select.md)                       |
| [Separator](docs/components/separator.md)                     | [Sheet](docs/components/sheet.md)                       | [Side Panel](docs/components/side-panel.md)            | [Sidebar](docs/components/sidebar.md)                     |
| [Skeleton](docs/components/skeleton.md)                       | [Slider](docs/components/slider.md)                     | [Spinner](docs/components/spinner.md)                  | [Sticky](docs/components/sticky.md)                       |
| [Switch](docs/components/switch.md)                           | [Table](docs/components/table.md)                       | [Tabs](docs/components/tabs.md)                        | [Textarea](docs/components/textarea.md)                   |
| [Timeago](docs/components/timeago.md)                         | [Toast](docs/components/toast.md)                       | [Toaster](docs/components/toaster.md)                  | [Toggle](docs/components/toggle.md)                       |
| [Toggle Group](docs/components/toggle-group.md)               | [Toggle Group Item](docs/components/toggle-group.md)    |                                                        |                                                           |

List everything available in your installed version:

```bash
php artisan hotwire:components
php artisan hotwire:docs --list --component
```

## Controllers

Package controllers auto-load from the vendor directory after `hotwire:install`. Use them directly with
`data-controller`, or through the Blade components that mount them for you.

Standalone controllers include:

|                                                            |                                                                    |                                                              |                                                                    |
| ---------------------------------------------------------- | ------------------------------------------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------------ |
| [Accordion](docs/controllers/accordion.md)                 | [Alert Dialog](docs/controllers/alert-dialog.md)                   | [Animated Number](docs/controllers/animated-number.md)       | [Auto Resize](docs/controllers/auto-resize.md)                     |
| [Auto Save](docs/controllers/auto-save.md)                 | [Auto Select](docs/controllers/auto-select.md)                     | [Auto Submit](docs/controllers/auto-submit.md)               | [Autofocus](docs/controllers/autofocus.md)                         |
| [Back to Top](docs/controllers/back-to-top.md)             | [Carousel](docs/controllers/carousel.md)                           | [Char Counter](docs/controllers/char-counter.md)             | [Chart](docs/controllers/chart.md)                                 |
| [Checkbox](docs/controllers/checkbox.md)                   | [Checkbox Select All](docs/controllers/checkbox-select-all.md)     | [Clean Query Params](docs/controllers/clean-query-params.md) | [Clear Input](docs/controllers/clear-input.md)                     |
| [Color Scheme](docs/controllers/color-scheme.md)           | [Conditional Fields](docs/controllers/conditional-fields.md)       | [Copy to Clipboard](docs/controllers/copy-to-clipboard.md)   | [Dev Log](docs/controllers/dev/log.md)                             |
| [Disclosure](docs/controllers/disclosure.md)               | [Drawer](docs/controllers/drawer.md)                               | [Dropdown](docs/controllers/dropdown.md)                     | [Error Scroll](docs/controllers/error-scroll.md)                   |
| [File Preserve](docs/controllers/file-preserve.md)         | [File Upload](docs/controllers/file-upload.md)                     | [GTM](docs/controllers/gtm.md)                               | [Hotkey](docs/controllers/hotkey.md)                               |
| [Hover Card](docs/controllers/hover-card.md)               | [Input Mask](docs/controllers/input-mask.md)                       | [Lazy Image](docs/controllers/lazy-image.md)                 | [Map](docs/controllers/map.md)                                     |
| [Modal](docs/controllers/modal.md)                         | [Modal Auto Close](docs/controllers/modal-auto-close.md)           | [Money Input](docs/controllers/money-input.md)               | [Multi Select](docs/controllers/multi-select.md)                   |
| [OEmbed](docs/controllers/oembed.md)                       | [Optimistic Dispatch](docs/controllers/optimistic/dispatch.md)     | [Optimistic Form](docs/controllers/optimistic/form.md)       | [Optimistic Link](docs/controllers/optimistic/link.md)             |
| [Pagination](docs/controllers/pagination.md)               | [Password Visibility](docs/controllers/password-visibility.md)     | [Popover](docs/controllers/popover.md)                       | [Read More](docs/controllers/read-more.md)                         |
| [Remote Form](docs/controllers/remote-form.md)             | [Reset Files](docs/controllers/reset-files.md)                     | [Reveal](docs/controllers/reveal.md)                         | [Rich Text](docs/controllers/rich-text.md)                         |
| [Rich Text Toolbar](docs/controllers/rich-text-toolbar.md) | [Scroll Progress](docs/controllers/scroll-progress.md)             | [Sheet](docs/controllers/sheet.md)                           | [Side Panel](docs/controllers/side-panel.md)                       |
| [Sidebar](docs/controllers/sidebar.md)                     | [Slider](docs/controllers/slider.md)                               | [Slug](docs/controllers/slug.md)                             | [Tabs](docs/controllers/tabs.md)                                   |
| [Timeago](docs/controllers/timeago.md)                     | [Toast](docs/controllers/toast.md)                                 | [Toaster](docs/controllers/toaster.md)                       | [Toggle](docs/controllers/toggle.md)                               |
| [Toggle Group](docs/controllers/toggle-group.md)           | [Tooltip](docs/controllers/tooltip.md)                             | [Turbo Frame Src](docs/controllers/turbo/frame-src.md)       | [Turbo Morph Guard](docs/controllers/turbo/morph-guard.md)         |
| [Turbo Polling](docs/controllers/turbo/polling.md)         | [Turbo Preserve Scroll](docs/controllers/turbo/preserve-scroll.md) | [Turbo Progress](docs/controllers/turbo/progress.md)         | [Turbo View Transition](docs/controllers/turbo/view-transition.md) |
| [Unsaved Changes](docs/controllers/unsaved-changes.md)     |                                                                    |                                                              |                                                                    |

Publish a package controller only when you want to customize its source:

```bash
php artisan hotwire:controllers carousel
php artisan hotwire:controllers --list
php artisan hotwire:controllers --outdated --force
```

## Styling

`hotwire:install` configures the default preset and Tailwind source scan.

Override semantic hooks after the preset:

```css
[data-slot="button"][data-variant="default"] {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
```

Or generate an application-owned preset:

```bash
php artisan hotwire:make-preset brand
php artisan hotwire:make-preset brand --from=nova
```

See [Presets](docs/presets.md) and [Theming](docs/theming.md).

## Check Your Setup

Check the controller loader, npm dependencies and published customizations:

```bash
php artisan hotwire:check
php artisan hotwire:check --fix
php artisan hotwire:check --fix --skip-install
```

`hotwire:check --fix` regenerates the controller loader and adds missing npm dependencies. By default it also runs the
detected package manager install command; use `--skip-install` when CI handles that separately.

## Development

```bash
composer test
composer analyse
bun run test
bun run test:css
bun run test:browser
composer format
```

`bun run build:css` compiles every public preset plus the selective fixture and reports raw/gzip sizes. After an
intentional output change, refresh the committed size baseline with `bun run test:css:update`.

The registry in [`src/Registry/catalog.php`](src/Registry/catalog.php) is the source of truth for package components,
controllers, npm dependencies, docs paths and styling hooks. Update it whenever you add or rename a package component or
controller.

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
