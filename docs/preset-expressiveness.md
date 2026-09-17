# Preset expressiveness study

This study stress-tests Laravel Hotwire's semantic component contract against the eight official shadcn/ui visual
styles. It asks whether substantially different visual languages can share the same Blade, DOM, controllers and
accessibility behavior. It does not propose shipping eight presets or reproducing React implementation details.

## References and method

- Laravel Hotwire architecture baseline: `457be7607b431208eccbdcc5c77abb99cf5d2ffe`
- shadcn/ui reference: [`3ba91b1cc83e1bbe4ab35a422ff2a694849c5048`](https://github.com/shadcn-ui/ui/tree/3ba91b1cc83e1bbe4ab35a422ff2a694849c5048)
- Visual corpus: `apps/v4/registry/styles/style-{vega,nova,maia,lyra,mira,luma,sera,rhea}.css`
- Curated descriptions: `apps/v4/registry/styles.tsx`
- Configuration dimensions: `apps/v4/registry/config.ts` and `packages/shadcn/src/preset/defaults.ts`

The comparison maps upstream classes and selectors to package `data-slot` parts, attributes and shared tokens. React
primitive state is relevant only when it identifies behavior or semantic state that the Hotwire implementation must
emit. Component-library classes, portals and application-specific selectors are not contracts to port.

## Style matrix

| Style | Density                    | Geometry                            | Typography                             | Surfaces and states                          | Contract implication                |
| ----- | -------------------------- | ----------------------------------- | -------------------------------------- | -------------------------------------------- | ----------------------------------- |
| Vega  | Baseline, 2.25rem controls | Moderate radius                     | Neutral sans                           | Restrained shadows and 3px focus rings       | CSS over existing parts             |
| Nova  | Compact, 2rem controls     | Soft despite tight spacing          | Small neutral sans                     | Flat/ring-led surfaces and muted sections    | CSS over existing parts             |
| Maia  | Generous spacing           | Pill controls and very large radius | Friendly sans                          | Stronger overlay and substantial controls    | CSS plus preset-local metrics       |
| Lyra  | Compact                    | Square                              | Mono-oriented and often extra small    | Flat surfaces and 1px focus rings            | CSS plus font pairing               |
| Mira  | Densest, 1.75rem controls  | Modest radius                       | Extra small                            | Compact menus and 2px focus rings            | CSS over existing parts             |
| Luma  | Spacious                   | Pill/blob geometry                  | Calm sans                              | Translucent fills, blur and strong elevation | CSS plus preset-local metrics       |
| Sera  | Spacious, 2.5rem controls  | Square                              | Editorial headings, uppercase controls | Rules, underlined controls and 2px rings     | CSS plus `--font-heading`           |
| Rhea  | Compact                    | Soft, capped radius                 | Neutral sans                           | Luma-like fills/elevation at Nova density    | CSS over existing parts             |

The eight styles collapse into four useful stress dimensions:

- **Density:** Mira/Nova versus Sera/Luma.
- **Geometry:** Lyra/Sera versus Maia/Luma.
- **Typography:** neutral sans versus Lyra mono and Sera editorial hierarchy.
- **Surface depth:** Nova/Lyra flatness versus Luma/Rhea elevation and translucency.

Vega, Mira, Sera and Luma are therefore the minimum contrast set. Nova falls between Vega and Mira; Maia is a
generous rounded midpoint; Lyra combines compactness with Sera's square extreme; Rhea intentionally combines Luma's
softness with Nova-like density.

## Executable contrast fixture

[`tests/Fixtures/views/preset-expressiveness`](../tests/Fixtures/views/preset-expressiveness) renders Button, Card,
Input, Select, Alert and Modal package components once for each minimum-set personality. All previews use one shared
Blade partial. The test compares their ordered semantic tag/slot/state signatures while allowing runtime-generated IDs
to remain unique in the combined document.

[`tests/Fixtures/css/preset_expressiveness.css`](../tests/Fixtures/css/preset_expressiveness.css) deliberately uses a
base rule for most semantic slots and changes personality through scoped custom properties. A few personality-specific
rules demonstrate that a preset may select different states or parts without changing markup. It is a diagnostic recipe,
not an official preset and not a pixel port of shadcn/ui.

Automated checks prove that the fixture:

- renders the same ordered tag/slot/variant/size/state signature for all four personalities;
- exercises the Alert family contract introduced by the slot-contract pilot;
- compiles through the installed-application Tailwind v4 pipeline;
- retains semantic selectors for controls, content surfaces, feedback and an overlay.

Visual review should compare density, geometry, typography, surface depth, focus, invalid/disabled states, light/dark,
RTL and reduced motion. Exact colors, shadows, radii and spacing are intentionally not snapshot-tested.

Toaster now joins the shared partial as structural proof of its Blade-owned source anatomy. Its card remains inside an
inert `<template>`, so this fixture does not include it in the visual comparison above and defines no Toaster-specific
fixture CSS. Tooltip is eligible for the same semantic tree but is not yet rendered by this fixture.

## Findings by family

| Family or concern                    | Classification             | Decision                                                                                                   | Destination                                |
| ------------------------------------ | -------------------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| Button                               | CSS-only                   | Existing `button`, `data-variant` and `data-size` cover all eight styles                                   | Preset CSS                                 |
| Card                                 | CSS-only                   | Root/header/title/description/action/content/footer and size are sufficient                                | Implemented family contract and preset CSS |
| Input and Textarea                   | CSS-only                   | Compact, underlined and soft-filled treatments need no DOM change                                          | Preset CSS                                 |
| Select size                          | Possible public axis       | Personality-wide control size is CSS; add a size prop only for an application-level semantic need          | Deferred, not a blocker                    |
| Field Group outline                  | Possible public axis       | One upstream composition does not justify a package axis                                                   | Omit until a package use case exists       |
| Alert                                | CSS-only                   | `alert`, title, description, action and variant cover all visual personalities                             | Implemented family contract                |
| Alert icon authorship                | Composition API            | `alert.icon` owns layout while packaged and third-party icons provide the graphic                          | Implemented Alert part                     |
| Item                                 | CSS-only                   | Existing media, content and size/variant axes are sufficient                                               | Implemented; retain in final QA            |
| Input Group button/text              | Low-level composition      | Existing components cover package controls; custom controls retain the explicit `input-group-control` hook | Documented escape hatch                    |
| Alert Dialog media/size              | Possible part and axis     | Useful upstream semantics, but not required to express current package behavior                            | Overlay-family review                      |
| Modal, Sheet and floating surfaces   | CSS-only                   | Existing panel/content parts and state/side attributes cover appearance                                    | Preset CSS                                 |
| Drawer swipe/nesting                 | Runtime-specific           | Vaul swipe variables and nested drawer behavior are not visual preset requirements                         | Do not port without behavior               |
| Tooltip component anatomy            | Semantic parts             | `Tooltip::SLOTS` owns package-styled surface and arrow slots while the trigger controller owns lifecycle   | Implemented; retain in final QA            |
| Toaster anatomy                      | Semantic parts             | `Toaster::SLOTS` owns the internal card template while JavaScript preserves state/lifecycle contracts      | Implemented; retain in final QA            |
| Overlay strength and blur            | Preset-local CSS           | `--backdrop` already provides the color hook; blur and elevation can remain internal preset variables      | Bloom/preset CSS                           |
| Heading typography                   | Application concern        | Bloom carries editorial hierarchy through scale and weight; font files stay an application choice          | Not adopted; revisit with an editorial preset |
| Logical floating sides               | Possible behavior API      | Current resolved physical `data-side` output is sufficient for visual presets                              | Independent Floating UI API review         |
| Upstream questionnaire/custom Select | React/application-specific | Not part of the package semantic contract                                                                  | Do not port                                |

The corpus found no preset-expressiveness blocker in the Alert contract. Sera's accent remains a pseudo-element and
destructive treatment remains a variant. The later authoring review added `alert.icon` so the family owns icon layout
without requiring third-party graphics to emit the generic Icon slot.

### Structural boundary audit

Byte-identical declarations across all eight styles are evidence of a shared convention, not proof that a rule is
structural. The decisive test remains whether removing the rule breaks component mechanics or only changes appearance.
Applying that test to the eight corpus-invariant candidates produced these decisions:

| Slot | Decision | Reason |
| --- | --- | --- |
| `attachment-trigger` | Split ownership | Full-card positioning and stacking are structural; suppressing the native outline remains preset-owned focus treatment. |
| `attachment-actions` | Split ownership | Actions must stack above the full-card trigger, while their flex composition, placement, offsets and gaps remain visual. |
| `table-container` | Split ownership | Full-width horizontal overflow implements the responsive wrapper. Presets retain `position: relative` as a deliberate positioning context for preset/application ornaments, although no package descendant currently consumes it; Bloom also owns its visible surface. |
| `table-body` | Visual | Removing the final row divider is edge treatment, not table mechanics. |
| `table-header` | Visual | A row divider distinguishes the header visually; native `thead` semantics do not depend on it. |
| `sidebar-gap` | Split ownership | Gap and fixed-container geometry follow desktop collapse state structurally; transition timing, easing and the floating/inset padding value remain preset-owned. |
| `item-media` | Visual | Sizing, crop, radius, gap and alignment are all legitimate preset choices. |
| `marker-content` | Visual | Wrapping, link decoration and separator composition change presentation without breaking behavior. |

All eight slots remain `visual` in the registry. Attachment's split slots and `sidebar-gap` retain declaration-bearing
participation in both presets. `table-container` remains visual because its positioning context is an explicit preset
extension point and Bloom gives it a surface. `structural.css` owns only invariant mechanics.

Those shared mechanics live in `@layer components`. They are package defaults, not a cascade lock: later preset or
application rules with equal or greater specificity can deliberately replace them. Sidebar keeps each coupled pair on
the same side of that boundary: structural CSS owns gap/container widths and offcanvas offsets, while presets set
`--sidebar-floating-inset` and consume it as visual padding. Nova and Bloom still choose transition timing and easing.

## Upstream corpus map

The family findings above are prose. [`tests/Fixtures/shadcn`](../tests/Fixtures/shadcn) turns the same comparison into
data that a test can check: `corpus.php` is the extracted inventory of every `.cn-*` class the eight styles declare, and
`map.php` records what the package does about each one — plus the opposite direction, so every visual slot answers the
corpus even when the corpus says nothing about it.

It is study data. It is not a runtime registry, not a compatibility promise and not public API: upstream class names
never become package API, and a decision recorded here never widens the official presets. `tests/Presets/ShadcnCorpusMapTest.php`
asserts the package runtime never reads it.

### Method

Both revisions are pinned in the fixtures: shadcn/ui at
[`3ba91b1c`](https://github.com/shadcn-ui/ui/tree/3ba91b1cc83e1bbe4ab35a422ff2a694849c5048) and the package revision the
decisions were verified against. The inventory is the union of the eight `style-*.css` files, extracted with the command
recorded in `corpus.reference.extraction`, which yields 425 classes. The densest single style declares 422 of them, so
the corpus is deliberately the union rather than any one file.

Each upstream class receives exactly one decision:

| Decision         | Meaning                                                                  | Count |
| ---------------- | ------------------------------------------------------------------------ | ----- |
| `equivalent`     | Same part, same name as a declared slot                                   | 141   |
| `renamed`        | Same part under a different name                                          | 29    |
| `divergent`      | The package represents the concern, but not as a matching part            | 119   |
| `not-applicable` | No package counterpart at all                                             | 136   |

Each of the 296 visual slots receives the mirror decision — 141 `equivalent`, 29 `renamed`, 62 `divergent` and 64
`hotwire-only`, the last where the corpus declares no class for the part. `equivalent` and `renamed` decisions are a
bijection: exactly one upstream class may claim a slot, and the slot must name that class back.

### Names were verified, not trusted

147 corpus classes share a name with a declared slot. Verification kept 141 of them and demoted six, which is the
reason the map exists at all rather than a name join:

- `cn-alert-dialog-overlay`, `cn-drawer-overlay` and `cn-sheet-overlay` carry the dimming declarations, so they map to
  the package's `*-backdrop`. The package's `*-overlay` is the presence layer that hosts the backdrop and the panel.
- `cn-radio-group-item` is the control itself and maps to `radio-group-input`; the package's `radio-group-item` is the
  label wrapper around the control and its content.
- `cn-drawer-content` is the sliding panel in upstream's vaul variant, while the package's `drawer-content` is the
  scrollable region inside `drawer-popup`. Same name, different part, so the decision is a divergence.
- `cn-progress` and `cn-progress-track` carry identical bodies for the bar, because upstream names it twice. The
  package's `progress` is the row holding label, track and value, so only `progress-track` is an equivalence.

The corpus is only the class layer. Upstream also emits `data-slot` attributes and reaches parts such as
`accordion-trigger-icon`, `checkbox-group`, `input-group-control` and `spinner` through descendant selectors on a
parent's class. A slot with no class in the corpus is therefore not evidence that upstream lacks the part.

### What the differences are

The decisions group into four kinds of difference, which is the useful output for Bloom and for application presets.
The counts below add up to the full 425:

- **Common semantics — 170.** The `equivalent` and `renamed` decisions cover Accordion, Alert, Card, Field,
  Item, Table, Sidebar, Attachment, Progress and the overlay families. Where a rename was needed it was consistent
  vocabulary, not a different anatomy: `dialog-*` to `modal-*`, `dropdown-menu-*` to `dropdown-*`, `empty-*` to
  `empty-state-*`, `native-select` to `select`, `tabs-content` to `tabs-panel`, `tooltip-content` to `tooltip`.
- **Visual technique — 63.** These divergences exist only because upstream spells a value into the class name — `button-size-lg`,
  `badge-variant-outline`, `item-size-sm`, `separator-horizontal`. The package keeps one part and selects `[data-variant]`,
  `[data-size]`, `[data-orientation]` or `[data-align]` on it. None of these asks for a new part or a new axis.
- **React particularity — 130.** 32 classes are React Aria substrate rules, restyling a part for the state attributes
  React Aria emits — `data-entering`, `data-exiting`, `data-selected`, `data-invalid`, `data-focused`,
  `data-focus-visible`, `data-placeholder` or `peer-data-disabled`, depending on the part — none of which the package
  DOM carries. Another 81 belong to components the package does not ship: Menubar, Command palette, Context Menu,
  Calendar, chat Bubble and Message, input OTP, Navigation Menu and Resizable. The remaining 17 are the
  application-specific questionnaire block the study already declined to port.
- **Deliberate divergence — 56.** Decisions, not gaps: native `<select>`, `<input type=range>`, checkbox and radio
  instead of custom listboxes and indicator children; one `input-group-control` hook instead of one class per nested
  control type; resolved physical `data-side` instead of logical `inline-start`/`inline-end` motion; a single Dropdown
  menu surface instead of submenus and checkable items; Multi Select over a native control instead of a Combobox port.
- **Neither — 6.** `cn-chart-tooltip`, the two vaul drawer swipe handles, `cn-menu-translucent` and the two Scroll Area
  parts, each recorded as not applicable for its own reason.

The `hotwire-only` slots are mostly package territory upstream has no equivalent for: File Upload, Read More, Reveal,
Rich Text, Side Panel, Toaster internals, oEmbed, Timeago, Sticky, Back to Top, Scroll Progress and the Turbo-aware
pagination loading state.

### Guards

`tests/Presets/ShadcnCorpusMapTest.php` fails when a corpus class has no decision, when a decision names a slot the
registry does not declare, when a new visual slot enters the registry without a comparative decision, when a decided
slot is no longer visual, and when an upstream class points at a slot recorded as having no upstream counterpart.

Point `HOTWIRE_SHADCN_REFERENCE` at a checkout of the pinned revision and one further test re-runs the extraction and
compares it to `corpus.php`, refusing to pass on a checkout that drifted from the pinned commit. It skips when no
checkout is reachable, so the inventory is verified against upstream wherever one exists rather than only against
itself.


## Recommended preset conformance policy

This section records the policy applied by package tooling and the application-preset validator. Blank scaffolds come
from registry visual slots, and preset coverage does not require lexical axis equality.

The catalog's visual slots should be the preset-neutral API. `PresetAxes` is a diagnostic description of attributes a
particular stylesheet differentiates; it cannot prove semantic support by itself.

Package CI applies this complete policy to official presets:

1. Imports the shared `foundation.css` facade exactly once before preset base and visual modules.
2. Compiles without unresolved Tailwind directives.
3. Gives every catalog visual slot declaration-bearing participation in the compiled CSS.
4. References only declared slots.
5. Preserves focused structural, behavioral and accessibility contracts.

For application presets, `hotwire:check --preset=<name|path>` statically checks the foundation imports, local import
graph and source-level slot participation from items 1, 3 and 4. The application's production asset build proves item 2;
focused browser and component tests prove item 5.

A valid preset does **not** have to enumerate the same selectors, axes or values as Nova. A base rule may cover every
value, multiple values may intentionally share appearance, and state may be expressed through an ancestor, equivalent
selector or Tailwind variant inside `@apply`.

Valid examples:

```css
/* One base appearance can cover every Badge variant. */
[data-slot="badge"] {
    @apply bg-primary text-primary-foreground inline-flex rounded-full;
}

/* The Card owns size while its descendant receives the visual change. */
[data-slot="card"][data-size="sm"] [data-slot="card-title"] {
    @apply text-sm;
}

/* Distinct API values may share one appearance in this preset. */
:where([data-slot="item"][data-size="default"], [data-slot="item"][data-size="sm"]) {
    @apply gap-2 px-3 py-2;
}
```

Invalid examples:

```css
/* Empty rules satisfy lexical occurrence checks but emit no visual CSS. */
[data-slot="badge"] {
}

/* The typo is outside the catalog contract. */
[data-slot="badgge"] {
    @apply rounded-full;
}
```

An explicit `--from=nova` clone remains the correct path for authors who want Nova's complete selector structure; a
blank scaffold is intentionally only a registry-derived anatomy checklist. The public validation workflow combines
catalog slot coverage from `hotwire:check`, the application's real production build and focused state checks. Static
uncertainty is reported separately from proven errors.

## Pairings and support boundary

Upstream treats component base, visual style, base color, theme, chart color, icon library, body font, heading font,
radius, menu treatment, direction and pointer cursor as separate dimensions. Recommended style/font/icon combinations
are curated defaults, not dependencies of the stylesheet. Laravel Hotwire should preserve the same separation:

- presets own component appearance;
- semantic tokens own palette, and a preset may ship its own values or explicitly registered additional names;
- application CSS owns font loading and font-token values;
- Icon remains independent of the visual preset;
- RTL and accessibility behavior remain shared contracts.

Nova covers compact, neutral product UI. Bloom now covers the opposing spacious, elevated and expressive territory on
the same semantic tree, with modular visual sources, a chromatic palette, restrained surface radii, tinted
notification surfaces and softer motion. That pair is sufficient for the core because the package's responsibility is to
prove the architecture and provide authoring/tooling, not to maintain every visual genre. Square/mono, editorial,
ultra-dense and glass-like treatments are better published as application or community recipes. If a future third core
preset is justified by demand, the Sera-like editorial extreme adds more independent coverage than another neutral or
soft midpoint.

## Reusable acceptance cases

The family migration, preset tooling, Bloom, external validator and final QA should reuse these cases:

- A base-only rule covers a public variant axis without an explicit value selector.
- Two values intentionally share one rule and appearance.
- An ancestor carries state while a descendant slot receives declarations.
- Equivalent selector spelling does not make a preset invalid.
- An empty selector and an undeclared slot typo fail conformance.
- The contrast fixture keeps one semantic tree across all personalities.
- Toaster's Blade-owned source joins the shared tree as structural coverage; rendered Toaster and Tooltip states remain
  final browser smoke cases.
- Final browser smoke covers focus, disabled/invalid state, light/dark, RTL, reduced motion and nested overlays without
  asserting decorative pixel parity.
