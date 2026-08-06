# Registry

The registry is the single source of truth for everything the package exposes publicly:

- Blade components
- Stimulus controllers
- npm dependencies
- visual and structural `data-slot` hooks
- preset-supported variant and size values
- documentation paths
- categories

It lives in [`src/Registry/catalog.php`](../src/Registry/catalog.php) and is consumed by every command and the service
provider — so editing the catalog is the only change needed to register a new component or controller.

## Catalog entries

### Component

```php
'modal' => [
    'class'       => \Emaia\LaravelHotwire\Components\Modal::class,
    'view'        => 'hotwire::component-views.modal',
    'docs'        => 'docs/components/modal.md',
    'category'    => 'overlay',
    'controllers' => ['modal'],
    'styling'     => [
        'slots' => [
            'modal'         => 'structural',
            'modal-overlay' => 'visual',
            'modal-panel'   => 'visual',
        ],
        'sizes' => [
            'modal-positioner' => ['sm', 'md', 'lg', 'xl', 'full', 'auto'],
            'modal-panel'      => ['full'],
        ],
    ],
],
```

| Key           | Description                                               |
|---------------|-----------------------------------------------------------|
| `class`       | PHP component class                                       |
| `view`        | Blade view name                                           |
| `docs`        | Relative path to the component's doc file                 |
| `category`    | Public category (see [Categories](#categories))           |
| `controllers` | Controller keys required by this component                |
| `styling`     | The styling surface this entry contributes (see below)    |

### Styling

`styling` groups everything a preset needs to know about an entry. It hydrates into
[`Registry\Styling`](../src/Registry/Styling.php), which exposes `visualSlots()`, `structuralSlots()` and
`axesFor($slot)`.

| Key        | Description                                                                   |
|------------|-------------------------------------------------------------------------------|
| `slots`    | Package-emitted slot names mapped to `visual` or `structural`                 |
| `variants` | Optional map of slot → `data-variant` values that slot's appearance varies by |
| `sizes`    | Optional map of slot → `data-size` values that slot's appearance varies by    |

Structural slots are containers, assistive nodes or geometry a controller stylesheet already owns; presets are not
expected to style them, and `hotwire:make-preset` leaves them out of the scaffold.

`variants` and `sizes` are keyed by slot, not by entry — a value belongs to the slot that carries the attribute. They
list what a preset differentiates, so values that need no rule of their own are omitted: `default` is the slot's base
rule by definition, and some semantic values are already covered by it.

Everything under `styling` is verified against every shipped preset in
[`tests/Registry/SlotCatalogTest.php`](../tests/Registry/SlotCatalogTest.php). A preset may not style a value the
catalog never declares, and a declared value must be styled somewhere or recorded as rule-free in that test.

### Controller

```php
'tooltip' => [
    'source'   => 'resources/js/controllers/tooltip_controller.js',
    'docs'     => 'docs/controllers/tooltip.md',
    'category' => 'utility',
    'npm'      => ['@floating-ui/dom' => '^1.8.0'],
    'styling'  => [
        'slots' => [
            'tooltip'       => 'visual',
            'tooltip-arrow' => 'visual',
        ],
    ],
],
```

| Key        | Description                                                              |
|------------|--------------------------------------------------------------------------|
| `source`   | Path to the controller file, relative to the package root                |
| `docs`     | Relative path to the controller's doc file                               |
| `category` | Public category                                                          |
| `npm`      | External npm packages required at runtime (package → version constraint) |
| `styling`  | Same shape as a component's, for controllers that build their own DOM    |

A controller declares `styling` only when it creates elements itself — `tooltip` builds its tooltip and arrow in
JavaScript, so no Blade view emits those slots and nothing else would put them in the inventory.

Controllers inside substrate folders use `/` in the key: `'turbo/progress'`.  
The identifier is derived automatically: `/` → `--`, `_` → `-`.

## Adding a new component

1. Create the PHP class in `src/Components/` and the Blade view in `resources/views/component-views/`.
2. Add the component entry to `catalog.php`. Reference every required Stimulus controller and declare every emitted
   slot under `styling`. Include slots from package subcomponents that belong to the component family.
3. If new controllers are needed, add their entries too (see [Adding a new controller](#adding-a-new-controller)).
4. Create `tests/Components/<Name>Test.php` covering rendering and props (follow `tests/Components/ModalTest.php` as
   reference).
5. Create `docs/components/<name>.md`.
6. Run `composer test`.

## Adding a new controller

1. Create the controller file in `resources/js/controllers/` (`{name}_controller.{js|ts}`).
2. Add the controller entry to `catalog.php`. Declare any external npm packages in `npm` and, if the controller
   builds its own DOM, the slots it creates under `styling`.
3. Create `tests/Controllers/<name>_controller.test.js` covering the controller's behavior (follow
   `tests/Controllers/auto_save_controller.test.js` as reference).
4. Create `docs/controllers/<name>.md`.
5. Run `bun test`.

## Categories

| Category   | Used for                                              |
|------------|-------------------------------------------------------|
| `overlay`  | Components that layer over the page (modals, dialogs) |
| `feedback` | User notifications and status (flash, loaders)        |
| `forms`    | Form behavior (submit, save, masks, validation UX)    |
| `turbo`    | Controllers tied to Turbo Drive / Turbo Frames        |
| `utility`  | General-purpose DOM helpers                           |
| `dev`      | Development-only tools                                |
