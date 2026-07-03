# Presets

Laravel Hotwire components render semantic attributes (`data-slot`, `data-variant`, `data-size`, `data-state`). Presets turn those attributes into Tailwind styles.

## Install with a preset

```bash
php artisan hotwire:install --preset=nova
```

The installer writes a thin `resources/css/app.css` that imports Tailwind, scans package CSS, and enables one preset:

```css
@import "tailwindcss";

@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css';

@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css';
```

## Override a component

Add app CSS after the preset import and target semantic slots:

```css
[data-slot="button"][data-variant="default"] {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
}
```

## Scoped overrides

The selected preset applies globally. Preset files may also include scoped selectors, so app CSS can opt a page region into targeted overrides with `data-preset`:

```blade
<section data-preset="compact">
    <hw:button>Save</hw:button>
</section>
```

Only CSS that has been imported can respond to `data-preset`. A `data-preset` attribute does nothing by itself.
