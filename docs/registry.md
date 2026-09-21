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
where each official preset declares an ordered `base` list and maps logical modules to private sources in canonical
cascade order. Base sources always precede modules and remain included for an empty module selection. A module source may
cover several modules and may be nested within the preset's private directory; no private path or grouping is a
cross-preset contract. The public CSS surfaces are the top-level `resources/css/presets/<name>.css` entrypoints and the
shared `resources/css/foundation.css` facade.

## Preset token contract

`styles.php` declares the shared foundation token contract once and each official preset declares only what it adds:

```php
'foundation' => [
    'properties' => [
        '--background' => 'themed',
        '--foreground' => 'themed',
        '--radius' => 'global',
    ],
    'aliases' => ['--color-background' => '--background'],
    'contrast_pairs' => [
        'background' => [
            'foreground' => '--foreground',
            'background' => '--background',
        ],
    ],
],
'presets' => [
    'example' => [
        'base' => ['presets/example/theme.css'],
        'properties' => [
            '--status' => 'themed',
            '--status-foreground' => 'themed',
            '--panel-radius' => 'global',
        ],
        'aliases' => ['--color-status' => '--status'],
        'contrast_pairs' => [
            'status' => [
                'foreground' => '--status-foreground',
                'background' => '--status',
            ],
        ],
        'sources' => [/* ... */],
    ],
],
```

The `properties` map uses full CSS custom-property names as keys and assigns each name introduced at that level its
declaration scope. A `global` property requires a top-level `:root` declaration. A `themed` property requires all three
top-level theme selectors: the unthemed default `:where(:root:not([data-theme="dark"]))`, explicit
`[data-theme="light"]` and explicit `[data-theme="dark"]`. CSS inheritance does not satisfy a missing default, light or
dark declaration: a computed value inherited from `:root` or an ancestor can hide an incomplete theme from browser-only
checks.

These owner contracts are mutually exclusive. A `global` property must not also appear in a theme scope, and a `themed`
property must not appear in `:root`. In the latter case, `:root` has greater specificity than the zero-specificity
unthemed default and would make that default declaration dead even if every required selector were present.

`aliases` maps each property emitted by Tailwind's `@theme inline` to the registered property it references; aliases are
optional for preset knobs. Every alias value must resolve to exactly one distinct `var(--...)` target. Static values and
expressions referencing different properties are not aliases in this contract.
`contrast_pairs` names explicit foreground/background roles. The manifest does not infer aliases, foregrounds or pairs
from stems because valid semantic relationships need not follow a naming convention.

Every official preset inherits the foundation properties, aliases and pairs even when all three preset additions are
empty. A preset cannot redeclare a foundation-owned name or pair. Its additional properties must occur in its complete
ordered `base`, and every additional alias must occur in `@theme inline` with the registered target. Base sources may
override values in cascade order, including shared properties, but an unregistered additional name is an ownership
error. A foundation-owned property declared by a preset base is an optional override rather than a new property: it may
target any subset allowed by its inherited classification. A themed override may use `default`, `light` and/or `dark`
without providing all three; a global override may use only `root`. Both inherit the foundation value wherever they are
not declared. Overrides do not require theme symmetry; do not register their names again under the preset. Package
validation reads CSS blocks structurally so formatting, multiple base files and nested function values do not weaken the
contract or create scope through name inference.

This metadata describes shipped package presets. An application-owned scaffold or clone may declare its own properties
after the import, and `hotwire:check --preset` does not claim to recover official provenance or diagnose arbitrary
unresolved application tokens.

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
    'description' => 'Inline alert with title, description, icon, action and semantic variants',
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
| `description` | Public discovery and search summary                        |
| `controllers` | Controller keys required by this component                |
| `styling`     | References to the styling surface this entry contributes  |

### Styling

`styling` groups everything a preset needs to know about an entry. Its family references hydrate into
[`Registry\Styling`](../src/Registry/Styling.php), preserving the existing `visualSlots()` and `structuralSlots()` query
API.

| Key     | Description                                                                      |
|---------|----------------------------------------------------------------------------------|
| `slots` | Ordered family references, each with `class` and optional local-key list `only` |
| `preset_properties` | Required custom properties by slot, mapped to neutral scaffold values |

References can combine declarations from multiple families. Use `only` when an entry owns a defined subset:

```php
'slots' => [
    ['class' => Alert::class, 'only' => ['action']],
]
```

The resolver reads class constants through reflection and never constructs a component, renders a view or resolves the
container. Missing local keys and conflicting `visual`/`structural` classifications are rejected. Package component
families use class declarations so the projection retains each slot's declaring family and scaffolds group shared slots
under their owner rather than whichever consumer happens to appear first.

Structural slots are containers, assistive nodes or geometry the shared structural stylesheet already owns; presets are not
expected to style them, and `hotwire:make-preset` leaves them out of the scaffold.

Use `preset_properties` only when structural CSS consumes a value that every complete preset must define. The registry
value is the neutral declaration emitted by `hotwire:make-preset`; `hotwire:check` verifies that application presets keep
the property on the named slot. For example, Sidebar scaffolds zero inset and edge contributions so its icon geometry
remains valid before the preset author chooses a floating treatment. Those neutral zeros retain the units consumed by
the structural calculation (`0rem` for inset and `0px` for edge), so each custom property remains a length when combined
with Sidebar widths through `calc()`.

## Slots and controller targets

`data-slot` names identifier-independent anatomy. Components, presets and registry queries use those names regardless of
which Stimulus identifier controls an element. A `data-{identifier}-target` attribute is controller wiring: its name and
scope belong to that specific controller instance. An element may carry both, but they are not interchangeable.

Controllers should find and validate behavior through their targets or dedicated `data-{identifier}-*` markers. Presets
should style declared slots and documented state rather than infer visual ownership from target names. Markers such
as `data-tooltip-surface` and `data-toaster-card` identify runtime parts to JavaScript; they do not replace the
corresponding family slots or make those parts controller-owned visual anatomy.

The values a slot varies by are deliberately **not** declared here. `Support\PresetAxes` can report the attributes one
stylesheet happens to differentiate, but that output is lexical diagnostic metadata rather than the public value
vocabulary or a conformance contract. A base rule may cover every value, and several values may intentionally share an
appearance.

The extractor recognizes literal equality and valueless attributes written after the slot selector in the same compound,
including `aria-expanded`, `aria-invalid`, `type` and the `open` an Accordion `<details>` carries. Attribute order is
therefore another lexical limit rather than a semantic rule. A nested rule that names no slot of its own, such as
`&[data-variant="ghost"]`, is read against the slot it refines. It also recognizes selected unprefixed Tailwind
`data-*` and `aria-*` variants inside a rule. It intentionally leaves out operator selectors such as `[class*="size-"]`,
pseudo-class states (`hover:`, `disabled:`, `focus-visible:`), negated variants and relational variants such as
`group-*`, `peer-*`, `has-*` or arbitrary descendant selectors.

A value belongs to the slot in whose compound it is written, so `[data-slot="sidebar"][data-collapsible="icon"]
[data-slot="sidebar-content"]` reports the attribute on `sidebar`. This remains useful when inspecting a stylesheet,
but official presets are not required to expose identical lexical axes.

`PresetAxes::coverage()` reports parser coverage: whether the scanner visited every `[data-slot=…]` selector and
`data-[slot=…]` Tailwind variant it counted. It does not prove semantic coverage, state support, accessibility or
compatibility with the component contract. `PresetAxes::inspectCoverage()` additionally reports structural validity
and the identifiable slot names the parser could not visit, separating complete references from incomplete syntax.

`Support\PresetSkeleton` does not use `PresetAxes` or parse an official preset. It emits one base rule for each visual
slot projected by the registry; rules stay empty unless the slot declares `preset_properties`. Ancestor state,
equivalent selectors and Tailwind variants remain authoring decisions documented by the component contract and
implementation examples.

Slot declarations are verified against every shipped preset in
[`tests/Registry/SlotCatalogTest.php`](../tests/Registry/SlotCatalogTest.php): every visual slot must participate in a
rule with declarations. Focused structural, behavioral and accessibility tests cover contracts that lexical slot
occurrence cannot prove.

`hotwire:check --preset=<name|path>` projects the same visual-slot inventory from the registry when validating a complete
application preset. It does not use `styles.php`, Nova selectors or `PresetAxes` vocabulary as a completeness baseline.
Generated selective bundles remain governed by `styles.php` module ownership and their recorded generation plan, so a
deliberately omitted slot is valid in a bundle even though it would be an error in a complete preset.

### Controller

```php
'tooltip' => [
    'source'      => 'resources/js/controllers/tooltip_controller.js',
    'docs'        => 'docs/controllers/tooltip.md',
    'category'    => 'overlay',
    'description' => 'Creates non-interactive ARIA tooltips from templates with anchored positioning',
    'npm'         => ['@floating-ui/dom' => '^1.8.0'],
],
```

| Key           | Description                                                              |
|---------------|--------------------------------------------------------------------------|
| `source`      | Path to the controller file, relative to the package root                |
| `docs`        | Relative path to the controller's doc file                               |
| `category`    | Public category                                                          |
| `description` | Public discovery and search summary                                      |
| `npm`         | External npm packages required at runtime (package → version constraint) |
| `styling`     | Structural slots emitted directly by controller behavior                 |

Package controllers do not own visual slots. Tooltip accepts an application-owned standalone template while
`<hw:tooltip>` owns the package-styled anatomy. The `oembed` controller emits structural, application-styled hooks for
editor content; the separate `<hw:video-embed>` component server-renders known video URLs with package styling. Toaster
follows the same ownership rule with a private component-authored card template: `Toaster::SLOTS` owns the package
visuals while the controller only clones and manages their lifecycle.

Controllers inside substrate folders use `/` in the key: `'turbo/progress'`.  
The identifier is derived automatically: `/` → `--`, `_` → `-`.

## Descriptions

Catalog descriptions are public discovery metadata. `hotwire:docs` displays them in list and interactive picker views
and includes them in keyword search.

Keep each description to one concise sentence without a trailing period. Start component descriptions with a noun phrase
that says what the rendered component provides, and controller descriptions with a present-tense verb that says what the
behavior does. Include capabilities or constraints that help someone choose the resource; omit implementation details,
lifecycle mechanics and claims already conveyed by its name or category.

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
2. Add the controller entry to `catalog.php`, declare external npm packages in `npm` and register any emitted
   presentation-free slots as structural styling.
3. If behavior needs package-styled runtime anatomy, author it in a Component Blade template, declare the component's
   slots and register that component's module ownership in `styles.php`. Keep standalone controller markup application-owned.
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
