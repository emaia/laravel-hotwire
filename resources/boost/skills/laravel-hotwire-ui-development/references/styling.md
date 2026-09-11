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

Light is the default when no `data-theme` exists. Keep text/background contrast at least 4.5:1. Application-authored
Tailwind `dark:` utilities match any descendant of a dark ancestor and cross nested light boundaries; Nova's packaged
surfaces do not. Prefer semantic tokens. When application dark-only declarations must differ, put them in a top-level
scope:

```css
@scope ([data-theme="dark"]) to ([data-theme="light"]) {
    :where(:scope, :scope *)[data-slot="button"][data-variant="outline"] {
        @apply border-input bg-input/30 hover:bg-input/50;
    }
}
```

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
- Start a visual system without inheriting Nova, with empty base rules as an anatomy checklist:
  `php artisan hotwire:make-preset brand`.
- Customize Nova's complete selector structure: `php artisan hotwire:make-preset brand --from=nova`.
- Ship only selected modules: `php artisan hotwire:styles` and regenerate after changing the selection or upgrading.
- Include Stream/JavaScript-only modules explicitly with `--include`.

Scaffolds and clones are application-owned snapshots. Compare a fresh temporary output on package upgrades and merge
relevant slot, foundation and contract changes manually. `--force` replaces the target; it does not merge. Keep package
foundation imports and validate with `php artisan hotwire:check --preset=brand --no-interaction`. Then run the application
production build and smoke-test the result. Static validation catches import, foundation and slot-contract errors; it
does not compile Tailwind utilities or prove visual, state or accessibility behavior.

Never edit generated selective bundles. Regenerate them from the command and keep custom rules in separate application
stylesheets.
