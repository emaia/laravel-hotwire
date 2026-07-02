# Theming

Override design tokens to customise the palette shared by every preset.

## How it works

Laravel Hotwire ships a Tailwind v4 token layer using semantic CSS custom properties. Components render semantic `data-slot` attributes; presets consume tokens like `bg-background`, `text-foreground`, `border-border` to style those slots.

Use [`presets.md`](presets.md) when you want to change component structure, spacing, radius or variants. Use this guide when you want to change colors/radius tokens while keeping the selected preset.

## Token reference

### Colors

| Token | Light mode | Dark mode | Role |
|-------|-----------|-----------|------|
| `--background` | `oklch(1 0 0)` | `oklch(0.145 0 0)` | Main background |
| `--foreground` | `oklch(0% 0 0)` | `oklch(0.985 0 0)` | Primary text |
| `--card` | `oklch(1 0 0)` | `oklch(0.205 0 0)` | Card/panel background |
| `--card-foreground` | `oklch(0% 0 0)` | `oklch(0.985 0 0)` | Card/panel text |
| `--popover` | `oklch(1 0 0)` | `oklch(0.205 0 0)` | Popover background |
| `--popover-foreground` | `oklch(0% 0 0)` | `oklch(0.985 0 0)` | Popover text |
| `--primary` | `oklch(0% 0 0)` | `oklch(0.922 0 0)` | Primary accent |
| `--primary-foreground` | `oklch(0.985 0 0)` | `oklch(0.205 0 0)` | Text on primary |
| `--secondary` | `oklch(0.97 0 0)` | `oklch(0.269 0 0)` | Secondary background |
| `--secondary-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Text on secondary |
| `--muted` | `oklch(0.97 0 0)` | `oklch(0.269 0 0)` | Muted background |
| `--muted-foreground` | `oklch(0.556 0 0)` | `oklch(0.708 0 0)` | Subdued text |
| `--accent` | `oklch(0.97 0 0)` | `oklch(0.371 0 0)` | Accent highlight |
| `--accent-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Text on accent |
| `--destructive` | `oklch(0.577 0.245 27.325)` | `oklch(0.704 0.191 22.216)` | Destructive action |
| `--destructive-foreground` | `oklch(0.97 0.01 17)` | `oklch(0.58 0.22 27)` | Text on destructive |
| `--border` | `oklch(0.922 0 0)` | `oklch(1 0 0 / 10%)` | Borders |
| `--input` | `oklch(0.922 0 0)` | `oklch(1 0 0 / 15%)` | Input backgrounds |
| `--ring` | `oklch(0.708 0 0)` | `oklch(0.556 0 0)` | Focus rings |

### Radius

Derived tokens scale proportionally against `--radius`, so overriding the base value (e.g. `--radius: 1rem`) keeps the relative sizes of `sm/md/xl/2xl/3xl/4xl` in the same visual proportion.

| Token | Value |
|-------|-------|
| `--radius` | `0.625rem` |
| `--radius-sm` | `calc(var(--radius) * 0.6)` |
| `--radius-md` | `calc(var(--radius) * 0.8)` |
| `--radius-lg` | `var(--radius)` |
| `--radius-xl` | `calc(var(--radius) * 1.4)` |
| `--radius-2xl` | `calc(var(--radius) * 1.8)` |
| `--radius-3xl` | `calc(var(--radius) * 2.2)` |
| `--radius-4xl` | `calc(var(--radius) * 2.6)` |

## Override tokens

Override CSS variables anywhere after the preset import in `resources/css/app.css`:

```css
@import "tailwindcss";

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';

:root {
    --background: oklch(0.98 0.01 280);      /* lavender tint */
    --foreground: oklch(0.15 0.02 280);
    --primary: oklch(0.5 0.2 280);           /* purple accent */
    --primary-foreground: oklch(0.98 0 0);
    --radius: 0.5rem;
}

[data-theme="dark"] {
    --background: oklch(0.2 0.01 280);
    --foreground: oklch(0.9 0.01 280);
    --primary: oklch(0.6 0.15 280);
    --primary-foreground: oklch(0.2 0.01 280);
}
```

Override only the tokens you change — the rest fall back to the package defaults.

## Dark mode

Dark mode activates when `<html>` has `data-theme="dark"`:

```blade
<html data-theme="dark">
```

Without `data-theme`, the `:root` (light) defaults apply. A packaged color-scheme toggle is planned separately; until then, set `data-theme` server-side or with your own script.

## Colour space

All tokens use the **OKLCH** colour space for perceptually uniform lightness and predictable blending. Browsers that do not support OKLCH (Safari < 15.4, Chrome < 111) will not render themed components — document this cutoff for your users.
