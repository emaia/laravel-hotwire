# Upgrade guide

Manual steps required when upgrading to a release that introduces a breaking change. The package follows semver `X.Y.Z`; **breaking visual changes** are also called out here because they aren't enforceable by code but can surprise apps relying on the prior appearance.

---

## Upgrading to `0.32.0`

`0.32.0` introduces the design system foundation (semantic tokens, OKLCH palette, dark mode via `data-theme`, `Variants` helper, embedded icon subset). All shipped components were repainted to consume the new tokens — visible without code changes in the host app, but the painted result is different.

### What changes automatically (no action required)

- Modal, Confirm-dialog, Dropdown, Form primitives (Input, Label, Select, Textarea, File, Error, Description), Flash-message, Toaster, Spinner and the auxiliary components ship with the new shadcn-aligned palette and spacing.
- All controllers ship from the vendor directory via `import.meta.glob` — no `php artisan hotwire:controllers <name>` step is required to make a `<x-hwc::*>` work in a fresh app.
- `hotwire:install` adds a `@hotwire` Vite alias to your `vite.config.{ts,mjs,js}` so user code can extend a vendor controller via a clean import (`import CarouselController from '@hotwire/controllers/carousel_controller.js'`). The alias is added idempotently — re-running `hotwire:install` is a no-op when the key is already present. If your config doesn't match the Laravel-stock shape, the command prints the snippet for manual paste instead of writing the file. See [extending-controllers.md](extending-controllers.md).
- The `Icon` component (`<x-hwc::icon name="..." />`) replaces inline SVGs in the shipped components.

### What you must do manually

#### 1. Add the `@source` directive for PHP component classes

The `Variants` helper (and any future component with classes declared in PHP) lives outside the Blade views. Tailwind v4's scanner is content-type agnostic but needs to know where to look — without this line, classes referenced inside `Variants::make(...)` calls will be silently omitted from the final CSS.

Open your application's `resources/css/app.css` and add the second `@source` line:

```diff
  @source '../../vendor/emaia/laravel-hotwire/resources/views/**/*.blade.php';
+ @source '../../vendor/emaia/laravel-hotwire/src/Components/**/*.php';
```

Apps installed via `hotwire:install` from `0.32.0` onwards get this automatically — the change applies only to apps installed on an earlier version.

#### 2. Re-publish the CSS stub if you customised it

If you ran `hotwire:install` before `0.32.0` and have *not* customised `resources/css/app.css`, the simplest path is:

```bash
php artisan hotwire:install --only=css --force
```

If you *have* customised the file, copy the new pieces manually:

- `@import "tailwindcss";`
- `@custom-variant turbo-*` / `form-busy` / `frame-busy` / `in-turbo-frame` / `modal` / `dark` directives.
- `@theme inline { … }` block mapping `--color-*` tokens to the underlying CSS variables (used by Tailwind utilities like `bg-primary`, `text-muted-foreground`).
- `@layer base { * { border-color: var(--border); outline-color: var(--ring); } body { background-color: var(--background); color: var(--foreground); } }`.
- `:root { … }` light palette and `[data-theme="dark"] { … }` dark overrides.

Full reference: [`docs/theming.md`](theming.md).

#### 3. Wire up the dark mode trigger (optional)

`[data-theme="dark"]` on `<html>` activates the dark palette. There is no toggle component yet — it lands in `0.34.0`. If you want dark mode now, set the attribute yourself (server-side, inline script, or via your own toggle).

```html
<html data-theme="dark">
```

### Visual diff — what apps relying on the old paint will see

If your app *relied* on the prior appearance of shipped components (e.g. screenshots, design specs), expect these substitutions in the rendered HTML:

| Component area | Before (`0.31.x`) | After (`0.32.0`) |
|---|---|---|
| Body background | not styled by the package | `var(--background)` via `@layer base` |
| Modal panel | `bg-white` + `bg-gray-50` borders | `bg-background ring-1 ring-foreground/10` |
| Modal backdrop | `bg-slate-600/80` | `bg-black/10 backdrop-blur-xs` |
| Confirm-dialog confirm | `bg-red-600 hover:bg-red-700 text-white` | `bg-destructive text-destructive-foreground hover:bg-destructive/90` |
| Confirm-dialog cancel | `bg-white border-gray-300 text-gray-700` | `bg-background border-input text-secondary-foreground hover:bg-accent` |
| Input / Textarea / Select | `border-gray-300 bg-white text-gray-900` | `border-input bg-background text-foreground focus-visible:border-ring focus-visible:ring-ring/50` |
| Input error state | `border-red-500` | `aria-invalid:border-destructive aria-invalid:ring-destructive/20` |
| Label | `text-gray-700` | `text-foreground` |
| Description | `text-gray-600` | `text-muted-foreground` |
| Error message | `text-red-600` | `text-destructive` |
| Spinner / Scroll-progress | hardcoded hues | semantic tokens (`text-foreground/50`, `bg-primary`) |
| Inline SVG close buttons | one-off `<svg>` per component | `<x-hwc::icon name="x" />` |

Custom classes you pass through `class="..."` on the component are unaffected — only the package's own defaults moved.

### Verifying the upgrade

1. Run `php artisan hotwire:check` — confirms catalog npm deps are present, reports any controller files diverging from the vendor's `// @hotwire-package` marker.
2. Run `bun run build` (or `vite build`) and visually inspect the resulting `dist/assets/*.css`. Confirm that semantic tokens (`--background`, `--foreground`, `--primary`, …) are defined.
3. Open the components in a browser:
   - Light mode: `<html>` with no `data-theme` attribute.
   - Dark mode: set `data-theme="dark"` on `<html>` and confirm the palette inverts.

### Rollback

If the visual change is disruptive and you need to ship before adopting:

- Pin to `^0.31.0` in `composer.json` until you can schedule the visual migration.
- The class substitutions are not one-way — you can keep overriding the package classes per-component via the `class="..."` attribute on each `<x-hwc::*>` instance if a holistic re-theme is not yet feasible.
