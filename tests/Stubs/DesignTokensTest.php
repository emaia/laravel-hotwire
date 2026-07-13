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
        ->toContain('[data-slot="field-group"]')
        ->toContain('[data-slot="field-description"]')
        ->toContain('[data-slot="field-description"] > a')
        ->toContain('[data-slot="field-error"]')
        ->toContain('[data-slot="field-error"] > ul')
        ->toContain('[data-slot="field-content"]')
        ->toContain('[data-slot="field-separator"]')
        ->toContain('[data-slot="button"]')
        ->toContain('[data-slot="badge"]')
        ->toContain('[data-slot="button-group"]')
        ->toContain('[data-slot="button-group-text"]')
        ->toContain('[data-slot="button-group-separator"]')
        ->toContain('[data-slot="card"]')
        ->toContain('[data-slot="card-header"]')
        ->toContain('[data-slot="card-footer"]')
        ->toContain('[data-slot="empty-state"]')
        ->toContain('[data-slot="empty-state-media"]')
        ->toContain('[data-slot="kbd"]')
        ->toContain('[data-slot="kbd-group"]')
        ->toContain('[data-slot="skeleton"]')
        ->toContain('[data-slot="separator"]')
        ->toContain('[data-slot="alert"]')
        ->toContain('[data-slot="alert-title"]')
        ->toContain('[data-slot="item"]')
        ->toContain('[data-slot="item-media"]')
        ->toContain('[data-slot="item-separator"]')
        ->toContain('[data-slot="table-container"]')
        ->toContain('[data-slot="table-row"]')
        ->toContain('[data-slot="input"]')
        ->toContain('[data-slot="modal-panel"]')
        ->toContain('[data-slot="alert-dialog-panel"]');
});

it('keeps item icon media unframed like the shadcn base-nova reference', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="item-media"][data-variant="icon"] { @apply [&>[data-slot=icon]]:size-4; }')
        ->not->toContain('[data-slot="item-media"][data-variant="icon"] { @apply size-8 rounded-md border border-border bg-background');
});

it('applies destructive alert color to the alert description', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="alert"][data-variant="destructive"] { @apply bg-card text-destructive')
        ->toContain('[data-slot="alert"][data-variant="destructive"] > [data-slot="alert-description"] { @apply text-destructive/90; }');
});

it('keeps item media sizing driven by the media variant and parent item size', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="item-media"][data-variant="default"] { @apply bg-transparent; }')
        ->toContain('[data-slot="item-media"][data-variant="image"] { @apply size-10 overflow-hidden rounded-sm')
        ->toContain('[data-slot="item"][data-size="sm"] [data-slot="item-media"][data-variant="image"] { @apply size-8; }')
        ->toContain('[data-slot="item"][data-size="xs"] [data-slot="item-media"][data-variant="image"] { @apply size-6; }')
        ->not->toContain('[data-slot="item-media"][data-variant="default"] { @apply size-8; }');
});

it('does not make field groups size containers by default', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="field-group"]')
        ->not->toContain('container-type: inline-size')
        ->not->toContain('@container field-group');
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

it('does not hard-code clear input visibility in the preset', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="clear-input-button"]')
        ->not->toContain('[data-slot="clear-input-button"] { @apply absolute right-1.5 hidden items-center; }');
});

it('styles checkable inputs when they are wrapped by labels', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="field-label"]:has(:is([data-slot="input"], [data-slot="checkbox-group-input"])[data-checkable="true"])')
        ->toContain('[data-slot="field"][data-orientation="horizontal"]:has(> [data-slot="field-content"])')
        ->toContain('[data-slot="field"]:has(> [data-slot="field-content"]) > :is([role="checkbox"], [role="radio"], [data-checkable="true"])')
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
        ->toContain('[data-slot="dropdown-group"]')
        ->toContain('[data-slot="dropdown-label"]')
        ->toContain('[data-slot="dropdown-item"]')
        ->toContain('[data-slot="dropdown-separator"]')
        ->toContain('[data-slot="dropdown-shortcut"]')
        ->toContain('[data-slot="dropdown-menu"][data-width="default"]')
        ->toContain('[data-slot="dropdown-menu"][data-align="start"]');
});

it('keeps dropdown menu subcomponent styling aligned with the nova reference', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="dropdown-label"] { @apply px-1.5 py-1 text-xs font-medium text-muted-foreground data-[inset=true]:pl-7; }')
        ->toContain('[data-slot="dropdown-item"] { @apply relative flex w-full cursor-default select-none appearance-none items-center gap-1.5 rounded-md border-0 bg-transparent px-1.5 py-1 text-left text-sm text-popover-foreground outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground')
        ->toContain('[data-slot="dropdown-item"]:is(:hover, :focus-visible) [data-slot="dropdown-shortcut"] { @apply text-accent-foreground; }')
        ->toContain('[data-slot="dropdown-item"][data-variant="destructive"] { @apply text-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:bg-destructive/10 focus-visible:text-destructive')
        ->toContain('[data-slot="dropdown-separator"] { @apply -mx-1 my-1 h-px bg-border; }')
        ->toContain('[data-slot="dropdown-shortcut"] { @apply ml-auto text-xs tracking-widest text-muted-foreground; }')
        ->not->toContain('[data-slot="dropdown-label"] { @apply px-2 py-1.5 text-sm font-medium text-foreground')
        ->not->toContain('[data-slot="dropdown-item"] { @apply relative flex w-full cursor-default appearance-none items-center gap-2');
});

it('defines breadcrumb slots in the nova preset', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="breadcrumb"]')
        ->toContain('[data-slot="breadcrumb-list"]')
        ->toContain('[data-slot="breadcrumb-item"]')
        ->toContain('[data-slot="breadcrumb-link"]')
        ->toContain('[data-slot="breadcrumb-page"]')
        ->toContain('[data-slot="breadcrumb-separator"]')
        ->toContain('[data-slot="breadcrumb-ellipsis"]');
});

it('defines pagination slots in the nova preset', function () use ($novaPresetPath) {
    $css = file_get_contents($novaPresetPath);

    expect($css)
        ->toContain('[data-slot="pagination"]')
        ->toContain('[data-slot="pagination-content"]')
        ->toContain('[data-slot="pagination-item"]')
        ->toContain('[data-slot="pagination-link"]')
        ->toContain('[data-slot="pagination-previous"]')
        ->toContain('[data-slot="pagination-next"]')
        ->toContain('[data-slot="pagination-ellipsis"]')
        ->toContain('[data-slot="pagination-previous-label"]')
        ->toContain('[data-slot="pagination-next-label"]');
});
