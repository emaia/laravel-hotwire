# Preset expressiveness study

This study stress-tests Laravel Hotwire's semantic component contract against the eight official shadcn/ui visual
styles. It asks whether substantially different visual languages can share the same Blade, DOM, controllers and
accessibility behavior. It does not propose shipping eight presets or reproducing React implementation details.

## References and method

- Laravel Hotwire reference: `773f585114c08752531d482a3ba93641a11eff81`
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
| Sera  | Spacious, 2.5rem controls  | Square                              | Editorial headings, uppercase controls | Rules, underlined controls and 2px rings     | CSS plus `--font-heading` candidate |
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

Tooltip and Toaster are excluded from this first executable fixture because their current controllers still own generated
markup. Add them to the same shared partial after their planned Blade-template migrations; do not create a temporary
second anatomy for this study.

## Findings by family

| Family or concern                    | Classification             | Decision                                                                                              | Destination                                |
| ------------------------------------ | -------------------------- | ----------------------------------------------------------------------------------------------------- | ------------------------------------------ |
| Button                               | CSS-only                   | Existing `button`, `data-variant` and `data-size` cover all eight styles                              | Preset CSS                                 |
| Card                                 | CSS-only                   | Root/header/title/description/action/content/footer and size are sufficient                           | Family migration and preset CSS            |
| Input and Textarea                   | CSS-only                   | Compact, underlined and soft-filled treatments need no DOM change                                     | Preset CSS                                 |
| Select size                          | Possible public axis       | Personality-wide control size is CSS; add a size prop only for an application-level semantic need     | Deferred, not a blocker                    |
| Field Group outline                  | Possible public axis       | One upstream composition does not justify a package axis                                              | Omit until a package use case exists       |
| Alert                                | CSS-only                   | `alert`, title, description, action and variant cover all visual personalities                        | Keep the pilot contract                    |
| Alert icon authorship                | Composition API            | Third-party icon ergonomics are independent of preset expressiveness                                  | Dedicated icon-composition work            |
| Item                                 | CSS-only                   | Existing media, content and size/variant axes are sufficient                                          | Family migration; verify separator spacing |
| Input Group button/text              | Possible semantic parts    | Add only if concrete package compositions cannot be expressed by addon plus existing controls         | Family migration decision                  |
| Alert Dialog media/size              | Possible part and axis     | Useful upstream semantics, but not required to express current package behavior                       | Overlay-family review                      |
| Modal, Sheet and floating surfaces   | CSS-only                   | Existing panel/content parts and state/side attributes cover appearance                               | Preset CSS                                 |
| Drawer swipe/nesting                 | Runtime-specific           | Vaul swipe variables and nested drawer behavior are not visual preset requirements                    | Do not port without behavior               |
| Tooltip and Toaster anatomy          | Semantic parts             | Their eventual Blade templates must preserve current state/lifecycle contracts                        | Template migrations and final QA           |
| Overlay strength and blur            | Preset-local CSS           | `--backdrop` already provides the color hook; blur and elevation can remain internal preset variables | Bloom/preset CSS                           |
| Heading typography                   | Shared token candidate     | `--font-heading` is useful across Card, Alert, overlays and editorial recipes                         | Bloom/theming review                       |
| Logical floating sides               | Possible behavior API      | Current resolved physical `data-side` output is sufficient for visual presets                         | Independent Floating UI API review         |
| Upstream questionnaire/custom Select | React/application-specific | Not part of the package semantic contract                                                             | Do not port                                |

No blocker was found in `Alert::SLOTS`. Sera's accent is a pseudo-element, destructive treatment is a variant, and icon
layout can select a composed Icon descendant. Whether the package should wrap third-party icons is an authoring decision,
not a missing visual part discovered by this corpus.

## Recommended preset conformance policy

This section records the target policy for the preset tooling and external validator. The current package still uses
Nova-derived scaffold selectors and a lexical cross-preset axis guard; those implementations must change before this
policy becomes an enforced package contract.

The catalog's visual slots should be the preset-neutral API. `PresetAxes` is a diagnostic description of attributes a
particular stylesheet differentiates; it cannot prove semantic support by itself.

Under the target policy, a complete official or application preset is valid when it:

1. Imports the shared token, custom-variant and structural foundations exactly as required.
2. Compiles without unresolved Tailwind directives.
3. Gives every catalog visual slot declaration-bearing participation in the compiled CSS.
4. References only declared slots.
5. Preserves focused structural, behavioral and accessibility contracts.

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

The current cross-preset axis-equality guard is vacuous while one preset ships and would reject legitimate base styling
when a second preset arrives. The preset generation/cloning work should stop treating Nova's selector structure as the
generic contract; an explicit `--from=nova` clone remains the correct path for authors who want Nova's complete
structure. The external preset validator should combine catalog slot coverage with real compilation and focused state
checks, and should distinguish proven errors from cases static analysis cannot decide.

## Pairings and support boundary

Upstream treats component base, visual style, base color, theme, chart color, icon library, body font, heading font,
radius, menu treatment, direction and pointer cursor as separate dimensions. Recommended style/font/icon combinations
are curated defaults, not dependencies of the stylesheet. Laravel Hotwire should preserve the same separation:

- presets own component appearance;
- semantic tokens own palette;
- application CSS owns font loading and font-token values;
- Icon remains independent of the visual preset;
- RTL and accessibility behavior remain shared contracts.

Nova covers compact, neutral product UI. Bloom should cover the opposing spacious, rounded, elevated and expressive
territory on the same semantic tree. That pair is sufficient for the core because the package's responsibility is to
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
- Tooltip and Toaster join the fixture only after their final Blade-owned anatomy exists.
- Final browser smoke covers focus, disabled/invalid state, light/dark, RTL, reduced motion and nested overlays without
  asserting decorative pixel parity.
