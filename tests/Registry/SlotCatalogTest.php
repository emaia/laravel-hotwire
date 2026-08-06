<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
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

it('hydrates valid slot and preset attribute metadata', function () {
    $definitions = [
        ...array_values(HotwireRegistry::make()->components()),
        ...array_values(HotwireRegistry::make()->controllers()),
    ];

    foreach ($definitions as $definition) {
        expect($definition->styling->slots)->each->toBeIn(['visual', 'structural'])
            ->and(array_keys($definition->styling->slots))->each->toMatch('/^[a-z][a-z0-9-]*$/');

        foreach (['variants' => $definition->styling->variants, 'sizes' => $definition->styling->sizes] as $axis => $bySlot) {
            foreach ($bySlot as $slot => $values) {
                expect(array_key_exists($slot, $definition->styling->slots))
                    ->toBeTrue("[{$axis}] declares slot [{$slot}], which the entry does not emit.")
                    ->and($values)->each->toBeString()
                    ->and($values)->not->toBeEmpty();
            }
        }
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
        'rich-text-input',
        'sheet',
    ]);
});

it('keeps Carousel geometry in its controller stylesheet', function () {
    $css = File::get(__DIR__.'/../../resources/js/controllers/carousel.css');

    expect($css)
        ->toContain('[data-carousel-viewport]')
        ->toContain('[data-carousel-container]')
        ->toContain('[data-carousel-container] > *')
        ->toContain('[data-carousel-axis="x"] [data-carousel-container]')
        ->toContain('[data-carousel-axis="y"] [data-carousel-container]')
        ->toContain('flex: 0 0 var(--carousel-slide-size, 100%)')
        ->toContain('var(--carousel-slide-spacing, 0px)');
});

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
 * Partial guard against phantom declarations. A component's own default is the one value it is
 * guaranteed to emit, so the catalog must know it. Values that are neither a prop default nor
 * styled by a preset stay invisible — no component validates `variant` against an allowlist, so
 * there is no source to check them against.
 */
it('declares the default value of every variant and size prop', function () {
    $components = HotwireRegistry::make()->components();
    $classes = collect($components)->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->all();
    $undeclared = [];

    foreach ($classes as $key => $class) {
        $definition = $components[$key] ?? $components[explode('.', $key)[0]] ?? null;
        $constructor = $definition === null ? null : (new ReflectionClass($class))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $bySlot = match ($parameter->getName()) {
                'variant' => $definition->styling->variants,
                'size' => $definition->styling->sizes,
                default => [],
            };

            if ($bySlot === [] || ! $parameter->isDefaultValueAvailable() || ! is_string($default = $parameter->getDefaultValue())) {
                continue;
            }

            if (! in_array($default, array_merge(...array_values($bySlot)), true)) {
                $undeclared[] = "{$key}: \${$parameter->getName()} defaults to '{$default}'";
            }
        }
    }

    sort($undeclared);

    expect($undeclared)->toBe([], 'A component defaults to a value no slot of its catalog entry declares.');
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

it('declares every variant and size a preset styles', function (string $preset) {
    $declared = declaredAxes();
    $styled = presetAxes(File::get(__DIR__."/../../resources/css/presets/{$preset}.css"));
    $undeclared = [];

    foreach ($styled as $slot => $axes) {
        foreach ($axes as $axis => $values) {
            foreach (array_diff($values, $declared[$slot][$axis] ?? []) as $value) {
                $undeclared[] = "{$slot}[data-{$axis}=\"{$value}\"]";
            }
        }
    }

    sort($undeclared);

    expect($undeclared)->toBe([], "Preset [{$preset}] styles values the catalog never declares, so generated scaffolds omit them.");
})->with('slot catalog presets');

it('styles every declared variant and size, or declares why it needs no rule', function (string $preset) {
    // Values that carry no appearance of their own. `default` is the slot's base rule by
    // definition; the rest are semantic or already covered by the base rule.
    $ruleFree = [
        'attachment-media' => ['variant' => ['icon']],   // the base attachment-media rule is the icon treatment
        'field-legend' => ['variant' => ['legend', 'label']], // chooses the element, not the appearance
        'modal-positioner' => ['size' => ['auto']],      // intrinsic width, no max-width to apply
    ];
    $styled = presetAxes(File::get(__DIR__."/../../resources/css/presets/{$preset}.css"));
    $unstyled = [];

    foreach (declaredAxes() as $slot => $axes) {
        foreach ($axes as $axis => $values) {
            $covered = [...$styled[$slot][$axis] ?? [], 'default', ...$ruleFree[$slot][$axis] ?? []];

            foreach (array_diff($values, $covered) as $value) {
                $unstyled[] = "{$slot}[data-{$axis}=\"{$value}\"]";
            }
        }
    }

    sort($unstyled);

    expect($unstyled)->toBe([], "Preset [{$preset}] declares values it never styles. Either add the rule or record the value as rule-free.");
})->with('slot catalog presets');

/**
 * Every slot the catalog declares, from component and controller entries alike.
 *
 * @return string[]
 */
function declaredSlots(): array
{
    $registry = HotwireRegistry::make();

    return collect([...array_values($registry->components()), ...array_values($registry->controllers())])
        ->flatMap(fn ($definition): array => array_keys($definition->styling->slots))
        ->unique()
        ->values()
        ->all();
}

/** @return array<string, array<string, string[]>> */
function declaredAxes(): array
{
    $registry = HotwireRegistry::make();
    $declared = [];

    foreach ([...array_values($registry->components()), ...array_values($registry->controllers())] as $definition) {
        foreach (['variant' => $definition->styling->variants, 'size' => $definition->styling->sizes] as $axis => $bySlot) {
            foreach ($bySlot as $slot => $values) {
                $declared[$slot][$axis] = array_values(array_unique([...$declared[$slot][$axis] ?? [], ...$values]));
            }
        }
    }

    return $declared;
}

/**
 * Map each slot to the `data-variant` / `data-size` values a preset styles for it. Only values in
 * the same compound selector count — `[data-slot="sidebar"][data-variant="floating"]
 * [data-slot="sidebar-container"]` belongs to `sidebar`, not to the descendant.
 *
 * @return array<string, array<string, string[]>>
 */
function presetAxes(string $css): array
{
    $axes = [];
    $collect = function (array $slots, string $attributes) use (&$axes): void {
        foreach (['variant', 'size'] as $axis) {
            if (preg_match_all('/\[data-'.$axis.'="([a-z0-9-]+)"\]/', $attributes, $values) === 0) {
                continue;
            }

            foreach ($slots as $slot) {
                $axes[$slot][$axis] = array_values(array_unique([...$axes[$slot][$axis] ?? [], ...$values[1]]));
            }
        }
    };

    foreach (explode("\n", $css) as $line) {
        $selector = trim(explode('{', $line)[0]);

        if (! str_contains($selector, '[data-slot=')) {
            continue;
        }

        preg_match_all('/:is\(([^)]*)\)((?:\[[^\]]*\])*)/', $selector, $groups, PREG_SET_ORDER);

        foreach ($groups as $group) {
            preg_match_all('/\[data-slot="([a-z0-9-]+)"\]/', $group[1], $slots);
            $collect($slots[1], $group[2]);
        }

        $singles = (string) preg_replace('/:is\([^)]*\)(?:\[[^\]]*\])*/', ' ', $selector);
        preg_match_all('/\[data-slot="([a-z0-9-]+)"\]((?:\[[^\]]*\])*)/', $singles, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $collect([$match[1]], $match[2]);
        }
    }

    return $axes;
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
