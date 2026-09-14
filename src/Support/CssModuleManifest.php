<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

/** @internal */
final readonly class CssModuleManifest
{
    /**
     * @param  array<string, array{components: string[], controllers: string[], dependencies: string[]}>  $modules
     * @param  array{properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>}  $foundation
     * @param  array<string, array{base: string[], properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>, sources: list<array{path: string, modules: string[]}>}>  $presets
     */
    private function __construct(
        private array $modules,
        private array $foundation,
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
        $foundation = $manifest['foundation'] ?? null;
        $modules = $manifest['modules'] ?? null;
        $presets = $manifest['presets'] ?? null;

        if (! is_array($modules) || ! is_array($presets)) {
            throw new PresetSourceException('CSS module manifest must define modules and presets.');
        }

        $foundation = self::validateTokenMetadata($foundation, 'CSS foundation');
        self::validateTokenReferences($foundation, 'CSS foundation', $foundation['properties']);

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
        $presets = self::validatePresets($presets, $modules, $foundation);

        /** @var array<string, array{components: string[], controllers: string[], dependencies: string[]}> $modules */
        /** @var array<string, array{base: string[], properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>, sources: list<array{path: string, modules: string[]}>}> $presets */
        return new self($modules, $foundation, $presets);
    }

    /**
     * Return official preset names in manifest order.
     *
     * @return string[]
     */
    public function presetNames(): array
    {
        return array_keys($this->presets);
    }

    /**
     * Return custom properties owned by the shared foundation.
     *
     * @return string[]
     */
    public function foundationProperties(): array
    {
        return $this->foundation['properties'];
    }

    /**
     * Return Tailwind aliases owned by the shared foundation.
     *
     * @return array<string, string>
     */
    public function foundationAliases(): array
    {
        return $this->foundation['aliases'];
    }

    /**
     * Return semantic contrast pairs owned by the shared foundation.
     *
     * @return array<string, array{foreground: string, background: string}>
     */
    public function foundationContrastPairs(): array
    {
        return $this->foundation['contrast_pairs'];
    }

    /**
     * Return the complete custom-property contract inherited by a preset.
     *
     * @return string[]
     */
    public function propertiesFor(string $preset): array
    {
        return [...$this->foundation['properties'], ...$this->additionalPropertiesFor($preset)];
    }

    /**
     * Return custom properties introduced by the preset beyond the shared foundation.
     *
     * @return string[]
     */
    public function additionalPropertiesFor(string $preset): array
    {
        return $this->preset($preset)['properties'];
    }

    /**
     * Return the complete Tailwind alias contract inherited by a preset.
     *
     * @return array<string, string>
     */
    public function aliasesFor(string $preset): array
    {
        return [...$this->foundation['aliases'], ...$this->additionalAliasesFor($preset)];
    }

    /**
     * Return Tailwind aliases introduced by the preset beyond the shared foundation.
     *
     * @return array<string, string>
     */
    public function additionalAliasesFor(string $preset): array
    {
        return $this->preset($preset)['aliases'];
    }

    /**
     * Return the complete semantic contrast contract inherited by a preset.
     *
     * @return array<string, array{foreground: string, background: string}>
     */
    public function contrastPairsFor(string $preset): array
    {
        return [...$this->foundation['contrast_pairs'], ...$this->additionalContrastPairsFor($preset)];
    }

    /**
     * Return semantic contrast pairs introduced by the preset beyond the shared foundation.
     *
     * @return array<string, array{foreground: string, background: string}>
     */
    public function additionalContrastPairsFor(string $preset): array
    {
        return $this->preset($preset)['contrast_pairs'];
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
     * Return preset base followed by selected module sources in canonical order.
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

        $sources = array_values(array_map(
            fn (array $source): string => $source['path'],
            array_filter(
                $this->presets[$preset]['sources'],
                fn (array $source): bool => array_intersect($modules, $source['modules']) !== [],
            ),
        ));

        return [...$this->presets[$preset]['base'], ...$sources];
    }

    /**
     * Return ordered preset base sources.
     *
     * @return string[]
     */
    public function baseFor(string $preset): array
    {
        return $this->preset($preset)['base'];
    }

    /**
     * Return every private source in canonical preset order.
     *
     * @return string[]
     */
    public function allSourcesFor(string $preset): array
    {
        return $this->sourcesFor($preset, array_keys($this->modules));
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
     * @param  array{properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>}  $foundation
     * @return array<string, array{base: string[], properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>, sources: list<array{path: string, modules: string[]}>}>
     */
    private static function validatePresets(array $presets, array $modules, array $foundation): array
    {
        $validated = [];

        foreach ($presets as $preset => $definition) {
            if (preg_match('/^[a-z][a-z0-9-]*$/', $preset) !== 1
                || ! is_array($definition)
                || ! isset($definition['base'], $definition['sources'])
                || ! is_array($definition['base'])
                || ! is_array($definition['sources'])
                || ! array_is_list($definition['base'])
                || ! array_is_list($definition['sources'])) {
                throw new PresetSourceException('CSS module manifest contains an invalid preset definition.');
            }

            $label = "CSS module preset [{$preset}]";
            $metadata = self::validateTokenMetadata($definition, $label);
            $foundationNames = [...$foundation['properties'], ...array_keys($foundation['aliases'])];

            foreach ($metadata['properties'] as $property) {
                if (in_array($property, $foundationNames, true)) {
                    throw new PresetSourceException("{$label} property [{$property}] already belongs to the shared foundation.");
                }
            }

            foreach (array_keys($metadata['aliases']) as $alias) {
                if (in_array($alias, $foundationNames, true)) {
                    throw new PresetSourceException("{$label} alias [{$alias}] already belongs to the shared foundation.");
                }
            }

            foreach (array_keys($metadata['contrast_pairs']) as $pair) {
                if (isset($foundation['contrast_pairs'][$pair])) {
                    throw new PresetSourceException("{$label} contrast pair [{$pair}] already belongs to the shared foundation.");
                }
            }

            self::validateTokenReferences(
                $metadata,
                $label,
                [...$foundation['properties'], ...$metadata['properties']],
            );

            $paths = [];
            $mappedModules = [];
            $sources = [];

            self::validateStringList($definition['base'], "CSS module preset [{$preset}] base");

            foreach ($definition['base'] as $path) {
                if (! self::validSourcePath($path, $preset)) {
                    throw new PresetSourceException("CSS module preset [{$preset}] contains an invalid base source.");
                }

                $paths[$path] = true;
            }

            foreach ($definition['sources'] as $source) {
                if (! is_array($source) || ! isset($source['path'], $source['modules'])
                    || ! is_string($source['path']) || ! is_array($source['modules'])
                    || ! self::validSourcePath($source['path'], $preset)) {
                    throw new PresetSourceException("CSS module preset [{$preset}] contains an invalid source.");
                }

                if (isset($paths[$source['path']])) {
                    throw new PresetSourceException("CSS module preset [{$preset}] repeats source [{$source['path']}].");
                }

                $paths[$source['path']] = true;
                self::validateStringList($source['modules'], "CSS source [{$source['path']}] modules");

                if ($source['modules'] === []) {
                    throw new PresetSourceException("CSS source [{$source['path']}] must map at least one module.");
                }

                foreach ($source['modules'] as $module) {
                    if (! isset($modules[$module])) {
                        throw new PresetSourceException("CSS source [{$source['path']}] references undefined module [{$module}].");
                    }

                    $mappedModules[$module] = true;
                }

                $sources[] = [
                    'path' => $source['path'],
                    'modules' => array_values($source['modules']),
                ];
            }

            $missingModules = array_diff(array_keys($modules), array_keys($mappedModules));

            if ($missingModules !== []) {
                throw new PresetSourceException(
                    "CSS module preset [{$preset}] does not map modules: ".implode(', ', $missingModules).'.'
                );
            }

            $validated[$preset] = [
                'base' => $definition['base'],
                ...$metadata,
                'sources' => $sources,
            ];
        }

        return $validated;
    }

    /** @return array{properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>} */
    private static function validateTokenMetadata(mixed $metadata, string $label): array
    {
        if (! is_array($metadata)
            || ! isset($metadata['properties'], $metadata['aliases'], $metadata['contrast_pairs'])
            || ! is_array($metadata['properties'])
            || ! is_array($metadata['aliases'])
            || ! is_array($metadata['contrast_pairs'])
            || ! array_is_list($metadata['properties'])) {
            throw new PresetSourceException("{$label} token metadata must define properties, aliases, and contrast_pairs.");
        }

        self::validateStringList($metadata['properties'], "{$label} properties");
        $properties = [];

        foreach ($metadata['properties'] as $property) {
            if (! is_string($property) || ! self::validCustomProperty($property)) {
                throw new PresetSourceException("{$label} properties must contain CSS custom property names beginning with --.");
            }

            $properties[] = $property;
        }

        $aliases = [];

        foreach ($metadata['aliases'] as $alias => $target) {
            if (! is_string($alias) || ! self::validCustomProperty($alias)) {
                throw new PresetSourceException("{$label} alias [{$alias}] must be a CSS custom property name beginning with --.");
            }

            if (! is_string($target) || ! self::validCustomProperty($target)) {
                throw new PresetSourceException("{$label} alias [{$alias}] must reference a CSS custom property name beginning with --.");
            }

            if (in_array($alias, $properties, true)) {
                throw new PresetSourceException("{$label} custom property [{$alias}] cannot be both a property and an alias.");
            }

            $aliases[$alias] = $target;
        }

        $contrastPairs = [];

        foreach ($metadata['contrast_pairs'] as $name => $pair) {
            if (! is_string($name) || preg_match('/^[a-z][a-z0-9-]*$/', $name) !== 1
                || ! is_array($pair)
                || count($pair) !== 2
                || ! array_key_exists('foreground', $pair)
                || ! array_key_exists('background', $pair)
                || ! is_string($pair['foreground'] ?? null)
                || ! self::validCustomProperty($pair['foreground'])
                || ! is_string($pair['background'] ?? null)
                || ! self::validCustomProperty($pair['background'])) {
                $pairName = (string) $name;

                throw new PresetSourceException("{$label} contrast pair [{$pairName}] must define foreground and background.");
            }

            $contrastPairs[$name] = [
                'foreground' => $pair['foreground'],
                'background' => $pair['background'],
            ];
        }

        return [
            'properties' => $properties,
            'aliases' => $aliases,
            'contrast_pairs' => $contrastPairs,
        ];
    }

    /** @param string[] $knownProperties */
    private static function validateTokenReferences(array $metadata, string $label, array $knownProperties): void
    {
        foreach ($metadata['aliases'] as $alias => $target) {
            if (! in_array($target, $knownProperties, true)) {
                throw new PresetSourceException("{$label} alias [{$alias}] references unknown property [{$target}].");
            }
        }

        foreach ($metadata['contrast_pairs'] as $name => $pair) {
            foreach ($pair as $property) {
                if (! in_array($property, $knownProperties, true)) {
                    throw new PresetSourceException("{$label} contrast pair [{$name}] references unknown property [{$property}].");
                }
            }
        }
    }

    private static function validCustomProperty(mixed $name): bool
    {
        return CssCustomPropertyName::isValid($name);
    }

    /**
     * Return a validated preset definition.
     *
     * @return array{base: string[], properties: string[], aliases: array<string, string>, contrast_pairs: array<string, array{foreground: string, background: string}>, sources: list<array{path: string, modules: string[]}>}
     */
    private function preset(string $preset): array
    {
        if (! isset($this->presets[$preset])) {
            throw new PresetSourceException("Unknown CSS module preset [{$preset}].");
        }

        return $this->presets[$preset];
    }

    private static function validSourcePath(string $path, string $preset): bool
    {
        return preg_match("~^presets/{$preset}/(?:[a-z0-9-]+/)*[a-z0-9-]+\\.css$~", $path) === 1;
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
            $entrypoint = "presets/{$preset}.css";

            if (! is_file($cssRoot.$entrypoint)) {
                throw new PresetSourceException("CSS preset [{$preset}] entrypoint [{$entrypoint}] does not exist.");
            }

            foreach ($this->allSourcesFor($preset) as $source) {
                if (! is_file($cssRoot.$source)) {
                    throw new PresetSourceException("CSS module preset [{$preset}] source [{$source}] does not exist.");
                }
            }
        }
    }
}
