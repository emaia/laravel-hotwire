<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\CssRules;
use Emaia\LaravelHotwire\Support\PresetAxes;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ViewErrorBag;

dataset('slot catalog presets', fn () => collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
    ->mapWithKeys(fn (string $path): array => [pathinfo($path, PATHINFO_FILENAME) => [pathinfo($path, PATHINFO_FILENAME)]])
    ->all());

it('declares slots on every component catalog entry', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    foreach ($catalog['components'] as $key => $component) {
        expect($component['styling']['slots'] ?? null)
            ->toBeArray("Component [{$key}] must declare its slots under the styling key.");
    }
});

it('only leaves infrastructure components without styling slots', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';

    $slotless = collect($catalog['components'])
        ->filter(fn (array $component): bool => ($component['styling']['slots'] ?? []) === [])
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
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $slotsByDoc = [];

    foreach ($catalog['components'] as $component) {
        $doc = $component['docs'] ?? null;

        if ($doc === null || ! File::exists(__DIR__.'/../../'.$doc)) {
            continue;
        }

        $slots = array_keys($component['styling']['slots'] ?? []);

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

it('classifies presentation-free and controller-owned slots as structural', function () {
    $structural = collect(HotwireRegistry::make()->components())
        ->flatMap(fn ($definition): array => $definition->styling->structuralSlots())
        ->unique()
        ->values()
        ->all();

    expect($structural)->toEqualCanonicalizing([
        'alert-dialog',
        'alert-dialog-trigger',
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
        ->toContain('transition: max-block-size 500ms ease-in-out')
        ->toContain('[data-state="expanded"]:not([data-transitioning])')
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
        ->toContain('transition: opacity 150ms ease-in-out')
        ->toContain('opacity: 0')
        ->toContain('inline-size: var(--side-panel-trigger-size, 1.75rem)')
        ->toContain('--side-panel-rail-position: var(--side-panel-width, 16rem)')
        ->toContain('--side-panel-rail-position: var(--side-panel-collapsed-width, 1.75rem)')
        ->toContain('--side-panel-trigger-left: var(--side-panel-rail-position)')
        ->toContain('--side-panel-trigger-right: auto')
        ->toContain('--side-panel-trigger-rotation: 180deg')
        ->toContain('transform: rotate(var(--side-panel-trigger-rotation, 0deg))')
        ->toContain('overflow: hidden');
});

it('keeps Sidebar content overflow mechanics in the structural stylesheet', function () {
    $structural = File::get(__DIR__.'/../../resources/css/structural.css');
    $preset = File::get(__DIR__.'/../../resources/css/presets/nova.css');

    expect($structural)
        ->toContain('[data-slot="sidebar-content"]')
        ->toContain('overflow: auto')
        ->toContain('[data-collapsible="icon"] [data-slot="sidebar-content"]')
        ->toContain('overflow-x: hidden')
        ->toContain('overflow-y: auto')
        ->and($preset)
        ->not->toContain('md:overflow-hidden');
});

it('keeps rules that name no slot out of the presets', function (string $preset) {
    // A preset groups by component; a rule keyed on a technical hook alone belongs to none of them.
    $css = File::get(__DIR__."/../../resources/css/presets/{$preset}.css");
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
        ->and($css)->toContain('@import "../structural.css";');
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
    $requiresSemanticProps = ['chart', 'file-upload', 'frame-or-page', 'frame-or-page.frame', 'frame-or-page.page', 'map'];
    $declared = declaredSlots();
    view()->share('errors', new ViewErrorBag);

    foreach (HotwireRegistry::make()->components() as $component) {
        $constructor = (new ReflectionClass($component->class))->getConstructor();

        if (in_array($component->key, $requiresSemanticProps, true)
            || ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0)) {
            continue;
        }

        $html = Blade::render("<x-hw::{$component->key} />");
        $slots = renderedSlots($html);

        expect(array_diff($slots, $declared))
            ->toBe([], "Component [{$component->key}] rendered undeclared slots.");
    }
});

/**
 * A component's default is the one value it is guaranteed to emit, so a preset that never styles it
 * is either missing a rule or reacting to a typo. Non-default values have no such source to check
 * against — no component validates `variant` against an allowlist.
 */
it('styles the default value of every variant and size prop', function (string $preset) {
    // Defaults that carry no appearance of their own — the slot's base rule already is that look.
    $baseIsEnough = ['icon', 'legend', 'auto', 'default'];
    $styled = (new PresetAxes)->extract(File::get(__DIR__."/../../resources/css/presets/{$preset}.css"));
    $components = HotwireRegistry::make()->components();
    $classes = collect($components)->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->all();
    $unstyled = [];

    foreach ($classes as $key => $class) {
        $definition = $components[$key] ?? $components[explode('.', $key)[0]] ?? null;
        $constructor = $definition === null ? null : (new ReflectionClass($class))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $axis = $parameter->getName();

            if (! in_array($axis, ['variant', 'size'], true) || ! $parameter->isDefaultValueAvailable()) {
                continue;
            }

            $default = $parameter->getDefaultValue();

            if (! is_string($default) || in_array($default, $baseIsEnough, true)) {
                continue;
            }

            $values = collect(array_keys($definition->styling->slots))
                ->flatMap(fn (string $slot): array => $styled[$slot]["data-$axis"] ?? [])
                ->all();

            if ($values !== [] && ! in_array($default, $values, true)) {
                $unstyled[] = "{$key}: \${$axis} defaults to '{$default}'";
            }
        }
    }

    sort($unstyled);

    expect($unstyled)->toBe([], "Preset [{$preset}] never styles a value a component defaults to.");
})->with('slot catalog presets');

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

it('styles every visual catalog slot in each preset', function (string $preset) {
    $registry = HotwireRegistry::make();
    $required = collect([
        ...array_values($registry->components()),
        ...array_values($registry->controllers()),
    ])
        ->flatMap(fn ($definition): array => $definition->styling->visualSlots())
        ->unique()
        ->values()
        ->all();
    $css = File::get(__DIR__."/../../resources/css/presets/{$preset}.css");
    preg_match_all('/\[data-slot=["\']([a-z0-9-]+)["\']\]/', $css, $matches);

    expect(array_values(array_diff($required, array_unique($matches[1]))))->toBe([]);
})->with('slot catalog presets');

/**
 * Holds vacuously while a single preset ships; it earns its keep the moment a second one can lose a
 * slot's `data-orientation` rules without anything else noticing.
 */
it('differentiates each slot by the same axes in every preset', function () {
    $axes = new PresetAxes;
    $byPreset = collect(glob(__DIR__.'/../../resources/css/presets/*.css') ?: [])
        ->mapWithKeys(fn (string $path): array => [
            pathinfo($path, PATHINFO_FILENAME) => $axes->extract(File::get($path)),
        ]);
    $reference = $byPreset->first();
    $referenceName = $byPreset->keys()->first();
    $divergent = [];

    foreach ($byPreset->skip(1) as $name => $slots) {
        foreach (array_unique([...array_keys($reference), ...array_keys($slots)]) as $slot) {
            $missing = array_diff(array_keys($reference[$slot] ?? []), array_keys($slots[$slot] ?? []));
            $extra = array_diff(array_keys($slots[$slot] ?? []), array_keys($reference[$slot] ?? []));

            foreach ([...$missing, ...$extra] as $axis) {
                $divergent[] = "{$slot}[data-{$axis}] differs between [{$referenceName}] and [{$name}]";
            }
        }
    }

    sort($divergent);

    expect($divergent)->toBe([]);
});

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
function renderedSlots(string $html): array
{
    preg_match_all('/data-slot=["\']([a-z][a-z0-9-]*)["\']/', $html, $matches);

    return array_values(array_unique($matches[1]));
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
