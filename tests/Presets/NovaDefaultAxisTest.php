<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ComponentAliases;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\CssRules;
use Emaia\LaravelHotwire\Support\PresetAxes;

it('keeps Nova explicit variant and size defaults aligned with component constructors', function () {
    expect(novaExplicitDefaultGaps(app(CssPresetFiles::class)->source('nova')->visualCss()))->toBe([]);
});

it('keeps each Nova base-styled default exemption load-bearing', function () {
    $css = app(CssPresetFiles::class)->source('nova')->visualCss();
    $withExplicitDefaults = $css;
    $withoutBaseRules = $css;
    $explicitDiagnostics = [];
    $missingBaseDiagnostics = [];

    foreach (novaBaseStyledDefaults() as $exemption) {
        ['component' => $component, 'slot' => $slot, 'axis' => $axis, 'default' => $default] = $exemption;
        $withExplicitDefaults .= "\n[data-slot=\"{$slot}\"][data-{$axis}=\"{$default}\"] { color: red; }";
        ['css' => $withoutBaseRules, 'replacements' => $replacements] = renameNovaBaseSelector($withoutBaseRules, $slot);

        expect($replacements)->toBeGreaterThan(0, "Base selector for [{$slot}] was not renamed.");

        $explicitDiagnostics[] = "{$component}: \${$axis} default '{$default}' is styled explicitly on [data-slot=\"{$slot}\"]";
        $missingBaseDiagnostics[] = "{$component}: \${$axis} default '{$default}' has no declaration-bearing base rule for [data-slot=\"{$slot}\"]";
    }

    sort($explicitDiagnostics);
    sort($missingBaseDiagnostics);

    expect(novaObsoleteBaseDefaultExemptions($css))->toBe([])
        ->and(novaObsoleteBaseDefaultExemptions($withExplicitDefaults))->toBe($explicitDiagnostics)
        ->and(novaObsoleteBaseDefaultExemptions($withoutBaseRules))->toBe($missingBaseDiagnostics);
});

it('recognizes unqualified slots in grouped selectors as declaration-bearing base rules', function () {
    $css = <<<'CSS'
:is([data-slot="avatar"], [data-slot="card"]), [data-slot="marker"] { color: red; }
:is([data-slot="dropdown-item"][data-variant="default"], [data-slot="sidebar-menu-button"]:hover) { color: blue; }
:is([data-slot="attachment-media"], [data-slot="badge"]) > svg { color: green; }
[data-state="open"] { [data-slot="nested-slot"] { color: purple; } }
CSS;

    expect(novaHasBaseRule($css, 'avatar'))->toBeTrue()
        ->and(novaHasBaseRule($css, 'card'))->toBeTrue()
        ->and(novaHasBaseRule($css, 'marker'))->toBeTrue()
        ->and(novaHasBaseRule($css, 'dropdown-item'))->toBeFalse()
        ->and(novaHasBaseRule($css, 'sidebar-menu-button'))->toBeFalse()
        ->and(novaHasBaseRule($css, 'attachment-media'))->toBeFalse()
        ->and(novaHasBaseRule($css, 'nested-slot'))->toBeFalse();
});

it('renames an unqualified slot inside a multiline grouped base selector', function () {
    $css = <<<'CSS'
/* Shared surface { grouped for maintenance }. */
:is(
    [data-slot="avatar"],
    [data-slot="card"]
) { color: red; }
CSS;

    ['css' => $renamed, 'replacements' => $replacements] = renameNovaBaseSelector($css, 'avatar');
    ['replacements' => $nestedReplacements] = renameNovaBaseSelector(
        '[data-state="open"] { [data-slot="avatar"] { color: red; } }',
        'avatar',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaHasBaseRule($renamed, 'avatar'))->toBeFalse()
        ->and(novaHasBaseRule($renamed, 'card'))->toBeTrue()
        ->and($nestedReplacements)->toBe(0);
});

it('keeps Nova defaults that intentionally omit their data attribute load-bearing', function () {
    expect(renderedSlots(renderAxisComponent('drawer')))->toContain('drawer-popup')
        ->and(renderedSlots(renderAxisComponent('sheet')))->toContain('sheet-content')
        ->and(novaObsoleteNonAttributeDefaultExemptions())->toBe([]);

    $stale = novaNonAttributeDefaults();
    $stale[0]['axis'] = 'removed-size';

    expect(novaObsoleteNonAttributeDefaultExemptions($stale))->toBe([
        "drawer: non-attribute exemption for \$removed-size default '' references a missing constructor parameter",
    ]);
});

it('reports a non-attribute exemption when the default starts rendering an attribute', function () {
    $render = fn (string $component): string => $component === 'drawer'
        ? '<div data-slot="drawer-popup" data-size=""></div>'
        : renderAxisComponent($component);

    expect(novaObsoleteNonAttributeDefaultExemptions(null, $render))->toBe([
        "drawer: non-attribute exemption for \$size default '' is obsolete because data-size is now rendered",
    ]);
});

it('reports component render failures separately from missing Nova selectors', function () {
    $render = fn (string $component): string => $component === 'button'
        ? throw new RuntimeException('fixture render failure')
        : renderAxisComponent($component);

    expect(novaExplicitDefaultGaps(app(CssPresetFiles::class)->source('nova')->visualCss(), $render))->toBe([
        "button: could not render while auditing \$size default 'default': fixture render failure",
        "button: could not render while auditing \$variant default 'default': fixture render failure",
    ]);
});

it('detects a renamed explicit Nova default selector', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'navbar',
        'variant',
        'line',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["navbar: \$variant default 'line' is not styled on [data-slot=\"navbar\"]"]);
});

it('detects a renamed explicit Nova default selector named default', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'button',
        'variant',
        'default',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["button: \$variant default 'default' is not styled on [data-slot=\"button\"]"]);
});

it('detects a renamed explicit Nova default selector named icon', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'color-scheme-toggle',
        'size',
        'icon',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["color-scheme.toggle: \$size default 'icon' is not styled on [data-slot=\"color-scheme-toggle\"]"]);
});

it('checks an explicit Nova default against the subcomponent slot that emits it', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'modal-close',
        'variant',
        'outline',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["modal.close: \$variant default 'outline' is not styled on [data-slot=\"modal-close\"]"]);
});

it('checks Toggle Group defaults against its inherited item axes', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'toggle-group-item',
        'variant',
        'default',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["toggle-group: \$variant default 'default' is not styled on [data-slot=\"toggle-group-item\"]"]);
});

it('checks Modal size defaults against its positioner', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'modal-positioner',
        'size',
        'md',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["modal: \$size default 'md' is not styled on [data-slot=\"modal-positioner\"]"]);
});

it('detects removal of an entire explicit Nova axis', function () {
    ['css' => $css, 'replacements' => $replacements] = renameNovaAxisSelector(
        app(CssPresetFiles::class)->source('nova')->visualCss(),
        'switch',
        'size',
    );

    expect($replacements)->toBeGreaterThan(0)
        ->and(novaExplicitDefaultGaps($css))->toBe(["switch: \$size default 'default' is not styled on [data-slot=\"switch\"]"]);
});

/** @return list<array{component: string, slot: string, axis: string, default: string}> */
function novaBaseStyledDefaults(): array
{
    // Nova applies these defaults in each slot's base rule and only selects their alternatives explicitly.
    return [
        ['component' => 'attachment.media', 'slot' => 'attachment-media', 'axis' => 'variant', 'default' => 'icon'],
        ['component' => 'avatar', 'slot' => 'avatar', 'axis' => 'size', 'default' => 'default'],
        ['component' => 'card', 'slot' => 'card', 'axis' => 'size', 'default' => 'default'],
        ['component' => 'dropdown.item', 'slot' => 'dropdown-item', 'axis' => 'variant', 'default' => 'default'],
        ['component' => 'marker', 'slot' => 'marker', 'axis' => 'variant', 'default' => 'default'],
        ['component' => 'sidebar.menu-button', 'slot' => 'sidebar-menu-button', 'axis' => 'variant', 'default' => 'default'],
    ];
}

/** @return list<array{component: string, axis: string, default: string}> */
function novaNonAttributeDefaults(): array
{
    // Drawer and Sheet intentionally omit data-size when their unconstrained default is selected.
    return [
        ['component' => 'drawer', 'axis' => 'size', 'default' => ''],
        ['component' => 'sheet', 'axis' => 'size', 'default' => ''],
    ];
}

/** @return string[] */
function novaExplicitDefaultGaps(string $css, ?Closure $renderComponent = null): array
{
    $baseStyledDefaults = collect(novaBaseStyledDefaults())
        ->keyBy(fn (array $default): string => implode('|', [
            $default['component'],
            $default['slot'],
            $default['axis'],
            $default['default'],
        ]));
    $nonAttributeDefaults = collect(novaNonAttributeDefaults())
        ->keyBy(fn (array $default): string => "{$default['component']}|{$default['axis']}");
    $styled = (new PresetAxes)->extract($css);
    $components = HotwireRegistry::make()->components();
    $classes = collect($components)->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->all();
    $gaps = [];
    $renderComponent ??= fn (string $component): string => renderAxisComponent($component);

    foreach ($classes as $key => $class) {
        $definition = $components[$key] ?? $components[explode('.', $key)[0]] ?? null;
        $constructor = $definition === null ? null : (new ReflectionClass($class))->getConstructor();

        $parameters = collect($constructor?->getParameters() ?? [])
            ->filter(fn (ReflectionParameter $parameter): bool => in_array($parameter->getName(), ['variant', 'size'], true)
                && $parameter->isDefaultValueAvailable()
                && is_string($parameter->getDefaultValue()));

        if ($parameters->isEmpty()) {
            continue;
        }

        try {
            $html = $renderComponent($key);
        } catch (Throwable $exception) {
            foreach ($parameters as $parameter) {
                $axis = $parameter->getName();
                $default = $parameter->getDefaultValue();
                $gaps[] = "{$key}: could not render while auditing \${$axis} default '{$default}': {$exception->getMessage()}";
            }

            continue;
        }

        foreach ($parameters as $parameter) {
            $axis = $parameter->getName();
            $default = $parameter->getDefaultValue();

            if (($nonAttributeDefaults["{$key}|{$axis}"]['default'] ?? null) === $default
                && renderedAxisSlots($html, $axis, $default) === []) {
                continue;
            }

            $slots = novaAxisTargetSlots($key, $axis, $default, $html);

            if ($slots === []) {
                $gaps[] = "{$key}: \${$axis} default '{$default}' renders no matching data-{$axis} slot";
            }

            foreach ($slots as $slot) {
                if ($baseStyledDefaults->has("{$key}|{$slot}|{$axis}|{$default}")) {
                    continue;
                }

                $values = $styled[$slot]["data-{$axis}"] ?? [];

                if (! in_array($default, $values, true)) {
                    $gaps[] = "{$key}: \${$axis} default '{$default}' is not styled on [data-slot=\"{$slot}\"]";
                }
            }
        }
    }

    $gaps = array_values(array_unique($gaps));
    sort($gaps);

    return $gaps;
}

/** @return string[] */
function novaObsoleteBaseDefaultExemptions(string $css, ?Closure $renderComponent = null): array
{
    $styled = (new PresetAxes)->extract($css);
    $components = HotwireRegistry::make()->components();
    $classes = collect($components)->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->all();
    $baseRuleSlots = novaBaseRuleSlots($css);
    $obsolete = [];
    $renderComponent ??= fn (string $component): string => renderAxisComponent($component);

    foreach (novaBaseStyledDefaults() as $exemption) {
        ['component' => $key, 'slot' => $slot, 'axis' => $axis, 'default' => $default] = $exemption;
        $class = $classes[$key] ?? null;
        $subject = "{$key}: \${$axis} default '{$default}'";

        if ($class === null) {
            $obsolete[] = "{$subject} references an unregistered component";

            continue;
        }

        $constructor = (new ReflectionClass($class))->getConstructor();

        if (componentRequiresRenderProps($key, $constructor)) {
            $obsolete[] = "{$subject} cannot be audited because the component requires render props";

            continue;
        }

        $parameters = $constructor?->getParameters() ?? [];
        $parameter = collect($parameters)->first(fn (ReflectionParameter $parameter): bool => $parameter->getName() === $axis);

        if ($parameter === null) {
            $obsolete[] = "{$subject} references a missing constructor parameter";

            continue;
        }

        if (! $parameter->isDefaultValueAvailable()) {
            $obsolete[] = "{$subject} references a constructor parameter without a default";

            continue;
        }

        if ($parameter->getDefaultValue() !== $default) {
            $actual = var_export($parameter->getDefaultValue(), true);
            $obsolete[] = "{$subject} does not match the constructor default {$actual}";

            continue;
        }

        try {
            $html = $renderComponent($key);
        } catch (Throwable $exception) {
            $obsolete[] = "{$subject} could not render: {$exception->getMessage()}";

            continue;
        }

        if (! in_array($slot, novaAxisTargetSlots($key, $axis, $default, $html), true)) {
            $obsolete[] = "{$subject} does not render [data-slot=\"{$slot}\"] with data-{$axis}=\"{$default}\"";

            continue;
        }

        if (in_array($default, $styled[$slot]["data-{$axis}"] ?? [], true)) {
            $obsolete[] = "{$subject} is styled explicitly on [data-slot=\"{$slot}\"]";

            continue;
        }

        if (! in_array($slot, $baseRuleSlots, true)) {
            $obsolete[] = "{$subject} has no declaration-bearing base rule for [data-slot=\"{$slot}\"]";
        }
    }

    sort($obsolete);

    return $obsolete;
}

/**
 * @param  null|list<array{component: string, axis: string, default: string}>  $exemptions
 * @return string[]
 */
function novaObsoleteNonAttributeDefaultExemptions(?array $exemptions = null, ?Closure $renderComponent = null): array
{
    $exemptions ??= novaNonAttributeDefaults();
    $components = HotwireRegistry::make()->components();
    $classes = collect($components)->map(fn ($definition): string => $definition->class)
        ->merge(ComponentAliases::subComponents())
        ->all();
    $obsolete = [];
    $renderComponent ??= fn (string $component): string => renderAxisComponent($component);

    foreach ($exemptions as ['component' => $key, 'axis' => $axis, 'default' => $default]) {
        $subject = "{$key}: non-attribute exemption for \${$axis} default '{$default}'";
        $class = $classes[$key] ?? null;

        if ($class === null) {
            $obsolete[] = "{$subject} references an unregistered component";

            continue;
        }

        $constructor = (new ReflectionClass($class))->getConstructor();
        $parameter = collect($constructor?->getParameters() ?? [])
            ->first(fn (ReflectionParameter $parameter): bool => $parameter->getName() === $axis);

        if ($parameter === null) {
            $obsolete[] = "{$subject} references a missing constructor parameter";

            continue;
        }

        if (! $parameter->isDefaultValueAvailable()) {
            $obsolete[] = "{$subject} references a constructor parameter without a default";

            continue;
        }

        if ($parameter->getDefaultValue() !== $default) {
            $actual = var_export($parameter->getDefaultValue(), true);
            $obsolete[] = "{$subject} does not match the constructor default {$actual}";

            continue;
        }

        try {
            $html = $renderComponent($key);
        } catch (Throwable $exception) {
            $obsolete[] = "{$subject} could not render: {$exception->getMessage()}";

            continue;
        }

        if (renderedAxisSlots($html, $axis, $default) !== []) {
            $obsolete[] = "{$subject} is obsolete because data-{$axis} is now rendered";
        }
    }

    sort($obsolete);

    return $obsolete;
}

function novaHasBaseRule(string $css, string $slot): bool
{
    return in_array($slot, novaBaseRuleSlots($css), true);
}

/** @return string[] */
function novaBaseRuleSlots(string $css): array
{
    $rules = new CssRules;
    $slots = [];

    foreach ($rules->parse($rules->stripComments($css)) as ['chain' => $chain, 'declarations' => $declarations]) {
        $selector = end($chain) ?: '';
        $enclosingSelectors = array_filter(
            array_slice($chain, 0, -1),
            fn (string $block): bool => ! str_starts_with($block, '@'),
        );

        if (trim($declarations) === '' || $enclosingSelectors !== []) {
            continue;
        }

        foreach (novaTopLevelSelectorBranches($selector) as $branch) {
            if (preg_match('/^\[data-slot\s*=\s*["\']?([a-z][a-z0-9-]*)["\']?\]$/', $branch, $match) === 1) {
                $slots[] = $match[1];
            }
        }
    }

    return array_values(array_unique($slots));
}

/** @return string[] */
function novaTopLevelSelectorBranches(string $selector): array
{
    $branches = [];

    foreach (novaSplitSelectorList($selector) as $branch) {
        $branch = trim($branch);

        if (preg_match('/^:(?:is|where)\((.*)\)$/s', $branch, $group) === 1) {
            $branches = [...$branches, ...novaTopLevelSelectorBranches($group[1])];
        } else {
            $branches[] = $branch;
        }
    }

    return $branches;
}

/** @return string[] */
function novaSplitSelectorList(string $selector): array
{
    $branches = [''];
    $depth = 0;
    $quote = null;

    foreach (str_split($selector) as $index => $character) {
        if ($quote !== null) {
            $branches[array_key_last($branches)] .= $character;

            if ($character === $quote && ($index === 0 || $selector[$index - 1] !== '\\')) {
                $quote = null;
            }

            continue;
        }

        if ($character === '"' || $character === "'") {
            $quote = $character;
        } elseif (in_array($character, ['(', '['], true)) {
            $depth++;
        } elseif (in_array($character, [')', ']'], true)) {
            $depth--;
        } elseif ($character === ',' && $depth === 0) {
            $branches[] = '';

            continue;
        }

        $branches[array_key_last($branches)] .= $character;
    }

    return $branches;
}

/** @return array{css: string, replacements: int} */
function renameNovaBaseSelector(string $css, string $slot): array
{
    $css = (new CssRules)->stripComments($css);

    if (! novaHasBaseRule($css, $slot)) {
        return ['css' => $css, 'replacements' => 0];
    }

    $replacements = 0;
    $attribute = '/(\[data-slot\s*=\s*["\']?)'.preg_quote($slot, '/').'(["\']?\])/';

    $renamed = preg_replace_callback('/([^{}]+)\{/', function (array $match) use ($attribute, $slot, &$replacements): string {
        $selector = trim($match[1]);

        if (! collect(novaTopLevelSelectorBranches($selector))->contains(
            fn (string $branch): bool => preg_match('/^\[data-slot\s*=\s*["\']?'.preg_quote($slot, '/').'["\']?\]$/', $branch) === 1
        )) {
            return $match[0];
        }

        $selector = preg_replace_callback(
            $attribute,
            fn (array $attributeMatch): string => $attributeMatch[1]."{$slot}-renamed".$attributeMatch[2],
            $match[1],
            -1,
            $count,
        ) ?? $match[1];
        $replacements += $count;

        return $selector.'{';
    }, $css);

    return ['css' => $renamed ?? $css, 'replacements' => $replacements];
}

/** @return array{css: string, replacements: int} */
function renameNovaAxisSelector(string $css, string $slot, string $axis, ?string $value = null): array
{
    $rules = new CssRules;
    $replacements = 0;

    foreach ($rules->parse($rules->stripComments($css)) as ['chain' => $chain]) {
        $selector = end($chain) ?: '';

        if (! str_contains($selector, "[data-slot=\"{$slot}\"]")
            || ($value !== null && ! str_contains($selector, "[data-{$axis}=\"{$value}\"]"))
            || ! str_contains($selector, "[data-{$axis}=")) {
            continue;
        }

        $renamed = str_replace("[data-slot=\"{$slot}\"]", "[data-slot=\"{$slot}-renamed\"]", $selector);
        $css = str_replace($selector, $renamed, $css, $count);
        $replacements += $count;
    }

    return ['css' => $css, 'replacements' => $replacements];
}

/** @return string[] */
function novaAxisTargetSlots(string $key, string $axis, string $default, string $html): array
{
    $targets = [
        'modal' => ['size' => ['modal-positioner']],
        'toggle-group' => [
            'variant' => ['toggle-group-item'],
            'size' => ['toggle-group-item'],
        ],
    ];
    $rendered = renderedAxisSlots($html, $axis, $default);

    return isset($targets[$key][$axis])
        ? array_values(array_intersect($targets[$key][$axis], $rendered))
        : $rendered;
}
