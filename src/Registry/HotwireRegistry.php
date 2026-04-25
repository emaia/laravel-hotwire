<?php

namespace Emaia\LaravelHotwire\Registry;

use RuntimeException;

final class HotwireRegistry
{
    private static ?self $instance = null;

    /** @var array<string, ComponentDefinition> */
    private array $components;

    /** @var array<string, ControllerDefinition> */
    private array $controllers;

    /**
     * @param  array<string, ComponentDefinition>  $components
     * @param  array<string, ControllerDefinition>  $controllers
     */
    private function __construct(
        private readonly string $basePath,
        array $components,
        array $controllers,
    ) {
        $this->components = $components;
        $this->controllers = $controllers;
    }

    public static function make(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $catalog = require __DIR__.'/catalog.php';
        $basePath = dirname(__DIR__, 2);

        return self::$instance = self::fromCatalog($catalog, $basePath);
    }

    public static function swap(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /** @param  array{components: array<string, array<string, mixed>>, controllers: array<string, array<string, mixed>>}  $catalog */
    public static function fromCatalog(array $catalog, string $basePath): self
    {
        $components = [];

        foreach ($catalog['components'] as $key => $component) {
            $components[$key] = new ComponentDefinition(
                key: $key,
                class: $component['class'],
                view: $component['view'],
                docs: $component['docs'],
                category: $component['category'],
                controllers: $component['controllers'] ?? [],
                aliases: $component['aliases'] ?? [],
                experimental: $component['experimental'] ?? false,
                deprecated: $component['deprecated'] ?? false,
            );
        }

        $controllers = [];

        foreach ($catalog['controllers'] as $identifier => $controller) {
            $controllers[$identifier] = new ControllerDefinition(
                identifier: $identifier,
                source: $controller['source'],
                docs: $controller['docs'],
                category: $controller['category'],
                npm: $controller['npm'] ?? [],
                aliases: $controller['aliases'] ?? [],
                internal: $controller['internal'] ?? false,
                experimental: $controller['experimental'] ?? false,
                deprecated: $controller['deprecated'] ?? false,
            );
        }

        ksort($components);
        uasort($controllers, function (ControllerDefinition $a, ControllerDefinition $b) {
            $cmp = strcmp($a->relativeDir(), $b->relativeDir());

            return $cmp !== 0 ? $cmp : strcmp($a->publishKey(), $b->publishKey());
        });

        return new self($basePath, $components, $controllers);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    /** @return array<string, ComponentDefinition> */
    public function components(): array
    {
        return $this->components;
    }

    /** @return array<string, ControllerDefinition> */
    public function controllers(): array
    {
        return $this->controllers;
    }

    public function component(string $key): ?ComponentDefinition
    {
        return $this->components[$key] ?? null;
    }

    public function controller(string $identifier): ?ControllerDefinition
    {
        return $this->controllers[$identifier] ?? null;
    }

    /** @return ControllerDefinition[] */
    public function controllersForComponent(string|ComponentDefinition $component): array
    {
        $definition = is_string($component) ? $this->component($component) : $component;

        if ($definition === null) {
            return [];
        }

        return array_map(
            fn (string $identifier) => $this->requireController($identifier),
            $definition->controllers,
        );
    }

    /** @return array<string, string> */
    public function bladeComponentAliases(string $prefix): array
    {
        $aliases = [];

        foreach ($this->components as $component) {
            $aliases["{$prefix}::{$component->key}"] = $component->class;
        }

        return $aliases;
    }

    /** @return array<string, ControllerDefinition> */
    public function publishableControllers(): array
    {
        $controllers = array_filter(
            $this->controllers,
            fn (ControllerDefinition $controller) => ! $controller->internal,
        );

        $byPublishKey = [];

        foreach ($controllers as $controller) {
            $byPublishKey[$controller->publishKey()] = $controller;
        }

        return $byPublishKey;
    }

    public function requireController(string $identifier): ControllerDefinition
    {
        $controller = $this->controller($identifier);

        if ($controller === null) {
            throw new RuntimeException("Controller [{$identifier}] is not defined in the Hotwire registry.");
        }

        return $controller;
    }
}
