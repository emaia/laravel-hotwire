# Registry

Laravel Hotwire keeps its public catalog in a single registry:

- Blade components
- Stimulus controllers
- npm dependencies
- documentation paths
- categories

The registry lives in [`src/Registry/catalog.php`](../src/Registry/catalog.php).

## Why it exists

The package needs the same metadata in multiple places:

- `hotwire:components`
- `hotwire:check`
- `hotwire:controllers --list`
- Blade component registration
- documentation integrity tests

Before the registry, that information was spread across multiple files and command implementations. Now the package has
one source of truth.

## What belongs in the registry

Use the registry for **public package metadata**, not behavior.

Good fit:

- component key and class
- controller identifier and source file
- external npm dependencies
- docs path
- category

Do not put business logic in the registry.

## Component entry

```php
'dialog' => [
    'class' => \Emaia\LaravelHotwire\Components\Dialog::class,
    'view' => 'hotwire::components.dialog.dialog',
    'docs' => 'docs/components/dialog/readme.md',
    'category' => 'overlay',
    'controllers' => ['dialog'],
],
```

## Controller entry

```php
'tooltip' => [
    'source' => 'resources/js/controllers/tooltip_controller.js',
    'docs' => 'docs/controllers/tooltip.md',
    'category' => 'utility',
    'npm' => ['tippy.js' => '^6.3.7'],
],
```

## How to use it when working on the package

When you add a new component:

1. Create the PHP component and Blade view.
2. Add the component entry to `src/Registry/catalog.php`.
3. Add every required Stimulus controller to the same catalog.
4. Add docs for the component and controllers.
5. Run:

```bash
composer test
php artisan hotwire:components
php artisan hotwire:controllers --list
```

When you add a new standalone Stimulus controller:

1. Create the controller file in `resources/js/controllers`.
2. Add it to `src/Registry/catalog.php`.
3. Declare external npm packages in the controller entry.
4. Add docs.
5. Verify with:

```bash
php artisan hotwire:controllers --list
php artisan hotwire:check --path=resources/views
```

## How apps benefit from the registry

Application code does not interact with the registry directly. Instead, use the public commands:

```bash
# See every Hotwire Blade component and the controllers it needs
php artisan hotwire:components

# See every publishable controller and its publish status
php artisan hotwire:controllers --list

# Check the components used in your views and fix missing pieces
php artisan hotwire:check --fix
```

Example:

```blade
<x-hwc::flash-container />
<x-hwc::flash-message />
```

Then:

```bash
php artisan hotwire:check --fix
```

This will:

- publish `toast` and `toaster` if needed
- add `@emaia/sonner` to `package.json` if missing

## Categories

Current public categories:

- `overlay`
- `feedback`
- `forms`
- `turbo`
- `utility`
- `dev`
