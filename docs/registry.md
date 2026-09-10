# Registry

The registry is the public query surface for everything the package exposes:

- Blade components
- Stimulus controllers
- npm dependencies
- visual and structural `data-slot` hooks
- documentation paths
- categories

Public component and controller metadata lives in [`src/Registry/catalog.php`](../src/Registry/catalog.php). Component
families own their slot anatomy in their root class, and catalog entries project those declarations into the registry.
Visual CSS ownership and dependency closure live separately in [`src/Registry/styles.php`](../src/Registry/styles.php),
where each official preset maps those logical modules to its private sources in canonical cascade order.

## Catalog entries

### Component

The root class names each part with a local key and classifies its public `data-slot` hook:

```php
class Alert extends Component
{
    public const array SLOTS = [
        'root'        => ['name' => 'alert', 'kind' => 'visual'],
        'icon'        => ['name' => 'alert-icon', 'kind' => 'visual'],
        'title'       => ['name' => 'alert-title', 'kind' => 'visual'],
        'description' => ['name' => 'alert-description', 'kind' => 'visual'],
        'action'      => ['name' => 'alert-action', 'kind' => 'visual'],
    ];
}
```

The family entry references that declaration instead of copying its names:

```php
'alert' => [
    'class'       => \Emaia\LaravelHotwire\Components\Alert::class,
    'view'        => 'hotwire::component-views.alert',
    'docs'        => 'docs/components/alert.md',
    'category'    => 'feedback',
    'controllers' => [],
    'styling'     => [
        'slots' => [
            ['class' => \Emaia\LaravelHotwire\Components\Alert::class],
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
| `styling`     | References to the styling surface this entry contributes  |

### Styling

`styling` groups everything a preset needs to know about an entry. Its family references hydrate into
[`Registry\Styling`](../src/Registry/Styling.php), preserving the existing `visualSlots()` and `structuralSlots()` query
API.

| Key     | Description                                                                      |
|---------|----------------------------------------------------------------------------------|
| `slots` | Ordered family references, each with `class` and optional local-key list `only` |

References can combine declarations from multiple families. Use `only` when an entry owns a defined subset:

```php
'slots' => [
    ['class' => Alert::class, 'only' => ['action']],
]
```

The resolver reads class constants through reflection and never constructs a component, renders a view or resolves the
container. Missing local keys and conflicting `visual`/`structural` classifications are rejected. Component families
use class declarations; literal maps remain available for controller-created anatomy that has no component family. The
projection retains each slot's declaring family so scaffolds group shared slots under their owner rather than whichever
consumer happens to appear first.

Structural slots are containers, assistive nodes or geometry a controller stylesheet already owns; presets are not
expected to style them, and `hotwire:make-preset` leaves them out of the scaffold.

The values a slot varies by are deliberately **not** declared here. `Support\PresetAxes` can report the attributes one
stylesheet happens to differentiate, but that output is lexical diagnostic metadata rather than the public value
vocabulary or a conformance contract. A base rule may cover every value, and several values may intentionally share an
appearance.

It reads every attribute a rule matches on, not only the `data-` ones — `aria-expanded`, `aria-invalid`, `type` and the
`open` an Accordion `<details>` carries are axes too, whether they are written in the selector or as a Tailwind variant
inside the rule. Pseudo-class states (`hover:`, `disabled:`, `focus-visible:`) are not attributes and stay out.

A value belongs to the slot in whose compound it is written, so `[data-slot="sidebar"][data-collapsible="icon"]
[data-slot="sidebar-content"]` reports the attribute on `sidebar`. This remains useful when inspecting a stylesheet,
but official presets are not required to expose identical lexical axes.

`Support\PresetSkeleton` does not use `PresetAxes` or parse an official preset. It emits one empty base rule for each
visual slot projected by the registry. Ancestor state, equivalent selectors and Tailwind variants remain authoring
decisions documented by the component contract and implementation examples.

Slot declarations are verified against every shipped preset in
[`tests/Registry/SlotCatalogTest.php`](../tests/Registry/SlotCatalogTest.php): every visual slot must participate in a
rule with declarations. Focused structural, behavioral and accessibility tests cover contracts that lexical slot
occurrence cannot prove.

### Controller

```php
'tooltip' => [
    'source'   => 'resources/js/controllers/tooltip_controller.js',
    'docs'     => 'docs/controllers/tooltip.md',
    'category' => 'overlay',
    'npm'      => ['@floating-ui/dom' => '^1.8.0'],
],
```

| Key        | Description                                                              |
|------------|--------------------------------------------------------------------------|
| `source`   | Path to the controller file, relative to the package root                |
| `docs`     | Relative path to the controller's doc file                               |
| `category` | Public category                                                          |
| `npm`      | External npm packages required at runtime (package → version constraint) |
| `styling`  | Same shape as a component's, only when JavaScript itself creates visual anatomy |

A controller declares `styling` only when JavaScript itself creates visual anatomy. Tooltip accepts an
application-owned standalone template and declares no slots; `<hw:tooltip>` separately owns `Tooltip::SLOTS` and the
package visual module used by it and component integrations. Toaster follows the same ownership rule with a private
component-authored card template: `Toaster::SLOTS` owns the package visuals while the controller only clones and manages
their lifecycle.

Controllers inside substrate folders use `/` in the key: `'turbo/progress'`.  
The identifier is derived automatically: `/` → `--`, `_` → `-`.

## Adding a new component

1. Create the PHP class in `src/Components/` and the Blade view in `resources/views/component-views/`.
2. Declare every family slot once in the root component's `SLOTS` constant. Reference those local keys from child
   classes and shared views rather than copying public names.
3. Add the component entry to `catalog.php`. Reference its slot declaration and every required Stimulus controller.
   Use `only` or another explicit family reference for composed slots; do not infer ownership from aliases or CSS.
4. If it has visual slots, register its module ownership and every official preset source in `styles.php`.
5. If new controllers are needed, add their entries too (see [Adding a new controller](#adding-a-new-controller)).
6. Create `tests/Components/<Name>Test.php` covering rendering and props (follow `tests/Components/ModalTest.php` as
   reference).
7. Create `docs/components/<name>.md`.
8. Run `composer test`.

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
