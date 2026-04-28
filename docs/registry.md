# Registry

The registry is the single source of truth for everything the package exposes publicly:

- Blade components
- Stimulus controllers
- npm dependencies
- documentation paths
- categories

It lives in [`src/Registry/catalog.php`](../src/Registry/catalog.php) and is consumed by every command and the service provider — so editing the catalog is the only change needed to register a new component or controller.

## Catalog entries

### Component

```php
'modal' => [
    'class'       => \Emaia\LaravelHotwire\Components\Modal::class,
    'view'        => 'hotwire::components.modal.modal',
    'docs'        => 'docs/components/modal.md',
    'category'    => 'overlay',
    'controllers' => ['modal'],
],
```

| Key | Description |
|---|---|
| `class` | PHP component class |
| `view` | Blade view name |
| `docs` | Relative path to the component's doc file |
| `category` | Public category (see [Categories](#categories)) |
| `controllers` | Controller keys required by this component |

### Controller

```php
'tooltip' => [
    'source'   => 'resources/js/controllers/tooltip_controller.js',
    'docs'     => 'docs/controllers/tooltip.md',
    'category' => 'utility',
    'npm'      => ['tippy.js' => '^6.3.7'],
],
```

| Key | Description |
|---|---|
| `source` | Path to the controller file, relative to the package root |
| `docs` | Relative path to the controller's doc file |
| `category` | Public category |
| `npm` | External npm packages required at runtime (package → version constraint) |

Controllers inside substrate folders use `/` in the key: `'turbo/progress'`.  
The identifier is derived automatically: `/` → `--`, `_` → `-`.

## Adding a new component

1. Create the PHP class in `src/Components/` and the Blade view in `resources/views/components/`.
2. Add the component entry to `catalog.php`. Reference every required Stimulus controller by key.
3. If new controllers are needed, add their entries too (see [Adding a new controller](#adding-a-new-controller)).
4. Create `tests/Components/<Name>Test.php` covering rendering and props (follow `tests/Components/ModalTest.php` as reference).
5. Create `docs/components/<name>.md`.
6. Run `composer test`.

## Adding a new controller

1. Create the controller file in `resources/js/controllers/` (`{name}_controller.{js|ts}`).
2. Add the controller entry to `catalog.php`. Declare any external npm packages in `npm`.
3. Create `tests/Controllers/<name>_controller.test.js` covering the controller's behavior (follow `tests/Controllers/auto_save_controller.test.js` as reference).
4. Create `docs/controllers/<name>.md`.
5. Run `bun test`.

## Categories

| Category | Used for |
|---|---|
| `overlay` | Components that layer over the page (modals, dialogs) |
| `feedback` | User notifications and status (flash, loaders) |
| `forms` | Form behavior (submit, save, masks, validation UX) |
| `turbo` | Controllers tied to Turbo Drive / Turbo Frames |
| `utility` | General-purpose DOM helpers |
| `dev` | Development-only tools |
