<?php

namespace Emaia\LaravelHotwire\Support;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

final readonly class CssPresetFiles
{
    public function __construct(
        private Filesystem $files,
        private PresetSourceResolver $sources,
        private CssModuleManifest $manifest,
        private CssCustomProperties $customProperties = new CssCustomProperties,
    ) {}

    /** @return array<string, string> */
    public function all(): array
    {
        $presets = [];

        foreach ($this->files->glob($this->sources->cssRoot().'/presets/*.css') ?: [] as $path) {
            if (! $this->files->isFile($path)) {
                continue;
            }

            $presets[$this->files->name($path)] = realpath($path) ?: $path;
        }

        ksort($presets);

        return $presets;
    }

    /** @return string[] */
    public function names(): array
    {
        return array_keys($this->all());
    }

    public function path(string $name): ?string
    {
        return $this->all()[$name] ?? null;
    }

    /** Resolve a shipped preset by name. */
    public function source(string $name): ?PresetSource
    {
        $path = $this->path($name);

        if ($path === null) {
            return null;
        }

        $source = $this->sources->resolve($path, baseSources: $this->manifest->baseFor($name));
        $this->validateSource($name, $source);

        return $source;
    }

    /**
     * Resolve a shipped preset for selected catalog owners.
     *
     * @param  string[]  $components
     * @param  string[]  $controllers
     */
    public function sourceForSelection(string $name, array $components = [], array $controllers = []): ?PresetSource
    {
        $path = $this->path($name);

        if ($path === null) {
            return null;
        }

        $modules = $this->manifest->modulesFor($components, $controllers);
        $source = $this->sources->resolve($path, baseSources: $this->manifest->baseFor($name));
        $this->validateSource($name, $source);

        return $source->select($this->manifest->sourcesFor($name, $modules));
    }

    private function validateSource(string $name, PresetSource $source): void
    {
        $this->validateFoundations($name, $source);
        $this->validateSourceImports($name, $source);
        $this->validateTokenContract($name, $source);
    }

    private function validateSourceImports(string $name, PresetSource $source): void
    {
        $expected = $this->manifest->allSourcesFor($name);
        $actual = $source->visualStylesheetPaths();

        if ($actual === $expected) {
            return;
        }

        $missing = array_values(array_diff($expected, $actual));
        $unexpected = array_values(array_diff($actual, $expected));

        if ($missing !== []) {
            throw new PresetSourceException(
                "Preset [{$name}] entrypoint does not import declared sources: ".implode(', ', $missing).'.'
            );
        }

        if ($unexpected !== []) {
            throw new PresetSourceException(
                "Preset [{$name}] entrypoint imports undeclared sources: ".implode(', ', $unexpected).'.'
            );
        }

        $base = $this->manifest->baseFor($name);

        throw new PresetSourceException(array_slice($actual, 0, count($base)) !== $base
            ? "Preset [{$name}] entrypoint must import preset base before modules in manifest order."
            : "Preset [{$name}] entrypoint must import module sources in manifest order.");
    }

    private function validateFoundations(string $name, PresetSource $source): void
    {
        if ($source->foundationImports() !== ['foundation.css']) {
            throw new PresetSourceException(
                "Preset [{$name}] must import shared foundation [foundation.css] exactly once before preset sources."
            );
        }
    }

    private function validateTokenContract(string $name, PresetSource $source): void
    {
        $tokenSource = FoundationFacade::TOKEN_SOURCE;

        try {
            $foundationCss = $this->files->get($this->sources->cssRoot().'/'.$tokenSource);
        } catch (FileNotFoundException $exception) {
            throw new PresetSourceException(
                "Shared foundation token source [{$tokenSource}] cannot be read.",
                previous: $exception,
            );
        }

        $foundation = $this->customProperties->inspectStylesheet($foundationCss);

        if (! $foundation['valid']) {
            throw new PresetSourceException(
                "Shared foundation token source [{$tokenSource}] has invalid CSS syntax."
            );
        }

        $this->validateProperties(
            $foundation['properties'],
            $this->manifest->foundationProperties(),
            fn (string $property): string => "Shared foundation property [{$property}] is not declared in {$tokenSource}.",
            fn (string $property): string => "Shared foundation {$tokenSource} declares unregistered property [{$property}].",
        );
        $this->validateAliases('Shared foundation', $foundation['aliases'], $this->manifest->foundationAliases());

        $base = $this->customProperties->inspectPresetBase($source->baseCss());

        if ($base['violations'] !== []) {
            throw new PresetSourceException(
                "Preset [{$name}] base contains unsupported CSS scope [{$base['violations'][0]}]."
            );
        }

        $expectedProperties = $this->manifest->additionalPropertiesFor($name);
        $knownProperties = [...$this->manifest->foundationProperties(), ...$expectedProperties];
        $this->validateProperties(
            $base['properties'],
            $expectedProperties,
            fn (string $property): string => "Preset [{$name}] property [{$property}] is not declared by its base sources.",
            fn (string $property): string => "Preset [{$name}] base declares unregistered property [{$property}].",
            $knownProperties,
        );

        $this->validateAliases(
            "Preset [{$name}]",
            $base['aliases'],
            $this->manifest->additionalAliasesFor($name),
        );
    }

    /**
     * @param  string[]  $actual
     * @param  string[]  $required
     * @param  Closure(string): string  $missingMessage
     * @param  Closure(string): string  $unregisteredMessage
     * @param  string[]|null  $permitted
     */
    private function validateProperties(
        array $actual,
        array $required,
        Closure $missingMessage,
        Closure $unregisteredMessage,
        ?array $permitted = null,
    ): void {
        foreach ($required as $property) {
            if (! in_array($property, $actual, true)) {
                throw new PresetSourceException($missingMessage($property));
            }
        }

        $permitted ??= $required;

        foreach ($actual as $property) {
            if (! in_array($property, $permitted, true)) {
                throw new PresetSourceException($unregisteredMessage($property));
            }
        }
    }

    /**
     * @param  array<string, string|null>  $actual
     * @param  array<string, string>  $expected
     */
    private function validateAliases(string $owner, array $actual, array $expected): void
    {
        foreach ($expected as $alias => $target) {
            if (! array_key_exists($alias, $actual)) {
                throw new PresetSourceException("{$owner} alias [{$alias}] is not declared in @theme inline.");
            }

            if ($actual[$alias] !== $target) {
                $found = $actual[$alias] ?? 'ambiguous or missing target';

                throw new PresetSourceException(
                    "{$owner} alias [{$alias}] must reference [{$target}], found [{$found}]."
                );
            }
        }

        foreach (array_keys($actual) as $alias) {
            if (! array_key_exists($alias, $expected)) {
                throw new PresetSourceException(
                    "{$owner} @theme inline declares unregistered alias [{$alias}]."
                );
            }
        }
    }
}
