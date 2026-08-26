<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

/** @internal */
final readonly class CssModuleManifest
{
    /**
     * @param  array<string, array{components: string[], controllers: string[], dependencies: string[]}>  $modules
     * @param  array<string, array{sources: list<array{path: string, modules: string[]}>}>  $presets
     */
    private function __construct(
        private array $modules,
        private array $presets,
    ) {}

    /** Load and validate the package CSS module manifest. */
    public static function load(): self
    {
        $manifest = require dirname(__DIR__).'/Registry/styles.php';
        $instance = self::fromArray($manifest);
        $instance->validatePackageContract();

        return $instance;
    }

    /**
     * Build a manifest from an already evaluated definition.
     *
     * @param  array<string, mixed>  $manifest
     */
    public static function fromArray(array $manifest): self
    {
        $modules = $manifest['modules'] ?? null;
        $presets = $manifest['presets'] ?? null;

        if (! is_array($modules) || ! is_array($presets)) {
            throw new PresetSourceException('CSS module manifest must define modules and presets.');
        }

        foreach ($modules as $name => $module) {
            if (! is_string($name) || preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1 || ! is_array($module)) {
                throw new PresetSourceException('CSS module manifest contains an invalid module definition.');
            }

            foreach (['components', 'controllers', 'dependencies'] as $key) {
                if (! isset($module[$key]) || ! is_array($module[$key])) {
                    throw new PresetSourceException("CSS module [{$name}] must define {$key}.");
                }

                self::validateStringList($module[$key], "CSS module [{$name}] {$key}");
            }
        }

        foreach ($modules as $name => $module) {
            foreach ($module['dependencies'] as $dependency) {
                if (! isset($modules[$dependency])) {
                    throw new PresetSourceException("CSS module [{$name}] depends on undefined module [{$dependency}].");
                }
            }
        }

        self::validateDependencyCycles($modules);
        self::validatePresets($presets, $modules);

        /** @var array<string, array{components: string[], controllers: string[], dependencies: string[]}> $modules */
        /** @var array<string, array{sources: list<array{path: string, modules: string[]}>}> $presets */
        return new self($modules, $presets);
    }

    /**
     * Return dependency-closed modules for catalog owners.
     *
     * @param  string[]  $components
     * @param  string[]  $controllers
     * @return string[]
     */
    public function modulesFor(array $components, array $controllers): array
    {
        $selected = [];

        foreach ($this->modules as $name => $module) {
            if (array_intersect($components, $module['components']) !== []
                || array_intersect($controllers, $module['controllers']) !== []) {
                $selected[$name] = true;
            }
        }

        $pending = array_keys($selected);

        while (($name = array_shift($pending)) !== null) {
            foreach ($this->modules[$name]['dependencies'] as $dependency) {
                if (! isset($selected[$dependency])) {
                    $selected[$dependency] = true;
                    $pending[] = $dependency;
                }
            }
        }

        return array_keys($selected);
    }

    /**
     * Return selected private sources in canonical preset order.
     *
     * @param  string[]  $modules
     * @return string[]
     */
    public function sourcesFor(string $preset, array $modules): array
    {
        if (! isset($this->presets[$preset])) {
            throw new PresetSourceException("Unknown CSS module preset [{$preset}].");
        }

        foreach ($modules as $module) {
            if (! isset($this->modules[$module])) {
                throw new PresetSourceException("Unknown CSS module [{$module}].");
            }
        }

        return array_values(array_map(
            fn (array $source): string => $source['path'],
            array_filter(
                $this->presets[$preset]['sources'],
                fn (array $source): bool => array_intersect($modules, $source['modules']) !== [],
            ),
        ));
    }

    /** @param mixed[] $values */
    private static function validateStringList(array $values, string $label): void
    {
        if (array_values(array_unique($values, SORT_REGULAR)) !== array_values($values)) {
            throw new PresetSourceException("{$label} must contain unique values.");
        }

        foreach ($values as $value) {
            if (! is_string($value) || $value === '') {
                throw new PresetSourceException("{$label} must contain non-empty strings.");
            }
        }
    }

    /**
     * @param  array<string, array{components: string[], controllers: string[], dependencies: string[]}>  $modules
     */
    private static function validateDependencyCycles(array $modules): void
    {
        $visited = [];
        $stack = [];
        $active = [];

        $visit = function (string $name) use (&$visit, &$visited, &$stack, &$active, $modules): void {
            if (isset($active[$name])) {
                $cycle = [...array_slice($stack, $active[$name]), $name];

                throw new PresetSourceException('CSS module dependency cycle: '.implode(' -> ', $cycle).'.');
            }

            if (isset($visited[$name])) {
                return;
            }

            $active[$name] = count($stack);
            $stack[] = $name;

            foreach ($modules[$name]['dependencies'] as $dependency) {
                $visit($dependency);
            }

            array_pop($stack);
            unset($active[$name]);
            $visited[$name] = true;
        };

        foreach (array_keys($modules) as $name) {
            $visit($name);
        }
    }

    /**
     * @param  array<string, mixed>  $presets
     * @param  array<string, array{components: string[], controllers: string[], dependencies: string[]}>  $modules
     */
    private static function validatePresets(array $presets, array $modules): void
    {
        foreach ($presets as $preset => $definition) {
            if (preg_match('/^[a-z][a-z0-9-]*$/', $preset) !== 1
                || ! is_array($definition) || ! isset($definition['sources']) || ! is_array($definition['sources'])) {
                throw new PresetSourceException('CSS module manifest contains an invalid preset definition.');
            }

            $paths = [];

            foreach ($definition['sources'] as $source) {
                if (! is_array($source) || ! isset($source['path'], $source['modules'])
                    || ! is_string($source['path']) || ! is_array($source['modules'])
                    || preg_match("~^presets/{$preset}/[a-z0-9-]+\\.css$~", $source['path']) !== 1) {
                    throw new PresetSourceException("CSS module preset [{$preset}] contains an invalid source.");
                }

                if (isset($paths[$source['path']])) {
                    throw new PresetSourceException("CSS module preset [{$preset}] repeats source [{$source['path']}].");
                }

                $paths[$source['path']] = true;
                self::validateStringList($source['modules'], "CSS source [{$source['path']}] modules");

                foreach ($source['modules'] as $module) {
                    if (! isset($modules[$module])) {
                        throw new PresetSourceException("CSS source [{$source['path']}] references undefined module [{$module}].");
                    }
                }
            }
        }
    }

    private function validatePackageContract(): void
    {
        $registry = HotwireRegistry::make();
        $components = $registry->components();
        $controllers = $registry->controllers();
        $cssRoot = dirname(__DIR__, 2).'/resources/css/';

        foreach ($this->modules as $name => $module) {
            foreach ($module['components'] as $component) {
                if (! isset($components[$component])) {
                    throw new PresetSourceException("CSS module [{$name}] references unknown component [{$component}].");
                }
            }

            foreach ($module['controllers'] as $controller) {
                if (! isset($controllers[$controller])) {
                    throw new PresetSourceException("CSS module [{$name}] references unknown controller [{$controller}].");
                }
            }
        }

        foreach ($this->presets as $preset => $definition) {
            foreach ($definition['sources'] as $source) {
                if (! is_file($cssRoot.$source['path'])) {
                    throw new PresetSourceException("CSS module preset [{$preset}] source [{$source['path']}] does not exist.");
                }
            }
        }
    }
}
