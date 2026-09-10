<?php

use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\CssRules;
use Illuminate\Support\Facades\File;

dataset('slot catalog presets', fn () => collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
    ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => [pathinfo($path, PATHINFO_FILENAME)]])
    ->all());

it('declares slots on every component catalog entry', function () {
    foreach (HotwireRegistry::make()->components() as $key => $component) {
        expect($component->styling->slots)
            ->toBeArray("Component [{$key}] must declare its slots through its family contract or catalog entry.");
    }
});

it('only leaves infrastructure components without styling slots', function () {
    $slotless = collect(HotwireRegistry::make()->components())
        ->filter(fn ($component): bool => $component->styling->slots === [])
        ->keys()
        ->values()
        ->all();

    expect($slotless)->toEqualCanonicalizing([
        'color-scheme.script',
        'controller-preloads',
        'frame',
        'frame-or-page',
        'frame-or-page.frame',
        'frame-or-page.page',
        'meta',
        'meta.cache',
        'meta.color-scheme',
        'meta.csrf',
        'meta.prefetch',
        'meta.refresh',
        'meta.root',
        'meta.view-transition',
        'meta.visit-control',
    ]);
});

it('documents component styling hooks from the catalog', function () {
    $slotsByDoc = [];

    foreach (HotwireRegistry::make()->components() as $component) {
        $doc = $component->docs;

        if (! File::exists(__DIR__.'/../../'.$doc)) {
            continue;
        }

        $slots = array_keys($component->styling->slots);

        if ($slots === []) {
            continue;
        }

        $slotsByDoc[$doc] = array_values(array_unique([
            ...($slotsByDoc[$doc] ?? []),
            ...$slots,
        ]));
    }

    foreach ($slotsByDoc as $doc => $slots) {
        $contents = File::get(__DIR__.'/../../'.$doc);

        expect($contents)->toMatch('/^## Styling hooks\r?$/m');

        foreach ($slots as $slot) {
            expect($contents)->toContain("data-slot=\"{$slot}\"");
        }
    }
});

it('hydrates valid slot and preset attribute metadata', function () {
    $definitions = [
        ...array_values(HotwireRegistry::make()->components()),
        ...array_values(HotwireRegistry::make()->controllers()),
    ];

    foreach ($definitions as $definition) {
        expect($definition->styling->slots)->each->toBeIn(['visual', 'structural'])
            ->and(array_keys($definition->styling->slots))->each->toMatch('/^[a-z][a-z0-9-]*$/');
    }
});

it('keeps package registry styling claims unambiguous', function () {
    expect(registryStylingClaimConflicts(HotwireRegistry::make()))->toBe([]);
});

it('detects ambiguous styling claims in mutated registry fixtures', function () {
    $registries = [
        HotwireRegistry::fromCatalog(registryAuditCatalog([
            'alpha' => registryAuditComponent(Button::class, [
                ['class' => Button::class, 'only' => ['root']],
            ]),
            'beta' => registryAuditComponent(Button::class, [
                ['class' => Button::class, 'only' => ['root']],
            ]),
        ]), __DIR__),
        HotwireRegistry::fromCatalog(registryAuditCatalog([
            'alpha' => registryAuditComponent(Button::class, ['shared-slot' => 'visual']),
            'beta' => registryAuditComponent(Card::class, ['shared-slot' => 'structural']),
        ]), __DIR__),
        HotwireRegistry::fromCatalog(registryAuditCatalog(
            ['alpha' => registryAuditComponent(Button::class, ['shared-slot' => 'visual'])],
            ['beta' => registryAuditController(['shared-slot' => 'visual'])],
        ), __DIR__),
    ];

    expect(registryStylingClaimConflicts($registries[0]))->toBe([
        'Component class ['.Button::class.'] owns multiple registry entries [alpha] and [beta].',
    ])->and(registryStylingClaimConflicts($registries[1]))->toBe([
        'Slot [shared-slot] is classified as both [visual] and [structural].',
    ])->and(registryStylingClaimConflicts($registries[2]))->toBe([
        'Visual slot [shared-slot] is claimed by both [component:alpha] and [controller:beta].',
    ]);
});

it('classifies presentation-free and controller-owned slots as structural', function () {
    $structural = collect(HotwireRegistry::make()->components())
        ->flatMap(fn ($definition): array => $definition->styling->structuralSlots())
        ->unique()
        ->values()
        ->all();

    expect($structural)->toEqualCanonicalizing([
        'alert-dialog',
        'alert-dialog-trigger',
        'aspect-ratio',
        'carousel-viewport',
        'carousel-container',
        'carousel-nav-wrapper',
        'chart',
        'conditional-field',
        'drawer',
        'field-label-required',
        'file-upload-announcer',
        'form',
        'map',
        'modal',
        'optimistic',
        'pagination-status',
        'read-more-viewport',
        'reveal-item',
        'rich-text-input',
        'sheet',
        'side-panel-panel',
        'toast-trigger',
        'toaster',
    ]);
});

it('keeps Carousel geometry in the structural stylesheet', function () {
    $css = File::get(__DIR__.'/../../resources/css/structural.css');

    expect($css)
        ->toContain('[data-carousel-viewport]')
        ->toContain('[data-carousel-container]')
        ->toContain('[data-carousel-container] > *')
        ->toContain('[data-carousel-axis="x"] [data-carousel-container]')
        ->toContain('[data-carousel-axis="y"] [data-carousel-container]')
        ->toContain('flex: 0 0 var(--carousel-slide-size, 100%)')
        ->toContain('var(--carousel-slide-spacing, 0px)');
});

it('keeps Read More first-paint geometry in the structural stylesheet', function () {
    $css = File::get(__DIR__.'/../../resources/css/structural.css');

    expect($css)
        ->toContain('[data-slot="read-more-content"]')
        ->toContain('display: flow-root')
        ->toContain('[data-slot="read-more-trigger"][hidden]')
        ->toContain('[data-slot="read-more-fade"][hidden]')
        ->toContain('@media (scripting: enabled)')
        ->toContain('[data-slot="read-more"]:not([data-ready])[data-state="collapsed"]')
        ->toContain('[data-slot="read-more"][data-ready][data-state="collapsed"]')
        ->toContain('[data-state="expanded"]:not([data-transitioning])')
        ->toContain('overflow: hidden')
        ->toContain('overflow: visible')
        ->toContain('var(--read-more-collapsed-height, 20rem)');
});

it('keeps Side Panel collapse mechanics in the structural stylesheet', function () {
    $css = File::get(__DIR__.'/../../resources/css/structural.css');

    expect($css)
        ->toContain('[data-slot="side-panel"]')
        ->toContain('[data-slot="side-panel-panel"]')
        ->toContain('inline-size: var(--side-panel-width, 16rem)')
        ->toContain('[data-state="collapsed"] > [data-slot="side-panel-panel"]')
        ->toContain('inline-size: var(--side-panel-collapsed-width, 1.75rem)')
        ->toContain('[data-slot="side-panel-panel-content"]')
        ->toContain('overflow: auto')
        ->toContain('inline-size: var(--side-panel-trigger-size, 1.75rem)')
        ->toContain('--side-panel-rail-position: var(--side-panel-width, 16rem)')
        ->toContain('--side-panel-rail-position: var(--side-panel-collapsed-width, 1.75rem)')
        ->toContain('--side-panel-trigger-left: var(--side-panel-rail-position)')
        ->toContain('--side-panel-trigger-right: auto')
        ->toContain('overflow: hidden');
});

it('keeps Sidebar content overflow mechanics in the structural stylesheet', function (string $preset) {
    $structural = File::get(__DIR__.'/../../resources/css/structural.css');
    $visual = app(CssPresetFiles::class)->source($preset)->visualCss();

    expect($structural)
        ->toContain('[data-slot="sidebar-content"]')
        ->toContain('overflow: auto')
        ->toContain('[data-collapsible="icon"] [data-slot="sidebar-content"]')
        ->toContain('overflow-x: hidden')
        ->toContain('overflow-y: auto')
        ->and($visual)
        ->not->toContain('md:overflow-hidden');
})->with('slot catalog presets');

it('stops Sidebar icon mode rules at nested providers', function (string $preset) {
    $stylesheets = [
        File::get(__DIR__.'/../../resources/css/structural.css'),
        app(CssPresetFiles::class)->source($preset)->visualCss(),
    ];
    $matched = 0;

    foreach ($stylesheets as $css) {
        foreach ((new CssRules)->parse((new CssRules)->stripComments($css)) as ['chain' => $chain]) {
            $selector = (string) end($chain);

            if (! str_contains($selector, '[data-slot="sidebar"]')
                || ! str_contains($selector, '[data-collapsible="icon"]')) {
                continue;
            }

            $matched++;

            expect($chain)->toContain(
                '@scope ([data-slot="sidebar"][data-collapsible="icon"]) to ([data-slot="sidebar-wrapper"])'
            );
        }
    }

    expect($matched)->toBeGreaterThan(0);
})->with('slot catalog presets');

it('keeps rules that name no slot out of the presets', function (string $preset) {
    // A preset groups by component; a rule keyed on a technical hook alone belongs to none of them.
    $css = app(CssPresetFiles::class)->source($preset)->visualCss();
    $slotless = [];

    foreach ((new CssRules)->parse((new CssRules)->stripComments($css)) as ['chain' => $chain]) {
        $selector = (string) end($chain);

        if (! str_contains($selector, 'data-slot') && ! str_ends_with($selector, '%')) {
            $slotless[] = $selector;
        }
    }

    expect($slotless)->toBe([], "Preset [{$preset}] styles something no component owns. Structural rules belong in resources/css/structural.css.")
        ->and(File::get(__DIR__.'/../../resources/css/structural.css'))
        ->toContain(':where([data-hotwire-top-layer][popover])')
        ->and(File::get(app(CssPresetFiles::class)->path($preset)))
        ->toContain('@import "../structural.css";');
})->with('slot catalog presets');

it('declares every literal slot emitted by any component view', function () {
    // Every view, not only the ones a catalog entry points at. Most package views belong to
    // subcomponents registered in Support\ComponentAliases, which have no catalog entry of their
    // own — their slots are declared under the parent, and nothing else would check them.
    $declared = declaredSlots();

    foreach (File::glob(__DIR__.'/../../resources/views/component-views/*.blade.php') as $path) {
        $slots = literalSlots(File::get($path));

        expect(array_diff($slots, $declared))
            ->toBe([], 'View ['.basename($path).'] emits slots no catalog entry declares.');
    }
});

it('declares slots rendered by components with trivial constructors', function () {
    $declared = declaredSlots();

    foreach (HotwireRegistry::make()->components() as $component) {
        $constructor = (new ReflectionClass($component->class))->getConstructor();

        if (componentRequiresRenderProps($component->key, $constructor)) {
            continue;
        }

        $html = renderAxisComponent($component->key);
        $slots = renderedSlots($html);

        expect(array_diff($slots, $declared))
            ->toBe([], "Component [{$component->key}] rendered undeclared slots.");
    }
});

it('reads axis slots from elements with Stimulus action descriptors', function () {
    $html = '<button data-slot="toggle" data-action="click->toggle#toggle" data-variant="default"></button>';

    expect(renderedAxisSlots($html, 'variant', 'default'))->toBe(['toggle']);
});

it('declares every slot referenced or created by package JavaScript', function () {
    $declared = declaredSlots();
    $referenced = collect(File::allFiles(__DIR__.'/../../resources/js'))
        ->filter(fn (SplFileInfo $file): bool => in_array($file->getExtension(), ['js', 'ts'], true))
        ->flatMap(fn (SplFileInfo $file): array => javascriptSlots($file->getContents()))
        ->unique()
        ->values()
        ->all();

    expect(array_diff($referenced, $declared))->toBe([]);
});

it('gives every visual catalog slot declaration-bearing participation in each preset', function (string $preset) {
    $registry = HotwireRegistry::make();
    $required = collect([
        ...array_values($registry->components()),
        ...array_values($registry->controllers()),
    ])
        ->flatMap(fn ($definition): array => $definition->styling->visualSlots())
        ->unique()
        ->values()
        ->all();
    $css = app(CssPresetFiles::class)->source($preset)->visualCss();
    $styled = [];
    $rules = new CssRules;
    $stripped = $rules->stripComments($css);

    foreach ($rules->parse($stripped) as ['chain' => $chain, 'declarations' => $declarations]) {
        if (trim($declarations) === '') {
            continue;
        }

        $selectorChain = implode(' ', array_filter($chain, fn (string $block): bool => ! str_starts_with($block, '@')));
        preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $selectorChain, $matches);
        $styled = [...$styled, ...$matches[1]];
    }

    expect(array_values(array_diff($required, array_unique($styled))))->toBe([]);
})->with('slot catalog presets');

it('declares every slot referenced by each preset', function (string $preset) {
    $css = app(CssPresetFiles::class)->source($preset)->visualCss();
    $stripped = (new CssRules)->stripComments($css);
    preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $stripped, $referenced);

    expect(array_values(array_diff(array_unique($referenced[1]), declaredSlots())))->toBe([]);
})->with('slot catalog presets');

/** @return string[] */
function registryStylingClaimConflicts(HotwireRegistry $registry): array
{
    $components = $registry->components();
    $componentKeysByClass = [];
    $conflicts = [];

    foreach ($components as $key => $component) {
        if (isset($componentKeysByClass[$component->class])) {
            $existing = $componentKeysByClass[$component->class];
            $conflicts[] = "Component class [{$component->class}] owns multiple registry entries [{$existing}] and [{$key}].";
        }

        $componentKeysByClass[$component->class] ??= $key;
    }

    $claims = [];

    foreach ($components as $key => $component) {
        foreach ($component->styling->slots as $slot => $kind) {
            $family = $component->styling->slotOwner($slot);
            $owner = $family === null ? $key : $componentKeysByClass[$family] ?? $key;
            registryStylingClaim($claims, $conflicts, $slot, $kind, "component:{$owner}");
        }
    }

    foreach ($registry->controllers() as $identifier => $controller) {
        foreach ($controller->styling->slots as $slot => $kind) {
            registryStylingClaim($claims, $conflicts, $slot, $kind, "controller:{$identifier}");
        }
    }

    $conflicts = array_values(array_unique($conflicts));
    sort($conflicts);

    return $conflicts;
}

/**
 * @param  array<string, array{kind: string, owner: string}>  $claims
 * @param  string[]  $conflicts
 */
function registryStylingClaim(array &$claims, array &$conflicts, string $slot, string $kind, string $owner): void
{
    if (isset($claims[$slot]) && $claims[$slot] !== ['kind' => $kind, 'owner' => $owner]) {
        $claimed = $claims[$slot];
        $conflicts[] = $claimed['kind'] !== $kind
            ? "Slot [{$slot}] is classified as both [{$claimed['kind']}] and [{$kind}]."
            : ucfirst($kind)." slot [{$slot}] is claimed by both [{$claimed['owner']}] and [{$owner}].";
    }

    $claims[$slot] ??= ['kind' => $kind, 'owner' => $owner];
}

/**
 * @param  array<string, array<string, mixed>>  $components
 * @param  array<string, array<string, mixed>>  $controllers
 * @return array{components: array<string, array<string, mixed>>, controllers: array<string, array<string, mixed>>}
 */
function registryAuditCatalog(array $components, array $controllers = []): array
{
    return compact('components', 'controllers');
}

/** @param array<mixed> $slots */
function registryAuditComponent(string $class, array $slots): array
{
    return [
        'class' => $class,
        'view' => 'fixture',
        'docs' => 'fixture.md',
        'category' => 'utility',
        'styling' => ['slots' => $slots],
    ];
}

/** @param array<string, 'visual'|'structural'> $slots */
function registryAuditController(array $slots): array
{
    return [
        'source' => 'resources/js/controllers/fixture_controller.js',
        'docs' => 'fixture.md',
        'category' => 'utility',
        'styling' => ['slots' => $slots],
    ];
}

/** @return string[] */
function declaredSlots(): array
{
    $registry = HotwireRegistry::make();

    return collect([...array_values($registry->components()), ...array_values($registry->controllers())])
        ->flatMap(fn ($definition): array => array_keys($definition->styling->slots))
        ->unique()
        ->values()
        ->all();
}

/** @return string[] */
function literalSlots(string $contents): array
{
    preg_match_all(
        '/["\']data-slot["\']\s*=>\s*["\']([a-z][a-z0-9-]*)["\']|data-slot\s*=\s*["\']([a-z][a-z0-9-]*)["\']/',
        $contents,
        $matches,
    );

    return array_values(array_unique(array_filter([...$matches[1], ...$matches[2]])));
}

/** @return string[] */
function javascriptSlots(string $contents): array
{
    preg_match_all(
        '/\.dataset\.slot\s*=\s*["\']([a-z][a-z0-9-]*)["\']|data-slot\s*=\s*["\']?([a-z][a-z0-9-]*)["\']?|setAttribute\(\s*["\']data-slot["\']\s*,\s*["\']([a-z][a-z0-9-]*)["\']/',
        $contents,
        $matches,
    );

    return array_values(array_unique(array_filter([...$matches[1], ...$matches[2], ...$matches[3]])));
}
