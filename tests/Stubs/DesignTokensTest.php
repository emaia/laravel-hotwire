<?php

$stubPath = realpath(__DIR__.'/../../stubs/resources/css/app.css');
$tokensPath = realpath(__DIR__.'/../../resources/css/tokens.css');
$variantsPath = realpath(__DIR__.'/../../resources/css/custom-variants.css');
$novaPresetPath = realpath(__DIR__.'/../../resources/css/presets/nova.css');

// --- Token system ---

it('contains @theme inline block', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);
    expect($css)->toContain('@theme inline');
});

it('declares all semantic color tokens', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    $required = [
        '--color-background',
        '--color-foreground',
        '--color-card',
        '--color-card-foreground',
        '--color-popover',
        '--color-popover-foreground',
        '--color-primary',
        '--color-primary-foreground',
        '--color-secondary',
        '--color-secondary-foreground',
        '--color-muted',
        '--color-muted-foreground',
        '--color-accent',
        '--color-accent-foreground',
        '--color-destructive',
        '--color-destructive-foreground',
        '--color-border',
        '--color-input',
        '--color-ring',
    ];

    foreach ($required as $token) {
        expect($css)->toContain("{$token}: var(");
    }
});

it('declares all radius tokens', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    $required = [
        '--radius-sm',
        '--radius-md',
        '--radius-lg',
        '--radius-xl',
        '--radius-2xl',
        '--radius-3xl',
        '--radius-4xl',
    ];

    foreach ($required as $token) {
        expect($css)->toContain($token);
    }
});

it('uses proportional scaling (multiplication) for radius derivations', function () use ($tokensPath) {
    // Pixel arithmetic (-/+ Npx) breaks proportions when the app overrides
    // --radius from the default. Proportional scaling keeps sm/md/xl/2xl/3xl/4xl
    // in the same visual relation to the base regardless of what the app sets.
    $css = file_get_contents($tokensPath);

    expect($css)
        ->toContain('--radius-sm: calc(var(--radius) * 0.6)')
        ->toContain('--radius-md: calc(var(--radius) * 0.8)')
        ->toContain('--radius-xl: calc(var(--radius) * 1.4)')
        ->toContain('--radius-2xl: calc(var(--radius) * 1.8)')
        ->toContain('--radius-3xl: calc(var(--radius) * 2.2)')
        ->toContain('--radius-4xl: calc(var(--radius) * 2.6)');
});

// --- Light mode ---

it('contains :root with OKLCH values', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);
    expect($css)->toContain(':root');

    $requiredVars = [
        '--background:',
        '--foreground:',
        '--primary:',
        '--primary-foreground:',
        '--secondary:',
        '--secondary-foreground:',
        '--muted:',
        '--muted-foreground:',
        '--accent:',
        '--accent-foreground:',
        '--destructive:',
        '--destructive-foreground:',
        '--border:',
        '--input:',
        '--ring:',
        '--radius:',
    ];

    foreach ($requiredVars as $var) {
        expect($css)->toContain($var);
    }

    expect($css)->toContain('oklch(');
});

// --- Dark mode ---

it('contains data-theme dark with overrides', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    expect($css)->toContain('[data-theme="dark"]');

    $darkVars = [
        '--background:',
        '--foreground:',
        '--primary:',
        '--primary-foreground:',
        '--destructive:',
    ];

    foreach ($darkVars as $var) {
        expect($css)->toContain("{$var} ");
    }
});

// --- Preserve existing features ---

it('contains @layer base with global border/outline rules', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    expect($css)->toContain('@layer base');
    expect($css)->toContain('@apply border-border outline-ring/50');
});

it('scans package CSS instead of Blade or PHP sources', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)
        ->toContain("@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css'")
        ->not->toContain('resources/views/**/*.blade.php')
        ->not->toContain('src/Components/**/*.php');
});

it('safelists runtime classes applied by Stimulus controllers', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('@source inline(')
        ->toContain('pointer-events-none')
        ->toContain('scale-95')
        ->toContain('duration-100');
});

it('preserves existing @custom-variant rules', function () use ($variantsPath) {
    $css = file_get_contents($variantsPath);

    $variants = [
        'turbo-preview',
        'turbo-visit',
        'form-busy',
        'frame-busy',
        'in-turbo-frame',
        'in-remote-turbo-frame',
        'modal',
        'dark',
    ];

    foreach ($variants as $variant) {
        expect($css)->toContain("@custom-variant {$variant}");
    }
});

it('keeps the app css stub thin and imports the default preset', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)
        ->toContain('@import "tailwindcss"')
        ->toContain("@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css'")
        ->not->toContain('@theme inline')
        ->not->toContain('@custom-variant turbo-preview');
});

it('defines component styles in the nova preset via data-slot selectors', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="button"]')
        ->toContain('[data-slot="input"]')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="alert-dialog-panel"]');
});

it('does not apply Tailwind marker-only classes inside presets', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)->not->toMatch('/@apply[^;]*\bgroup\b/');
});

it('hides the native select arrow when rendering a custom select icon', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="select"] { @apply appearance-none pr-8; }')
        ->toContain('[data-slot="select-icon"]');
});

it('styles checkable inputs when they are wrapped by labels', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="label"]:has(:is([data-slot="input"], [data-slot="checkbox-group-input"])[data-checkable="true"])')
        ->toContain(':is([data-slot="input"], [data-slot="checkbox-group-input"])[data-checkable="true"]')
        ->toContain('appearance-none')
        ->toContain('aspect-square h-4 max-h-4 min-h-4 w-4 min-w-4 max-w-4')
        ->toContain('checked:border-primary checked:bg-primary')
        ->toContain('[type="checkbox"]:indeterminate')
        ->not->toContain('indeterminate:border-primary indeterminate:bg-primary')
        ->toContain('::before')
        ->toContain('opacity: 0')
        ->toContain(':checked::before { opacity: 1')
        ->toContain('[type="checkbox"]:indeterminate::before')
        ->toContain('aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20');
});

it('styles rich text via granular slots instead of textarea-only styles', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="rich-text"]')
        ->toContain('[data-slot="rich-text"]:has(.ProseMirror:focus-visible)')
        ->toContain('[data-slot="rich-text-toolbar"]')
        ->toContain('[data-slot="rich-text-toolbar-button"]')
        ->toContain('[data-slot="rich-text-editor"] .ProseMirror')
        ->toContain('p.is-editor-empty:first-child::before')
        ->toContain('aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20')
        ->not->toContain('[data-slot="textarea"], [data-slot="rich-text"]');
});

it('defines overlay and menu slots in the nova preset', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="alert-dialog-action"]')
        ->toContain('[data-slot="alert-dialog-cancel"]')
        ->toContain('[data-slot="dropdown"]')
        ->toContain('[data-slot="dropdown-trigger"]')
        ->toContain('[data-slot="dropdown-trigger-icon"]')
        ->toContain('[data-slot="dropdown-trigger"][aria-expanded="true"] [data-slot="dropdown-trigger-icon"]')
        ->toContain('[data-slot="dropdown-menu"]')
        ->toContain('[data-slot="dropdown-menu"][data-width="default"]')
        ->toContain('[data-slot="dropdown-menu"][data-align="start"]');
});
