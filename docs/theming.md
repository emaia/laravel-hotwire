# Theming

Override design tokens to customise the palette and geometry your preset renders with.

## How it works

Laravel Hotwire ships a Tailwind v4 token layer using semantic CSS custom properties. Public presets reach that layer
through `resources/css/foundation.css`, which also imports package custom variants and structural CSS in canonical order.
Components render semantic `data-slot` attributes; presets consume tokens like `bg-background`, `text-foreground`,
`border-border` to style those slots.

Use [`presets.md`](presets.md) and `php artisan hotwire:make-preset` when you want to change the visual system's
spacing, geometry, motion or variant treatment. Presets keep the component's Blade markup, behavior and accessibility
contract; use this guide when you only want to change colors or radius tokens while keeping the selected preset.

The token layer ships neutral defaults. A preset may replace them: Nova renders on the defaults below, while Bloom
declares its own palette and radius. Application overrides go after the preset import either way, so the
instructions here apply to both — only the value you start from differs.

## Token reference

### Colors

| Token                          | Light mode                  | Dark mode                   | Role                            |
|--------------------------------|-----------------------------|-----------------------------|---------------------------------|
| `--background`                 | `oklch(1 0 0)`              | `oklch(0.145 0 0)`          | Main background                 |
| `--foreground`                 | `oklch(0% 0 0)`             | `oklch(0.985 0 0)`          | Primary text                    |
| `--card`                       | `oklch(1 0 0)`              | `oklch(0.205 0 0)`          | Card/panel background           |
| `--card-foreground`            | `oklch(0% 0 0)`             | `oklch(0.985 0 0)`          | Card/panel text                 |
| `--popover`                    | `oklch(1 0 0)`              | `oklch(0.205 0 0)`          | Popover background              |
| `--popover-foreground`         | `oklch(0% 0 0)`             | `oklch(0.985 0 0)`          | Popover text                    |
| `--primary`                    | `oklch(0% 0 0)`             | `oklch(0.922 0 0)`          | Primary accent                  |
| `--primary-foreground`         | `oklch(0.985 0 0)`          | `oklch(0.205 0 0)`          | Text on primary                 |
| `--secondary`                  | `oklch(0.97 0 0)`           | `oklch(0.269 0 0)`          | Secondary background            |
| `--secondary-foreground`       | `oklch(0.205 0 0)`          | `oklch(0.985 0 0)`          | Text on secondary               |
| `--muted`                      | `oklch(0.97 0 0)`           | `oklch(0.269 0 0)`          | Muted background                |
| `--muted-foreground`           | `oklch(0.54 0 0)`           | `oklch(0.708 0 0)`          | Subdued text                    |
| `--accent`                     | `oklch(0.97 0 0)`           | `oklch(0.371 0 0)`          | Accent highlight                |
| `--accent-foreground`          | `oklch(0.205 0 0)`          | `oklch(0.985 0 0)`          | Text on accent                  |
| `--destructive`                | `oklch(0.577 0.245 27.325)` | `oklch(0.704 0.191 22.216)` | Destructive action              |
| `--destructive-foreground`     | `oklch(0.985 0 0)`          | `oklch(0.205 0 0)`          | Text on destructive             |
| `--border`                     | `oklch(0.922 0 0)`          | `oklch(1 0 0 / 10%)`        | Borders                         |
| `--backdrop`                   | `oklch(0 0 0 / 10%)`        | `oklch(0 0 0 / 10%)`        | Overlay backdrops               |
| `--input`                      | `oklch(0.922 0 0)`          | `oklch(1 0 0 / 15%)`        | Input backgrounds               |
| `--ring`                       | `oklch(0.708 0 0)`          | `oklch(0.556 0 0)`          | Focus rings                     |
| `--sidebar`                    | `oklch(0.985 0 0)`          | `oklch(0.205 0 0)`          | Sidebar background              |
| `--sidebar-foreground`         | `oklch(0.145 0 0)`          | `oklch(0.985 0 0)`          | Sidebar text                    |
| `--sidebar-primary`            | `oklch(0.205 0 0)`          | `oklch(0.985 0 0)`          | Sidebar primary accents         |
| `--sidebar-primary-foreground` | `oklch(0.985 0 0)`          | `oklch(0.205 0 0)`          | Text on sidebar primary accents |
| `--sidebar-accent`             | `oklch(0.97 0 0)`           | `oklch(0.269 0 0)`          | Sidebar hover/active background |
| `--sidebar-accent-foreground`  | `oklch(0.205 0 0)`          | `oklch(0.985 0 0)`          | Text on sidebar accent          |
| `--sidebar-border`             | `oklch(0.922 0 0)`          | `oklch(1 0 0 / 10%)`        | Sidebar borders                 |
| `--sidebar-ring`               | `oklch(0.708 0 0)`          | `oklch(0.556 0 0)`          | Sidebar focus rings             |

### Radius

Derived tokens scale proportionally against `--radius`, so overriding the base value (e.g. `--radius: 1rem`) keeps the
relative sizes of `sm/md/xl/2xl/3xl/4xl` in the same visual proportion.

| Token          | Value                       |
|----------------|-----------------------------|
| `--radius`     | `0.625rem`                  |
| `--radius-sm`  | `calc(var(--radius) * 0.6)` |
| `--radius-md`  | `calc(var(--radius) * 0.8)` |
| `--radius-lg`  | `var(--radius)`             |
| `--radius-xl`  | `calc(var(--radius) * 1.4)` |
| `--radius-2xl` | `calc(var(--radius) * 1.8)` |
| `--radius-3xl` | `calc(var(--radius) * 2.2)` |
| `--radius-4xl` | `calc(var(--radius) * 2.6)` |

### Preset-owned tokens

Bloom re-declares the shared token layer after importing it, so selecting Bloom changes those starting values while
retaining their names and roles. It also adds documented status and geometry tokens for its own visual language:

| Token          | Bloom                | Default (Nova)                     |
|----------------|----------------------|------------------------------------|
| `--primary`    | Chromatic orchid     | Achromatic near-black / near-white |
| `--accent`     | Soft leaf green      | Achromatic grey                    |
| `--secondary`  | Warm blush           | Achromatic grey                    |
| `--background` | Warm paper           | Pure white / near-black            |
| `--ring`       | Chromatic orchid     | Achromatic grey                    |
| `--radius`     | `0.25rem`            | `0.625rem`                         |
| `--success`    | Green ink            | Not declared                       |
| `--warning`    | Amber ink            | Not declared                       |
| `--info`       | Blue ink             | Not declared                       |

`--success`, `--warning` and `--info` (each with a `*-foreground`) are Bloom's added colour token names. They exist
because Bloom tints Toast surfaces per `data-type`, and they follow `--destructive`'s pattern: a darker ink in light
mode, a brighter one in dark mode. They are preset-owned, not part of the shared token layer, so Nova and any preset
that does not declare them keep the neutral, glyph-only treatment.

Bloom also declares `--radius-action`, which defaults action surfaces to `var(--radius)`, and `--radius-control`, a capped
step (`min(calc(var(--radius) * 0.8), 0.5rem)`) used by checkbox, indicator and badge-sized slots that would otherwise
resolve to a circle at a large application radius. Both follow an application `--radius` override; set
`--radius-action: 9999px` to opt action surfaces into pill geometry without changing intrinsic circular controls.

Every token keeps the same name and role, so an application override works identically under either preset — see
[Override tokens](#override-tokens). Overriding `--radius` alone re-proportions Bloom's whole geometry, because its
slots round through the derived scale rather than hardcoded values.

## Override tokens

Override CSS variables anywhere after the preset import in `resources/css/app.css`:

```css
@import "tailwindcss";

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';

:root {
    --radius: 0.5rem;
}

:root:not([data-theme="dark"]),
[data-theme="light"] {
    --background: oklch(0.98 0.01 280); /* lavender tint */
    --foreground: oklch(0.15 0.02 280);
    --primary: oklch(0.5 0.2 280); /* purple accent */
    --primary-foreground: oklch(0.98 0 0);
}

[data-theme="dark"] {
    --background: oklch(0.2 0.01 280);
    --foreground: oklch(0.9 0.01 280);
    --primary: oklch(0.6 0.15 280);
    --primary-foreground: oklch(0.2 0.01 280);
}
```

Override only the tokens you change — the rest fall back to the package defaults.

Use the guarded `:root:not([data-theme="dark"])` selector for light-only colour overrides. A later bare `:root` rule
also matches `<html data-theme="dark">` and can therefore replace dark tokens with light values. Theme-independent
tokens such as `--radius` can remain on bare `:root`.

## Semantic contrast

Every semantic text pair must meet a WCAG 2.x contrast ratio of at least `4.50:1` in both themes. The shared registered
contrast pairs are background, card, popover, primary, secondary, muted, accent, destructive, sidebar, sidebar primary
and sidebar accent, each with its corresponding `*-foreground` token (`--foreground` for `--background`). An official
preset may register additional explicit pairs for semantic surfaces introduced by its own token contract.

WCAG permits `3:1` for large text and uses non-text criteria for graphical controls, but these shared pairs can render
normal-size copy across many components, so their package contract does not relax by usage. Border, input and ring tokens
are not text pairs and are outside this ratio check.

The package browser test discovers every official preset, its ordered base sources and all inherited and additional
registered contrast pairs from the validated PHP manifest. It verifies rendered browser colours, including CSS gamut
mapping and nested theme scopes. Application overrides may use any valid CSS colour syntax, but become part of the
application's accessibility contract: test the rendered result after overrides, opacity, images, gradients and blending.
`hotwire:check` deliberately does not interpret application CSS or claim to validate its contrast.

## Forced colors and print

Forced-colors mode intentionally uses browser system colors instead of design tokens. The shared structural stylesheet
also restores native rendering for custom-painted checkable controls, so changing palette tokens cannot remove checked,
indeterminate, focus or disabled states in Windows High Contrast. Printing uses the same principle and preserves
selection/progress without relying on printed backgrounds.

These are control-level fallbacks, not an application print layout. Laravel Hotwire does not hide navigation, expand
disclosures or append link destinations. See [Structural and visual CSS](presets.md#structural-and-visual-css) when a
custom preset needs to refine the shared baseline.

For broader changes, generate a local preset and replace the selected official preset import:

```bash
php artisan hotwire:make-preset brand --from=nova
```

Use `--from=bloom` instead when Bloom is the intended starting point.

```css
@import './presets/brand.css';
```

Keep application-level token overrides after the local preset import. A local scaffold or clone already imports the
package `foundation.css` facade; do not duplicate it or its internal imports in `app.css`.

Shared semantic tokens such as `--background`, `--primary` and `--ring` retain the same meaning across presets. A preset
may additionally document system-wide knobs for its own geometry or visual language. Override those after the preset in
the same way, but treat only documented knobs as public API; variables local to one component module may change with the
preset implementation.

## Color schemes

Dark mode activates when `<html>` has `data-theme="dark"`:

```blade
<html data-theme="dark">
```

Without `data-theme`, the `:root` light defaults apply and advertise `color-scheme: light` to the browser. Explicit
`[data-theme="light"]` and `[data-theme="dark"]` scopes set both their semantic palette and the matching `color-scheme`.
Semantic tokens, native controls, scrollbars and preset-specific dark surfaces therefore follow the nearest nested
theme. A light island inside a dark scope restores the complete light treatment, and a nested dark island applies the
dark treatment again.

Application-authored `dark:` utilities still use Tailwind's ancestor variant exported by the package. They match any
descendant of a dark ancestor and do not stop at a nested `[data-theme="light"]` boundary. Prefer semantic tokens when
both themes can share a declaration. When an application visual needs different dark declarations, move them into a
top-level `@scope ([data-theme="dark"]) to ([data-theme="light"])` block as shown in
[Upgrade](upgrade.md#semantic-tokens-enforce-readable-contrast-and-nested-color-schemes).

The unthemed document stays fully light even when the operating system prefers dark. Use the Color Scheme script when
the page should follow that preference; advertising both schemes while keeping an unconditional light palette would let
the browser paint dark native controls on light component surfaces.

Use `<hw:color-scheme.script>` in the document head to apply the stored document scheme before CSS paints, and
`<hw:color-scheme.toggle>` for user switching.

See [`docs/components/color-scheme.md`](components/color-scheme.md) for the packaged script and toggle.

## Colour space

All tokens use the **OKLCH** colour space for perceptually uniform lightness and predictable blending. Browsers that do
not support OKLCH (Safari < 15.4, Chrome and Edge < 111, Firefox < 113) will not render themed components. Official
preset nearest-theme surfaces and Sidebar icon-collapsed rules use CSS `@scope`, raising the effective minimum to Safari and
iOS 17.4, Chrome and Edge 118, Firefox 146, Opera 106 and Samsung Internet 25. Browsers below that floor ignore the
scoped rules: semantic dark tokens still resolve when OKLCH is supported, but presets lose their dark-specific surface and
state adjustments across controls, while the Sidebar's scoped icon-collapse rules do not apply. Firefox ESR 140 does
not meet this requirement; Firefox ESR 153 does.
