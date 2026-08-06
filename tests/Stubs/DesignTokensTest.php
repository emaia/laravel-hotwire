<?php

$stubPath = realpath(__DIR__.'/../../stubs/resources/css/app.css');
$tokensPath = realpath(__DIR__.'/../../resources/css/tokens.css');
$variantsPath = realpath(__DIR__.'/../../resources/css/custom-variants.css');

dataset('design presets', fn () => collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
    ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => [$path]])
    ->all());

// --- Token system and install stub ---

it('contains @theme inline block', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))->toContain('@theme inline');
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
        '--color-sidebar',
        '--color-sidebar-foreground',
        '--color-sidebar-primary',
        '--color-sidebar-primary-foreground',
        '--color-sidebar-accent',
        '--color-sidebar-accent-foreground',
        '--color-sidebar-border',
        '--color-sidebar-ring',
    ];

    foreach ($required as $token) {
        expect($css)->toContain("{$token}: var(");
    }
});

it('declares all radius tokens', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    foreach (['--radius-sm', '--radius-md', '--radius-lg', '--radius-xl', '--radius-2xl', '--radius-3xl', '--radius-4xl'] as $token) {
        expect($css)->toContain($token);
    }
});

it('uses proportional scaling for radius derivations', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toContain('--radius-sm: calc(var(--radius) * 0.6)')
        ->toContain('--radius-md: calc(var(--radius) * 0.8)')
        ->toContain('--radius-xl: calc(var(--radius) * 1.4)')
        ->toContain('--radius-2xl: calc(var(--radius) * 1.8)')
        ->toContain('--radius-3xl: calc(var(--radius) * 2.2)')
        ->toContain('--radius-4xl: calc(var(--radius) * 2.6)');
});

it('contains light and dark OKLCH token values', function () use ($tokensPath) {
    $css = file_get_contents($tokensPath);

    expect($css)
        ->toContain(':root')
        ->toContain('[data-theme="dark"]')
        ->toContain('oklch(');

    foreach (['--background:', '--foreground:', '--primary:', '--primary-foreground:', '--destructive:', '--radius:'] as $variable) {
        expect($css)->toContain($variable);
    }
});

it('contains the base layer border and outline contract', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toContain('@layer base')
        ->toContain('@apply border-border outline-ring/50');
});

it('preserves all package custom variants', function () use ($variantsPath) {
    $css = file_get_contents($variantsPath);

    foreach (['turbo-preview', 'turbo-visit', 'form-busy', 'frame-busy', 'in-turbo-frame', 'in-remote-turbo-frame', 'modal', 'drawer', 'sheet', 'dark'] as $variant) {
        expect($css)->toContain("@custom-variant {$variant}");
    }
});

it('keeps the app css stub thin and scans package css', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)
        ->toContain('@import "tailwindcss"')
        ->toContain("@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css'")
        ->toContain("@source '../../vendor/emaia/laravel-hotwire/resources/css/**/*.css'")
        ->not->toContain('resources/views/**/*.blade.php')
        ->not->toContain('src/Components/**/*.php')
        ->not->toContain('@theme inline')
        ->not->toContain(':root {');
});

// --- Shared preset contracts ---

it('safelists exactly the utilities applied at runtime', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css');

    expect($css)->toContain('@source inline(');

    preg_match('/@source inline\("([^"]*)"\)/', $css, $match);
    $safelisted = preg_split('/\s+/', trim($match[1] ?? '')) ?: [];

    expect(array_diff($safelisted, runtimeAppliedClasses()))
        ->toBe([], 'Safelisted utilities that nothing applies at runtime. Utilities reached through @apply resolve at build time and must not be listed.')
        ->and(array_diff(runtimeAppliedClasses(), $safelisted))
        ->toBe([], 'Utilities applied at runtime but missing from @source inline(). Tailwind cannot see class names living in package JavaScript or Blade.');
});

it('leaves the runtime safelist to the structural stylesheet', function (string $preset) {
    // A preset restating it snapshots the list, and goes stale the next time a controller applies one.
    expect(file_get_contents($preset))
        ->not->toContain('@source inline(')
        ->toContain('@import "../structural.css";');
})->with('design presets');

/**
 * Utility classes the package applies at runtime, from strings Tailwind's scanner never reads:
 * JavaScript literals and Stimulus Classes API values declared in package Blade. State hooks are
 * excluded — the package toggles them for CSS to target, so Tailwind must never generate them, and
 * adding to that list asserts a new runtime class needs no utility behind it.
 *
 * @return string[]
 */
function runtimeAppliedClasses(): array
{
    $stateHooks = ['clear-input--touched', 'is-active'];
    $root = dirname(__DIR__, 2);
    $classes = [];

    foreach (File::allFiles($root.'/resources/js') as $file) {
        if (! in_array($file->getExtension(), ['js', 'ts'], true)) {
            continue;
        }

        preg_match_all('/classList\.(?:add|remove|toggle)\(\s*["\']([^"\']+)["\']/', $file->getContents(), $matches);
        $classes = [...$classes, ...$matches[1]];
    }

    foreach (File::allFiles($root.'/resources/views') as $file) {
        preg_match_all('/data-[a-z-]+-class["\']?\s*(?:=>|=)\s*["\']([^"\']+)["\']/', $file->getContents(), $matches);
        $classes = [...$classes, ...$matches[1]];
    }

    $classes = array_diff(array_unique($classes), $stateHooks);

    sort($classes);

    return array_values($classes);
}

it('keeps closed floating surfaces renderable until Presence hides them', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="multi-select-content"])[data-state="closed"]')
        ->toContain('[data-state="open"]')
        ->not->toMatch('/\[data-state="closed"\][^{]*\{[^}]*\b(?:display:\s*none|@apply[^;}]*\bhidden\b)/s');
})->with('design presets');

it('uses pre-connect and resolved color scheme hooks', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('html[data-color-scheme-mode="system"]')
        ->toContain('html[data-color-scheme-mode="light"]')
        ->toContain('html[data-color-scheme-mode="dark"]')
        ->toContain('html:not([data-color-scheme-mode])')
        ->toContain('[data-mode-icon="system"]')
        ->toContain('[data-scheme-icon="light"]')
        ->toContain('[data-scheme-icon="dark"]');
})->with('design presets');

it('keeps file upload state and bare dropzone contracts', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-file-upload-dropzone-variant="bare"]')
        ->toContain('[data-dragging="true"]')
        ->toContain('[aria-invalid="true"]')
        ->toContain('[data-state="error"]')
        ->toContain('[data-upload-state="error"]')
        ->toContain('[data-slot="file-upload-image-preview"]:not([hidden])')
        ->toContain('[data-loading="true"]')
        ->not->toContain('[data-slot="file-upload-dropzone"] { @apply flex min-h-32');
})->with('design presets');

it('keeps sidebar icon collapse responsive and labels in layout flow', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="sidebar"][data-collapsible="icon"] [data-slot="sidebar-menu-button"] { @apply md:')
        ->toContain('[data-slot="sidebar-menu-button"] > span:not([data-slot="avatar"])')
        ->not->toMatch('/\[data-slot="sidebar-menu-button"\] > span:not\([^}]+@apply[^;}]*(?:sr-only|opacity-0)/s');
})->with('design presets');

it('drives native slider tracks from the controller value', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-orientation="horizontal"]::-webkit-slider-runnable-track')
        ->toContain('[data-orientation="horizontal"]:dir(rtl)::-webkit-slider-runnable-track')
        ->toContain('[data-orientation="vertical"]::-webkit-slider-runnable-track')
        ->toContain('var(--slider-value)')
        ->not->toContain('::-webkit-slider-thumb { margin-top:')
        ->not->toContain('[data-slot="slider"]:hover::-webkit-slider-thumb')
        ->not->toContain('[data-slot="slider"]:hover::-moz-range-thumb');
})->with('design presets');

it('keeps the flash container eligible for top-layer stacking', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-hotwire-top-layer][popover]:is([data-slot="modal-overlay"], [data-slot="alert-dialog-overlay"], [data-slot="drawer-overlay"], [data-slot="sheet-overlay"], [data-slot="sidebar"], [data-slot="flash-container"])')
        ->toContain('[data-slot="flash-container"]')
        ->not->toContain('[data-slot="flash-container"] { @apply contents; }');
})->with('design presets');

it('keeps clear input visibility owned by its controller', function (string $preset) {
    $declaration = presetDeclaration(file_get_contents($preset), '[data-slot="clear-input-button"]');

    expect($declaration)->not->toMatch('/\bhidden\b/');
})->with('design presets');

it('styles generated rich text DOM through granular hooks', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="rich-text"]')
        ->toContain('.ProseMirror:focus-visible')
        ->toContain('[data-slot="rich-text-toolbar-button"]')
        ->toContain('[data-slot="rich-text-editor"] .ProseMirror')
        ->toContain('p.is-editor-empty:first-child::before')
        ->toContain('aria-pressed:')
        ->toContain('data-[active=true]:');
})->with('design presets');

it('uses Floating UI geometry tokens instead of css-only offsets', function (string $preset) {
    $css = file_get_contents($preset);

    foreach (['--available-height', '--anchor-width', '--transform-origin'] as $token) {
        expect($css)->toContain($token);
    }

    expect($css)
        ->not->toContain('[data-slot="dropdown-menu"] { @apply absolute')
        ->not->toContain('slide-in-from');
})->with('design presets');

it('drives floating presence from semantic state and motion hooks', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-state="closed"]')
        ->toContain('[data-state="open"]')
        ->toContain('[data-motion="none"]')
        ->toContain('[data-presence="instant"]')
        ->toContain('@media (prefers-reduced-motion: reduce)');
})->with('design presets');

it('drives overlay motion from semantic presence state', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="modal-overlay"][data-state="open"]')
        ->toContain('[data-slot="alert-dialog-overlay"][data-state="open"]')
        ->toContain('[data-slot="drawer-overlay"][data-state="closed"]')
        ->toContain('[data-slot="sheet-overlay"][data-state="closed"]')
        ->toContain('[data-slot="sidebar"][data-mobile-state="closed"]')
        ->toContain('[data-presence="leaving"]')
        ->not->toContain('data-modal-dialog-hidden-class')
        ->not->toContain('data-drawer-dialog-hidden-class');
})->with('design presets');

it('keeps multi-select state selectors aligned with controller output', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="multi-select-content"]')
        ->toContain('[data-slot="multi-select-option"][data-selected="true"]')
        ->toContain('[data-slot="multi-select-select-all"][data-selected="true"]')
        ->toContain('[data-slot="multi-select-select-all"][data-indeterminate="true"]')
        ->toContain('[data-slot="multi-select-indicator"]');
})->with('design presets');

it('preserves component custom-property contracts', function (string $preset) {
    $css = file_get_contents($preset);

    expect($css)
        ->toContain('[data-slot="aspect-ratio"]')
        ->toContain('aspect-(--ratio)')
        ->toContain('[data-slot="sticky"][data-side="top"]')
        ->toContain('--sticky-offset')
        ->toContain('[data-slot="progress-indicator"]')
        ->toContain('width: var(--progress-value)');
})->with('design presets');

// --- Known bug guards ---

it('does not reintroduce known clipping, stacking, sizing or marker bugs', function (string $preset) {
    $css = file_get_contents($preset);
    $avatar = presetDeclaration($css, '[data-slot="avatar"]');
    $avatarImage = presetDeclaration($css, '[data-slot="avatar-image"]');
    $itemIcon = presetDeclaration($css, '[data-slot="item-media"][data-variant="icon"]');
    $itemDefault = presetDeclaration($css, '[data-slot="item-media"][data-variant="default"]');
    $spinner = presetDeclaration($css, '[data-slot="spinner"]');

    expect($avatar)->not->toContain('overflow-hidden')
        ->and($avatarImage)->not->toMatch('/\bz-\d+/')
        ->and($itemIcon)->not->toContain('size-8')->not->toContain('border-border')->not->toContain('bg-background')
        ->and($itemDefault)->not->toMatch('/\bsize-/')
        ->and($spinner)->not->toMatch('/\btext-[^\s;]+/')
        ->and($css)->not->toContain('container-type: inline-size')
        ->not->toContain('@container field-group')
        ->not->toMatch('/@apply[^;]*\bgroup\b/');
})->with('design presets');

it('keeps input-group focus and addon layout owned by the group', function (string $preset) {
    $css = file_get_contents($preset);
    $inlineStart = presetDeclaration($css, '[data-slot="input-group-addon"][data-align="inline-start"]');
    $inlineEnd = presetDeclaration($css, '[data-slot="input-group-addon"][data-align="inline-end"]');

    expect($inlineStart)->not->toContain('absolute')
        ->and($inlineEnd)->not->toContain('absolute')
        ->and($css)->toContain('focus-visible:ring-0')
        ->not->toContain('[data-slot="input-group-control"] { @apply pl-8')
        ->not->toContain('[data-slot="input-group-control"] { @apply pr-8');
})->with('design presets');

function presetDeclaration(string $css, string $selector): string
{
    preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/s', $css, $matches);

    return $matches[1] ?? '';
}
