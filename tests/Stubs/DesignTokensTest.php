<?php

use Emaia\LaravelHotwire\Support\CssPresetFiles;

$stubPath = realpath(__DIR__.'/../../stubs/resources/css/app.css');
$tokensPath = realpath(__DIR__.'/../../resources/css/tokens.css');
$variantsPath = realpath(__DIR__.'/../../resources/css/custom-variants.css');

dataset('design presets', fn () => collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
    ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => [pathinfo($path, PATHINFO_FILENAME)]])
    ->all());

function presetVisualCss(string $preset): string
{
    return app(CssPresetFiles::class)->source($preset)->visualCss();
}

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
        '--color-backdrop',
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

it('advertises the active browser color scheme on default and explicit theme scopes', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toMatch('/:root\s*\{[^}]*color-scheme:\s*light;/s')
        ->toMatch('/\[data-theme="light"\]\s*\{[^}]*color-scheme:\s*light;/s')
        ->toMatch('/\[data-theme="dark"\]\s*\{[^}]*color-scheme:\s*dark;/s');
});

it('guards package light tokens from explicit dark roots', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toMatch('/:where\(:root:not\(\[data-theme="dark"\]\)\)\s*,\s*\[data-theme="light"\]\s*\{/s');
});

it('scopes backdrop defaults with the active color theme', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toMatch('/:where\(:root:not\(\[data-theme="dark"\]\)\)\s*,\s*\[data-theme="light"\]\s*\{[^}]*--backdrop:/s')
        ->toMatch('/\[data-theme="dark"\]\s*\{[^}]*--backdrop:/s');
});

it('contains the base layer border and outline contract', function () use ($tokensPath) {
    expect(file_get_contents($tokensPath))
        ->toContain('@layer base')
        ->toContain('@apply border-border outline-ring/50');
});

it('does not scan package sources because presets and runtime safelists compile through imports', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)
        ->not->toContain('@source')
        ->not->toContain('resources/views/**/*.blade.php')
        ->not->toContain('src/Components/**/*.php');
});

it('safelists runtime classes applied by Stimulus controllers', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css');

    expect($css)
        ->toContain('@source inline(')
        ->toContain('hidden')
        ->toContain('overflow-hidden');
});

it('applies shared button styles to attachment actions', function () {
    $lines = collect(explode("\n", presetVisualCss('nova')));
    $base = $lines->first(fn (string $line): bool => str_contains($line, ':is([data-slot="button"]'));
    $ghost = $lines->first(fn (string $line): bool => str_contains($line, ':is([data-slot="button"]') && str_contains($line, '[data-variant="ghost"]'));
    $iconXs = $lines->first(fn (string $line): bool => str_contains($line, ':is([data-slot="button"]') && str_contains($line, '[data-size="icon-xs"]'));

    expect($base)->toContain('[data-slot="attachment-action"]')
        ->and($ghost)->toContain('[data-slot="attachment-action"]')
        ->and($iconXs)->toContain('[data-slot="attachment-action"]');
});

it('applies shared button styles and visibility states to back to top', function () {
    $lines = collect(explode("\n", presetVisualCss('nova')));
    $buttonRules = $lines->filter(fn (string $line): bool => str_contains($line, ':is([data-slot="button"]'));
    $css = $lines->implode("\n");

    expect($buttonRules)->toHaveCount(15);

    $buttonRules->each(fn (string $line) => expect($line)->toContain('[data-slot="back-to-top"]'));

    expect($css)
        ->toContain('[data-slot="back-to-top"][data-visible] { @apply fixed end-4 bottom-4 z-40 rounded-full shadow-lg transition-opacity sm:end-6 sm:bottom-6; }')
        ->toContain('@media (prefers-reduced-motion: reduce) {')
        ->toContain('[data-slot="back-to-top"][data-visible] { transition: none; }')
        ->toContain('[data-slot="back-to-top"][data-visible="false"] { @apply pointer-events-none opacity-0; }')
        ->toContain('[data-slot="back-to-top"][data-visible="true"] { @apply pointer-events-auto opacity-100; }')
        ->not->toContain('[data-slot="back-to-top"] { @apply transition-none; }')
        ->not->toContain('motion-reduce:transition-none');
});

it('keeps closed floating surfaces visible until Presence applies hidden', function () {
    $css = presetVisualCss('nova');

    expect($css)
        ->toContain('[data-slot="multi-select-content"])[data-state="closed"] { @apply pointer-events-none scale-95 opacity-0; }')
        ->not->toContain('[data-state="closed"] { @apply hidden');
});

it('uses the pre-connect color scheme mode to avoid toggle icon flicker', function () {
    $css = presetVisualCss('nova');

    expect($css)
        ->toContain('html[data-color-scheme-mode="system"] [data-slot="color-scheme-toggle"][data-color-scheme-modes-value~="system"] [data-mode-icon="system"]')
        ->toContain('html[data-color-scheme-mode="light"] [data-slot="color-scheme-toggle"] [data-scheme-icon="light"]')
        ->toContain('html[data-color-scheme-mode="dark"] [data-slot="color-scheme-toggle"] [data-scheme-icon="dark"]')
        ->toContain('html:not([data-color-scheme-mode]) [data-slot="color-scheme-toggle"][data-mode="system"][data-color-scheme-modes-value~="system"] [data-mode-icon="system"]');
});

it('uses resolved icons when system is outside a color scheme toggle cycle', function () {
    $css = presetVisualCss('nova');

    expect($css)
        ->toContain('html[data-color-scheme-mode="system"] [data-slot="color-scheme-toggle"][data-color-scheme-modes-value~="system"] [data-mode-icon="system"]')
        ->toContain('html[data-color-scheme-mode="system"][data-theme="light"] [data-slot="color-scheme-toggle"]:not([data-color-scheme-modes-value~="system"]) [data-scheme-icon="light"]')
        ->toContain('html[data-color-scheme-mode="system"][data-theme="dark"] [data-slot="color-scheme-toggle"]:not([data-color-scheme-modes-value~="system"]) [data-scheme-icon="dark"]')
        ->toContain('html:not([data-color-scheme-mode]) [data-slot="color-scheme-toggle"][data-mode="system"]:not([data-color-scheme-modes-value~="system"])[data-scheme="light"] [data-scheme-icon="light"]');
});

it('preserves existing @custom-variant rules', function () use ($variantsPath) {
    $css = file_get_contents($variantsPath);

    foreach (['turbo-preview', 'turbo-visit', 'form-busy', 'frame-busy', 'in-turbo-frame', 'in-remote-turbo-frame', 'modal', 'drawer', 'sheet', 'dark'] as $variant) {
        expect($css)->toContain("@custom-variant {$variant}");
    }
});

it('keeps the app css stub thin', function () use ($stubPath) {
    $css = file_get_contents($stubPath);

    expect($css)
        ->toContain('@import "tailwindcss"')
        ->toContain("@import '../../vendor/emaia/laravel-hotwire/resources/css/presets/nova.css'")
        ->not->toContain('@source')
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

it('rides transition-behavior inside the shorthand, never as its own declaration', function () {
    // Lightning CSS reorders declarations. A `transition` shorthand emitted after a standalone
    // `transition-behavior` resets it to normal, and the Accordion then snaps shut instead of
    // collapsing — the source order looks correct and the built stylesheet is wrong.
    $stylesheets = [
        'structural.css' => file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css'),
    ];

    foreach (app(CssPresetFiles::class)->names() as $preset) {
        foreach (app(CssPresetFiles::class)->source($preset)->visualStylesheets() as $index => $css) {
            $stylesheets["{$preset} visual source {$index}"] = $css;
        }
    }

    foreach ($stylesheets as $name => $css) {
        expect($css)->not->toContain('transition-behavior:', "{$name} declares transition-behavior on its own line.");
    }
});

it('keeps the Accordion collapse in the structural stylesheet', function () {
    // Mechanics, not looks: a preset left to restate these would ship an accordion that snaps shut.
    expect(file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css'))
        ->toContain('@supports selector(::details-content)')
        ->toContain('content-visibility 180ms ease-out allow-discrete')
        ->toContain('block-size: calc-size(auto, size)');
});

it('keeps preset-independent component mechanics in the structural stylesheet', function () {
    $structural = file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css');
    $visual = presetVisualCss('nova');

    expect($structural)
        ->toContain('[data-slot="aspect-ratio"]')
        ->toContain('aspect-ratio: var(--ratio)')
        ->toContain('[data-slot="sticky"][data-side="top"]')
        ->toContain('inset-block-start: var(--sticky-offset)')
        ->toContain('@keyframes hotwire-reveal-rise')
        ->toContain('@keyframes hotwire-reveal-flat')
        ->toContain('@keyframes hotwire-reveal-fade')
        ->toContain('[data-hotwire-top-layer][popover]:is([data-slot="modal-overlay"]')
        ->toContain('[data-hotwire-top-layer][popover]:is([data-slot="dropdown-menu"]')
        ->and($visual)
        ->not->toContain('[data-slot="aspect-ratio"]')
        ->not->toContain('@keyframes hotwire-reveal-')
        ->not->toContain('[data-hotwire-top-layer]');
});

it('renders Turbo Frames as block-level containers from the structural stylesheet', function () {
    $structural = file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css');

    expect(presetDeclaration($structural, 'turbo-frame'))->toContain('display: block');
});

it('uses a semantic backdrop token instead of a raw utility color', function (string $preset) use ($tokensPath) {
    $visual = presetVisualCss($preset);
    $selectors = [
        '[data-slot="modal-backdrop"], [data-slot="alert-dialog-backdrop"]',
        '[data-slot="drawer-backdrop"]',
        '[data-slot="sheet-backdrop"]',
        '[data-slot="sidebar-backdrop"]',
    ];

    expect(file_get_contents($tokensPath))
        ->toContain('--color-backdrop: var(--backdrop)')
        ->toContain('--backdrop: oklch(0 0 0 / 10%)');

    foreach ($selectors as $selector) {
        expect(presetDeclaration($visual, $selector))
            ->toContain('bg-backdrop')
            ->not->toContain('bg-black/10');
    }
})->with('design presets');

it('keeps alternate-media control states in an overridable shared layer', function () {
    $structural = file_get_contents(dirname(__DIR__, 2).'/resources/css/structural.css');
    $slider = file_get_contents(dirname(__DIR__, 2).'/resources/css/presets/nova/slider.css');

    expect($structural)
        ->toContain('@layer hotwire-accessibility')
        ->toContain('@media (forced-colors: active)')
        ->toContain('@media print')
        ->toContain('[data-checkable="true"]')
        ->toContain('[data-slot="switch"]')
        ->toContain('[data-slot="slider"]')
        ->toContain('[data-slot="multi-select-indicator"]')
        ->toContain('[data-slot="progress-indicator"]')
        ->toContain('appearance: auto')
        ->toContain('forced-color-adjust: none')
        ->and($slider)->not->toContain('@media (forced-colors: active)');
});

it('leaves the runtime safelist to the structural stylesheet', function (string $preset) {
    // A preset restating it snapshots the list, and goes stale the next time a controller applies one.
    expect(file_get_contents(app(CssPresetFiles::class)->path($preset)))
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
    $css = presetVisualCss($preset);

    expect($css)
        ->toContain('[data-slot="multi-select-content"])[data-state="closed"]')
        ->toContain('[data-state="open"]')
        ->not->toMatch('/\[data-state="closed"\][^{]*\{[^}]*\b(?:display:\s*none|@apply[^;}]*\bhidden\b)/s');
})->with('design presets');

it('uses pre-connect and resolved color scheme hooks', function (string $preset) {
    $css = presetVisualCss($preset);

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
    $css = presetVisualCss($preset);

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
    $css = presetVisualCss($preset);

    expect($css)
        ->toContain('[data-slot="sidebar"][data-collapsible="icon"] [data-slot="sidebar-menu-button"] { @apply md:')
        ->toContain('[data-slot="sidebar-menu-button"] > span:not([data-slot="avatar"])')
        ->not->toMatch('/\[data-slot="sidebar-menu-button"\] > span:not\([^}]+@apply[^;}]*(?:sr-only|opacity-0)/s');
})->with('design presets');

it('drives native slider tracks from the controller value', function (string $preset) {
    $css = presetVisualCss($preset);

    expect($css)
        ->toContain('[data-orientation="horizontal"]::-webkit-slider-runnable-track')
        ->toContain('[data-orientation="horizontal"]:where(:dir(rtl), [dir="rtl"], [dir="rtl"] *)::-webkit-slider-runnable-track')
        ->not->toContain('[data-orientation="horizontal"]:dir(rtl)::-webkit-slider-runnable-track')
        ->toContain('[data-orientation="vertical"]::-webkit-slider-runnable-track')
        ->toContain('var(--slider-value)')
        ->not->toContain('::-webkit-slider-thumb { margin-top:')
        ->not->toContain('[data-slot="slider"]:hover::-webkit-slider-thumb')
        ->not->toContain('[data-slot="slider"]:hover::-moz-range-thumb');
})->with('design presets');

it('gives every custom property in the toast transform a fallback', function () {
    // An unresolved var() invalidates the whole calc, the transform collapses to identity, and the
    // moment the value lands the browser transitions away from that identity — the entry animation
    // silently degrades to a fade. The manager sets these properties after the element is attached,
    // so there is always a frame where they are missing.
    $css = File::get(__DIR__.'/../../resources/css/structural.css');

    preg_match_all('/transform:\s*(.+?);/s', $css, $matches);
    $toastTransforms = array_filter(
        $matches[1],
        fn (string $declaration): bool => str_contains($declaration, '--toast-'),
    );

    expect($toastTransforms)->not->toBeEmpty();

    foreach ($toastTransforms as $declaration) {
        preg_match_all('/var\(\s*(--[a-z-]+)\s*([,)])/', $declaration, $vars, PREG_SET_ORDER);

        foreach ($vars as [, $name, $terminator]) {
            expect($terminator)->toBe(
                ',',
                "var($name) in the toast transform has no fallback",
            );
        }
    }
});

it('keeps the toaster viewport eligible for top-layer stacking from the structural stylesheet', function () {
    // The viewport is mechanics, not appearance: a preset restyles the toast surface, never where
    // the stack lives or whether it clears the UA popover box.
    $css = file_get_contents(__DIR__.'/../../resources/css/structural.css');

    expect($css)
        ->toContain('[data-hotwire-top-layer][popover][data-slot="toaster"]')
        ->toContain('pointer-events: none');
});

it('keeps clear input visibility owned by its controller', function (string $preset) {
    $declaration = presetDeclaration(presetVisualCss($preset), '[data-slot="clear-input-button"]');

    expect($declaration)->not->toMatch('/\bhidden\b/');
})->with('design presets');

it('styles generated rich text DOM through granular hooks', function (string $preset) {
    $css = presetVisualCss($preset);

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
    $css = presetVisualCss($preset);

    foreach (['--available-height', '--anchor-width', '--transform-origin'] as $token) {
        expect($css)->toContain($token);
    }

    expect($css)
        ->not->toContain('[data-slot="dropdown-menu"] { @apply absolute')
        ->not->toContain('slide-in-from');
})->with('design presets');

it('drives floating presence from semantic state and motion hooks', function (string $preset) {
    $css = presetVisualCss($preset);

    expect($css)
        ->toContain('[data-state="closed"]')
        ->toContain('[data-state="open"]')
        ->toContain('[data-motion="none"]')
        ->toContain('[data-presence="instant"]')
        ->toContain('@media (prefers-reduced-motion: reduce)');
})->with('design presets');

it('drives overlay motion from semantic presence state', function (string $preset) {
    $css = presetVisualCss($preset);

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
    $css = presetVisualCss($preset);

    expect($css)
        ->toContain('[data-slot="multi-select-content"]')
        ->toContain('[data-slot="multi-select-option"][data-selected="true"]')
        ->toContain('[data-slot="multi-select-select-all"][data-selected="true"]')
        ->toContain('[data-slot="multi-select-select-all"][data-indeterminate="true"]')
        ->toContain('[data-slot="multi-select-indicator"]');
})->with('design presets');

it('preserves component custom-property contracts', function (string $preset) {
    $css = presetVisualCss($preset);
    $structural = File::get(__DIR__.'/../../resources/css/structural.css');

    expect($css)
        ->toContain('[data-slot="progress-indicator"]')
        ->toContain('width: var(--progress-value)')
        ->and($structural)
        ->toContain('[data-slot="aspect-ratio"]')
        ->toContain('aspect-ratio: var(--ratio)')
        ->toContain('[data-slot="sticky"][data-side="top"]')
        ->toContain('var(--sticky-offset)');
})->with('design presets');

it('matches the pinned Nova density and surface contract', function () {
    $css = presetVisualCss('nova');
    $accordionTrigger = presetDeclaration($css, '[data-slot="accordion-trigger"]');
    $accordionContent = presetDeclaration($css, '[data-slot="accordion-content"]');
    $input = presetDeclaration($css, '[data-slot="input"], [data-slot="select"]');
    $fieldCard = presetDeclaration($css, '[data-slot="field-label"]:has(> [data-slot="field"])');
    $fieldCardContent = presetDeclaration($css, '[data-slot="field-label"]:has(> [data-slot="field"]) > [data-slot="field"]');
    $toggle = presetDeclaration($css, ':is([data-slot="toggle"], [data-slot="toggle-group-item"])[data-size="default"]');
    $tabsList = presetDeclaration($css, '[data-slot="tabs"][data-orientation="horizontal"] [data-slot="tabs-list"]');
    $tabsTrigger = presetDeclaration($css, '[data-slot="tabs-trigger"]');
    $hoverCard = presetDeclaration($css, '[data-slot="hover-card-content"]');
    $popover = presetDeclaration($css, '[data-slot="popover-content"]');
    $sidebarInner = presetDeclaration($css, '[data-slot="sidebar"][data-variant="floating"] [data-slot="sidebar-inner"]');
    $sidebarContent = presetDeclaration($css, '[data-slot="sidebar-content"]');
    $sidebarMenu = presetDeclaration($css, '[data-slot="sidebar-menu"]');
    $sliderThumb = presetDeclaration($css, '[data-slot="slider"]::-webkit-slider-thumb');

    expect($accordionTrigger)->toContain('rounded-lg', 'py-2.5')
        ->and($accordionContent)->toContain('pb-2.5')
        ->and($input)->toContain('rounded-lg', 'bg-transparent', 'px-2.5', 'disabled:bg-input/50')
        ->not->toContain('shadow-xs')
        ->and($fieldCard)->toContain('rounded-lg', 'not-has-[:disabled,[data-disabled]]:hover:bg-muted/50', 'has-[:focus-visible]:border-ring', 'has-[:focus-visible]:ring-3')
        ->and($fieldCardContent)->toContain('p-2.5')
        ->and($toggle)->toContain('h-8', 'min-w-8', 'px-2.5')
        ->and($tabsList)->toContain('h-8')
        ->and($tabsTrigger)->toContain('px-1.5', 'py-0.5')
        ->and($hoverCard)->toContain('rounded-lg', 'p-2.5', 'ring-1', 'duration-100')
        ->and($popover)->toContain('gap-2.5', 'p-2.5', 'ring-1', 'duration-100')
        ->and($sidebarInner)->toContain('ring-1')->not->toContain('border ')
        ->and($sidebarContent)->toContain('gap-0')
        ->and($sidebarMenu)->toContain('gap-0')
        ->and($sliderThumb)->toContain('border-ring');
});

// --- Known bug guards ---

it('does not reintroduce known clipping, stacking, sizing or marker bugs', function (string $preset) {
    $css = presetVisualCss($preset);
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
    $css = presetVisualCss($preset);
    $inlineStart = presetDeclaration($css, '[data-slot="input-group-addon"][data-align="inline-start"]');
    $inlineEnd = presetDeclaration($css, '[data-slot="input-group-addon"][data-align="inline-end"]');

    expect($inlineStart)->not->toContain('absolute')
        ->and($inlineEnd)->not->toContain('absolute')
        ->and($css)->toContain('focus-visible:ring-0')
        ->not->toContain('[data-slot="input-group-control"] { @apply pl-8')
        ->not->toContain('[data-slot="input-group-control"] { @apply pr-8');
})->with('design presets');

it('uses physical inline CSS only for documented physical contracts', function (string $preset) {
    $css = presetVisualCss($preset);
    $structural = File::get(__DIR__.'/../../resources/css/structural.css');
    $allowed = [
        '[data-slot="carousel"][data-carousel-axis="y"] > :is([data-slot="carousel-prev-button"], [data-slot="carousel-next-button"])' => ['left-1/2', '-translate-x-1/2'],
        '[data-slot="sheet-overlay"][data-state="closed"] > [data-slot="sheet-content"][data-side="right"]' => ['translate-x-10'],
        '[data-slot="sheet-overlay"][data-state="closed"] > [data-slot="sheet-content"][data-side="left"]' => ['-translate-x-10'],
        '[data-slot="sheet-overlay"][data-state="open"] > [data-slot="sheet-content"]' => ['translate-x-0'],
        '[data-slot="sheet-content"][data-side="right"]' => ['right-0', 'border-l'],
        '[data-slot="sheet-content"][data-side="left"]' => ['left-0', 'border-r'],
        '[data-slot="drawer-overlay"][data-state="closed"] > [data-slot="drawer-popup"][data-direction="right"]' => ['translate-x-full'],
        '[data-slot="drawer-overlay"][data-state="closed"] > [data-slot="drawer-popup"][data-direction="left"]' => ['-translate-x-full'],
        '[data-slot="drawer-overlay"][data-state="open"] > [data-slot="drawer-popup"]' => ['translate-x-0'],
        '[data-slot="drawer-popup"][data-direction="right"]' => ['right-0', 'rounded-l-xl', 'border-l'],
        '[data-slot="drawer-popup"][data-direction="left"]' => ['left-0', 'rounded-r-xl', 'border-r'],
        '[data-slot="sidebar-container"]' => ['left-0', 'right-0'],
        '[data-slot="sidebar"][data-collapsible="offcanvas"] [data-slot="sidebar-container"][data-side="left"]' => ['left-[calc(var(--sidebar-width)*-1)]'],
        '[data-slot="sidebar"][data-collapsible="offcanvas"] [data-slot="sidebar-container"][data-side="right"]' => ['right-[calc(var(--sidebar-width)*-1)]'],
        '[data-slot="sidebar"][data-variant="sidebar"][data-side="left"] [data-slot="sidebar-container"]' => ['border-r'],
        '[data-slot="sidebar"][data-variant="sidebar"][data-side="right"] [data-slot="sidebar-container"]' => ['border-l'],
        '[data-slot="sidebar"][data-variant="inset"][data-side="left"] ~ [data-slot="sidebar-inset"]' => ['ml-0'],
        '[data-slot="sidebar"][data-variant="inset"][data-side="right"] ~ [data-slot="sidebar-inset"]' => ['mr-0'],
        '[data-slot="sidebar-rail"]' => ['left-1/2', '-translate-x-1/2'],
        '[data-slot="sidebar"][data-side="left"] [data-slot="sidebar-rail"]' => ['-right-4'],
        '[data-slot="sidebar"][data-side="right"] [data-slot="sidebar-rail"]' => ['left-0'],
        '[data-slot="sidebar"][data-collapsible="offcanvas"] [data-slot="sidebar-rail"]' => ['left-full', 'translate-x-0'],
        '[data-slot="sidebar-menu-sub"]' => ['translate-x-px', '-translate-x-px'],
        '[data-slot="sidebar-menu-sub-button"]' => ['-translate-x-px', 'translate-x-px'],
        '[data-slot="sidebar"][data-mobile-state] > [data-slot="sidebar-container"][data-side="left"]' => ['left-0'],
        '[data-slot="sidebar"][data-mobile-state] > [data-slot="sidebar-container"][data-side="right"]' => ['right-0'],
        '[data-slot="sidebar"][data-mobile-state="closed"] > [data-slot="sidebar-container"][data-side="left"]' => ['-translate-x-full'],
        '[data-slot="sidebar"][data-mobile-state="closed"] > [data-slot="sidebar-container"][data-side="right"]' => ['translate-x-full'],
        '[data-slot="sidebar"][data-mobile-state="open"] > [data-slot="sidebar-container"]' => ['translate-x-0'],
        ':is([data-slot="dropdown-menu"], [data-slot="tooltip"], [data-slot="hover-card-content"], [data-slot="popover-content"], [data-slot="multi-select-content"])[data-state="closed"][data-side="left"]' => ['translate-x-2'],
        ':is([data-slot="dropdown-menu"], [data-slot="tooltip"], [data-slot="hover-card-content"], [data-slot="popover-content"], [data-slot="multi-select-content"])[data-state="closed"][data-side="right"]' => ['-translate-x-2'],
        ':is([data-slot="dropdown-menu"], [data-slot="tooltip"], [data-slot="hover-card-content"], [data-slot="popover-content"], [data-slot="multi-select-content"])[data-state="open"]' => ['translate-x-0'],
    ];
    $physical = physicalInlineUtilities($css);
    $unexpected = collect($physical)
        ->reject(fn (array $occurrence): bool => in_array(
            $occurrence['utility'],
            $allowed[$occurrence['selector']] ?? [],
            true,
        ))
        ->map(fn (array $occurrence): string => "{$occurrence['selector']} uses {$occurrence['utility']}")
        ->values()
        ->all();
    $missingAllowed = collect($allowed)
        ->flatMap(fn (array $utilities, string $selector): array => array_map(
            fn (string $utility): array => ['selector' => $selector, 'utility' => $utility],
            $utilities,
        ))
        ->reject(fn (array $occurrence): bool => in_array($occurrence, $physical, true))
        ->values()
        ->all();
    $probe = physicalInlineUtilities(<<<'CSS'
        [data-slot="probe"] {
            @apply md:pr-2
                rtl:-translate-x-1
                rounded-tr-lg
                bg-left-top
                object-right
                md:[padding-right:1rem];
        }
        CSS);
    $raw = physicalInlineDeclarations($css);
    $rawProbe = physicalInlineDeclarations(<<<'CSS'
        [data-slot="probe"] {
            left: 0;
            padding-right: 1rem;
            transform: translateX(1rem);
            color: red;
        }
        CSS);

    expect($unexpected)->toBe([])
        ->and($missingAllowed)->toBe([])
        ->and($probe)->toBe([
            ['selector' => '[data-slot="probe"]', 'utility' => 'pr-2'],
            ['selector' => '[data-slot="probe"]', 'utility' => '-translate-x-1'],
            ['selector' => '[data-slot="probe"]', 'utility' => 'rounded-tr-lg'],
            ['selector' => '[data-slot="probe"]', 'utility' => 'bg-left-top'],
            ['selector' => '[data-slot="probe"]', 'utility' => 'object-right'],
            ['selector' => '[data-slot="probe"]', 'utility' => '[padding-right:1rem]'],
        ])
        ->and($raw)->toHaveCount(5)
        ->toContain(['selector' => '[data-slot="switch"]::before', 'declaration' => 'transform: translateX(0)'])
        ->toContain(['selector' => '[data-slot="switch"]:checked::before', 'declaration' => 'transform: translateX(calc(100% - 2px))'])
        ->toContain(['selector' => '[data-slot="switch"]:where(:dir(rtl), [dir="rtl"], [dir="rtl"] *):checked::before', 'declaration' => 'transform: translateX(calc(-100% + 2px))'])
        ->toContain(['selector' => '[data-slot="slider"][data-orientation="horizontal"]::-webkit-slider-runnable-track', 'declaration' => 'background: linear-gradient(to right, var(--primary) 0 var(--slider-value), var(--muted) var(--slider-value) 100%)'])
        ->toContain(['selector' => '[data-slot="slider"][data-orientation="horizontal"]:where(:dir(rtl), [dir="rtl"], [dir="rtl"] *)::-webkit-slider-runnable-track', 'declaration' => 'background: linear-gradient(to left, var(--primary) 0 var(--slider-value), var(--muted) var(--slider-value) 100%)'])
        ->and($rawProbe)->toBe([
            ['selector' => '[data-slot="probe"]', 'declaration' => 'left: 0'],
            ['selector' => '[data-slot="probe"]', 'declaration' => 'padding-right: 1rem'],
            ['selector' => '[data-slot="probe"]', 'declaration' => 'transform: translateX(1rem)'],
        ])
        ->and($structural)
        ->toContain('margin-inline-start: calc(var(--carousel-slide-spacing, 0px) * -1)')
        ->toContain('padding-inline-start: var(--carousel-slide-spacing, 0px)')
        ->not->toContain('margin-left: calc(var(--carousel-slide-spacing, 0px) * -1)')
        ->not->toContain('padding-left: var(--carousel-slide-spacing, 0px)');
})->with('design presets');

function presetDeclaration(string $css, string $selector): string
{
    preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/s', $css, $matches);

    return $matches[1] ?? '';
}

/**
 * @return array<int, array{selector: string, utility: string}>
 */
function physicalInlineUtilities(string $css): array
{
    $occurrences = [];
    $css = preg_replace('/\/\*.*?\*\//s', '', $css) ?? $css;

    preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);
    foreach ($rules as $rule) {
        preg_match_all('/@apply\s+([^;}]+)/s', $rule[2], $declarations);

        foreach ($declarations[1] as $declaration) {
            foreach (preg_split('/\s+/', trim($declaration)) ?: [] as $token) {
                $utility = tailwindBaseUtility($token);
                $candidate = ltrim($utility, '-');

                if (! preg_match('/^(?:(?:left|right|ml|mr|pl|pr|scroll-ml|scroll-mr|scroll-pl|scroll-pr|translate-x)-|border-(?:l|r)(?:-|$)|rounded-(?:l|r|tl|tr|bl|br)(?:-|$)|origin-(?:left|right)$|(?:text|float|clear)-(?:left|right)$|(?:bg|object)-(?!\[)[a-z0-9-]*(?:left|right)[a-z0-9-]*$|\[(?:left|right|margin-(?:left|right)|padding-(?:left|right)|border-(?:left|right)(?:-(?:width|style|color))?|border-(?:top|bottom)-(?:left|right)-radius|transform-origin|background-position|object-position):)/', $candidate)) {
                    continue;
                }

                $occurrences[] = ['selector' => trim($rule[1]), 'utility' => $utility];
            }
        }
    }

    return $occurrences;
}

/**
 * @return array<int, array{selector: string, declaration: string}>
 */
function physicalInlineDeclarations(string $css): array
{
    $occurrences = [];
    $css = preg_replace('/\/\*.*?\*\//s', '', $css) ?? $css;

    preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);
    foreach ($rules as $rule) {
        $declarations = preg_replace('/@apply\s+[^;}]+;?/s', '', $rule[2]) ?? $rule[2];
        preg_match_all('/(?:^|;)\s*([a-z-]+)\s*:\s*([^;]+)/', $declarations, $properties, PREG_SET_ORDER);

        foreach ($properties as $property) {
            $name = trim($property[1]);
            $value = trim($property[2]);
            $physicalProperty = preg_match('/^(?:left|right|margin-(?:left|right)|padding-(?:left|right)|border-(?:left|right)(?:-(?:width|style|color))?|border-(?:top|bottom)-(?:left|right)-radius)$/', $name);
            $physicalValue = match ($name) {
                'transform' => preg_match('/\btranslate(?:X)?\(/i', $value) === 1,
                'translate' => true,
                'background', 'background-image' => preg_match('/\blinear-gradient\(\s*to\s+(?:left|right)\b/i', $value) === 1,
                'transform-origin', 'background-position', 'object-position', 'text-align', 'float', 'clear' => preg_match('/\b(?:left|right)\b/', $value) === 1,
                default => false,
            };

            if (! $physicalProperty && ! $physicalValue) {
                continue;
            }

            $occurrences[] = [
                'selector' => trim($rule[1]),
                'declaration' => "$name: $value",
            ];
        }
    }

    return $occurrences;
}

function tailwindBaseUtility(string $token): string
{
    $squareDepth = 0;
    $roundDepth = 0;
    $separator = -1;

    for ($index = 0, $length = strlen($token); $index < $length; $index++) {
        match ($token[$index]) {
            '[' => $squareDepth++,
            ']' => $squareDepth--,
            '(' => $roundDepth++,
            ')' => $roundDepth--,
            ':' => $squareDepth === 0 && $roundDepth === 0 ? $separator = $index : null,
            default => null,
        };
    }

    return substr($token, $separator + 1);
}
