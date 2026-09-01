# Styling and presets

## Semantic tokens

Use foreground/background pairs such as `background`/`foreground`, `card`/`card-foreground`,
`popover`/`popover-foreground`, `primary`/`primary-foreground`, `secondary`/`secondary-foreground`,
`muted`/`muted-foreground`, `accent`/`accent-foreground`, and `destructive`/`destructive-foreground`.
Borders and focus use `border`, `input` and `ring`; sidebar tokens have their own `sidebar-*` namespace.

Application overrides belong after the preset import. Guard light-only overrides so they do not also match dark mode:

```css
:root:not([data-theme="dark"]),
[data-theme="light"] {
    --primary: oklch(...);
}

[data-theme="dark"] {
    --primary: oklch(...);
}
```

Light is the default when no `data-theme` exists. Keep text/background contrast at least 4.5:1. A nested explicit light
island inside a dark ancestor can still match Tailwind ancestor `dark:` variants, so prefer semantic tokens.

## Structural versus visual CSS

- Structural CSS owns mechanics that otherwise break behavior: track geometry, collapse mechanics, top-layer resets and
  runtime utility safelists.
- Presets own appearance and target `data-slot`, state, variant, size and native/ARIA attributes.
- Imported presets are processed directly. Do not scan them with `@source`.
- Import the public preset entry point, not private Nova module files.
- Preserve generated source order so shared primitives and dependent modules cascade predictably.

Presence-driven overlays stay rendered while exit CSS runs. Style closed state for motion, but do not apply
`display:none`; Presence adds `hidden` after motion settles. Parent overlay state selectors must use direct-child scoping
so an open nested overlay does not visually reopen its parent.

## Choosing the workflow

- Override only colors/radius: change semantic tokens after the preset import.
- Change the full component visual system: `php artisan hotwire:make-preset brand --from=nova`.
- Ship only selected modules: `php artisan hotwire:styles` and regenerate after changing the selection or upgrading.
- Include Stream/JavaScript-only modules explicitly with `--include`.

Never edit generated selective bundles. Regenerate them from the command and keep custom rules in separate application
stylesheets.
