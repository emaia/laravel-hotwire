# Registry

The registry is the single source of truth for everything the package exposes publicly:

- Blade components
- Stimulus controllers
- npm dependencies
- visual and structural `data-slot` hooks
- preset-supported variant and size values
- documentation paths
- categories

Public component and controller metadata lives in [`src/Registry/catalog.php`](../src/Registry/catalog.php). Visual CSS
ownership and dependency closure live separately in [`src/Registry/styles.php`](../src/Registry/styles.php), where each
official preset maps those logical modules to its private sources in canonical cascade order.

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
[`Registry\Styling`](../src/Registry/Styling.php), which exposes `visualSlots()` and `structuralSlots()`.

| Key     | Description                                                   |
|---------|---------------------------------------------------------------|
| `slots` | Package-emitted slot names mapped to `visual` or `structural` |

Structural slots are containers, assistive nodes or geometry a controller stylesheet already owns; presets are not
expected to style them, and `hotwire:make-preset` leaves them out of the scaffold.

The values a slot varies by are deliberately **not** declared here. Only a stylesheet knows them, and it knows all of
them: a slot varies by `data-orientation` because a rule says so. The preset source resolver gives
`Support\PresetAxes` the complete ordered visual CSS, so `hotwire:make-preset` documents every axis without anything
being kept in sync by hand.

It reads every attribute a rule matches on, not only the `data-` ones — `aria-expanded`, `aria-invalid`, `type` and the
`open` an Accordion `<details>` carries are axes too, whether they are written in the selector or as a Tailwind variant
inside the rule. Pseudo-class states (`hover:`, `disabled:`, `focus-visible:`) are not attributes and stay out.

A value belongs to the slot in whose compound it is written, so `[data-slot="sidebar"][data-collapsible="icon"]
[data-slot="sidebar-content"]` puts the attribute on `sidebar`, which is what the cross-preset check compares. The
scaffold does not go through the axes at all: `Support\PresetSkeleton` mirrors the shipped selectors themselves, so a
rule driven from an ancestor arrives written out rather than described.

Slot declarations are verified against every shipped preset in
[`tests/Registry/SlotCatalogTest.php`](../tests/Registry/SlotCatalogTest.php): every visual slot must be styled, and
every preset must differentiate a given slot by the same axes as the others.

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
3. If it has visual slots, register its module ownership and every official preset source in `styles.php`.
4. If new controllers are needed, add their entries too (see [Adding a new controller](#adding-a-new-controller)).
5. Create `tests/Components/<Name>Test.php` covering rendering and props (follow `tests/Components/ModalTest.php` as
   reference).
6. Create `docs/components/<name>.md`.
7. Run `composer test`.

## Adding a new controller

1. Create the controller file in `resources/js/controllers/` (`{name}_controller.{js|ts}`).
2. Add the controller entry to `catalog.php`. Declare any external npm packages in `npm` and, if the controller
   builds its own DOM, the slots it creates under `styling`.
3. If it has visual slots, register its module ownership and every official preset source in `styles.php`.
4. Create `tests/Controllers/<name>_controller.test.js` covering the controller's behavior (follow
   `tests/Controllers/auto_save_controller.test.js` as reference).
5. Create `docs/controllers/<name>.md`.
6. Run `bun test`.

## Categories

Categories are the browse facet of `hotwire:docs` — they order the `--list` table and are folded into the search
string, so an entry filed under the wrong one becomes hard to find. Components and controllers share one vocabulary,
defined by [`Registry\Category`](../src/Registry/Category.php); the catalog stores the string value and hydration
rejects anything outside the enum.

| Category     | Used for                                                                     |
|--------------|------------------------------------------------------------------------------|
| `forms`      | Form behavior — submit, save, masks, validation UX, field primitives          |
| `display`    | Content and visual primitives — cards, tables, avatars, charts, disclosure UI |
| `turbo`      | Turbo Drive, Frames, Streams and the head metas that configure them           |
| `overlay`    | Anything layered above the page — modals, dialogs, floating panels, tooltips  |
| `utility`    | General-purpose helpers with no visual identity of their own                  |
| `navigation` | Getting around the app — navbar, sidebar, breadcrumb, pagination              |
| `feedback`   | Notifications and status — toasts, alerts, loaders, progress                  |
| `dev`        | Development-only tools, never meant to reach production                       |

A component and the controller powering it belong to the same category. `tests/Registry/HotwireRegistryTest.php`
enforces this for every pair sharing a key, so a family cannot drift apart the way `accordion` once had its component
in `display` and its controller in `utility`.
