# Theming

Override the design tokens to customise the visual appearance of Laravel Hotwire components.

## How it works

Laravel Hotwire ships a Tailwind v4 theme using semantic CSS custom properties. All components consume tokens like `bg-background`, `text-foreground`, `border-border` — never raw colors. Override the underlying CSS variables to re-theme without touching Blade views.

## Token reference

### Colors

| Token | Light mode | Dark mode | Role |
|-------|-----------|-----------|------|
| `--background` | `oklch(1 0 0)` | `oklch(0.145 0 0)` | Main background |
| `--foreground` | `oklch(0.145 0 0)` | `oklch(0.985 0 0)` | Primary text |
| `--card` | `oklch(1 0 0)` | `oklch(0.205 0 0)` | Card/panel background |
| `--card-foreground` | `oklch(0.145 0 0)` | `oklch(0.985 0 0)` | Card/panel text |
| `--popover` | `oklch(1 0 0)` | `oklch(0.205 0 0)` | Popover background |
| `--popover-foreground` | `oklch(0.145 0 0)` | `oklch(0.985 0 0)` | Popover text |
| `--primary` | `oklch(0.205 0 0)` | `oklch(0.922 0 0)` | Primary accent |
| `--primary-foreground` | `oklch(0.985 0 0)` | `oklch(0.205 0 0)` | Text on primary |
| `--secondary` | `oklch(0.965 0 0)` | `oklch(0.269 0 0)` | Secondary background |
| `--secondary-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Text on secondary |
| `--muted` | `oklch(0.965 0 0)` | `oklch(0.269 0 0)` | Muted background |
| `--muted-foreground` | `oklch(0.556 0 0)` | `oklch(0.708 0 0)` | Subdued text |
| `--accent` | `oklch(0.965 0 0)` | `oklch(0.269 0 0)` | Accent highlight |
| `--accent-foreground` | `oklch(0.205 0 0)` | `oklch(0.985 0 0)` | Text on accent |
| `--destructive` | `oklch(0.577 0.245 27.325)` | `oklch(0.704 0.191 22.216)` | Destructive action |
| `--destructive-foreground` | `oklch(0.985 0 0)` | `oklch(0.637 0.237 25.331)` | Text on destructive |
| `--border` | `oklch(0.922 0 0)` | `oklch(0.269 0 0)` | Borders |
| `--input` | `oklch(0.922 0 0)` | `oklch(0.269 0 0)` | Input backgrounds |
| `--ring` | `oklch(0.205 0 0)` | `oklch(0.439 0 0)` | Focus rings |

### Radius

| Token | Value |
|-------|-------|
| `--radius` | `0.625rem` |
| `--radius-sm` | `calc(var(--radius) - 4px)` |
| `--radius-md` | `calc(var(--radius) - 2px)` |
| `--radius-lg` | `var(--radius)` |
| `--radius-xl` | `calc(var(--radius) + 4px)` |

## Override tokens

Override the CSS variables anywhere after the `@import` in `resources/css/app.css`:

```css
@import "tailwindcss";

/* ... @source and @theme from the package ... */

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

Use the provided `color-scheme` component (available from `0.33.0`) to let users toggle themes. Without `data-theme`, the `:root` (light) defaults apply.

## Colour space

All tokens use the **OKLCH** colour space for perceptually uniform lightness and predictable blending. Browsers that do not support OKLCH (Safari < 15.4, Chrome < 111) will not render themed components — document this cutoff for your users.
