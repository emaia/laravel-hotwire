# Theming

Override design tokens to customise the palette shared by every preset.

## How it works

Laravel Hotwire ships a Tailwind v4 token layer using semantic CSS custom properties. Components render semantic
`data-slot` attributes; presets consume tokens like `bg-background`, `text-foreground`, `border-border` to style those
slots.

Use [`presets.md`](presets.md) and `php artisan hotwire:make-preset` when you want to change component structure,
spacing, radius or variants. Use this guide when you want to change colors/radius tokens while keeping the selected
preset.

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

Every semantic text pair must meet a WCAG 2.x contrast ratio of at least `4.50:1` in both themes. The checked pairs are
background, card, popover, primary, secondary, muted, accent, destructive, sidebar, sidebar primary and sidebar accent,
each with its corresponding `*-foreground` token (`--foreground` for `--background`).

WCAG permits `3:1` for large text and uses non-text criteria for graphical controls, but these shared pairs can render
normal-size copy across many components, so their package contract does not relax by usage. Border, input and ring tokens
are not text pairs and are outside this ratio check.

The package verifies these pairs from their rendered browser colours, including CSS gamut mapping and nested theme
scopes. Application overrides may use any valid CSS colour syntax, but become part of the application's accessibility
contract: test the rendered result after overrides, opacity, images, gradients and blending. `hotwire:check` deliberately
does not interpret application CSS or claim to validate its contrast.

## Forced colors and print

Forced-colors mode intentionally uses browser system colors instead of design tokens. The shared structural stylesheet
also restores native rendering for custom-painted checkable controls, so changing palette tokens cannot remove checked,
indeterminate, focus or disabled states in Windows High Contrast. Printing uses the same principle and preserves
selection/progress without relying on printed backgrounds.

These are control-level fallbacks, not an application print layout. Laravel Hotwire does not hide navigation, expand
disclosures or append link destinations. See [Structural and visual CSS](presets.md#structural-and-visual-css) when a
custom preset needs to refine the shared baseline.

For broader changes, generate a local preset and replace the Nova import:

```bash
php artisan hotwire:make-preset brand --from=nova
```

```css
@import './presets/brand.css';
```

Keep application-level token overrides after the local preset import. A blank generated preset already imports the
package token and custom-variant layers; do not duplicate those imports in `app.css`.

## Color schemes

Dark mode activates when `<html>` has `data-theme="dark"`:

```blade
<html data-theme="dark">
```

Without `data-theme`, the `:root` light defaults apply and advertise `color-scheme: light` to the browser. Explicit
`[data-theme="light"]` and `[data-theme="dark"]` scopes set both their semantic palette and the matching `color-scheme`,
so semantic tokens, native controls and scrollbars follow the nearest nested theme.

Preset rules written with the `dark:` variant do not. The variant matches any element under a dark ancestor, so a light
island nested inside a dark scope keeps its light tokens but still receives dark-tuned preset surfaces. Place a light
island below a dark one only where the component's `dark:` rules do not matter, or override the affected slots in the
application stylesheet.

The unthemed document stays fully light even when the operating system prefers dark. Use the Color Scheme script when
the page should follow that preference; advertising both schemes while keeping an unconditional light palette would let
the browser paint dark native controls on light component surfaces.

Use `<hw:color-scheme.script>` in the document head to apply the stored document scheme before CSS paints, and
`<hw:color-scheme.toggle>` for user switching.

See [`docs/components/color-scheme.md`](components/color-scheme.md) for the packaged script and toggle.

## Colour space

All tokens use the **OKLCH** colour space for perceptually uniform lightness and predictable blending. Browsers that do
not support OKLCH (Safari < 15.4, Chrome < 111) will not render themed components. The Nova Sidebar's icon-collapsed
rules additionally use CSS `@scope`; include both features when defining the application's supported browser matrix.
