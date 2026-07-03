<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

final readonly class LaravelIdeaMetadata
{
    public function __construct(
        private HotwireRegistry $registry,
        private string $prefix = 'hw',
        /** @var array<string, string> */
        private array $controllerLocations = [],
        private bool $includeComponents = true,
        private bool $includeCompletions = false,
    ) {}

    /** @return array<string, mixed> */
    public static function make(
        ?HotwireRegistry $registry = null,
        string $prefix = 'hw',
        array $controllerLocations = [],
        bool $includeComponents = true,
        bool $includeCompletions = false,
    ): array {
        return (new self(
            $registry ?? HotwireRegistry::make(),
            $prefix,
            $controllerLocations,
            $includeComponents,
            $includeCompletions,
        ))->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $metadata = [
            '$schema' => 'https://laravel-ide.com/schema/laravel-ide-v2.json',
        ];

        if ($this->includeComponents) {
            $metadata['blade'] = [
                'components' => [
                    'list' => $this->componentList(),
                    'phpNamespaces' => [
                        [
                            'phpNamespace' => '\\Emaia\\LaravelHotwire\\Components',
                            'prefix' => $this->prefix.':',
                            'ignoreBladeComponentPrefix' => true,
                        ],
                    ],
                ],
            ];
        }

        if ($this->includeCompletions) {
            $metadata['completions'] = $this->stimulusControllerCompletions();
        }

        return $metadata;
    }

    /** @return list<array{name: string, namespace: string, className: class-string}> */
    private function componentList(): array
    {
        $components = [];

        foreach ($this->registry->components() as $component) {
            $components[$component->key] = $component->class;
        }

        foreach (ComponentAliases::subComponents() as $name => $class) {
            $components[$name] = $class;
        }

        ksort($components);

        return array_map(
            fn (string $name, string $class): array => [
                'name' => $name,
                'namespace' => $this->prefix,
                'className' => '\\'.$class,
            ],
            array_keys($components),
            array_values($components),
        );
    }

    /** @return list<array<string, mixed>> */
    private function stimulusControllerCompletions(): array
    {
        $controllers = $this->controllerLocations();

        return [
            $this->completion(['functionNames' => ['stimulus_controller'], 'parameters' => [1]], $controllers),
            $this->completion(['functionNames' => ['stimulus_action'], 'parameters' => [1]], $controllers),
            $this->completion(['functionNames' => ['stimulus_target'], 'parameters' => [1]], $controllers),
            $this->completion(['classFqn' => ['\\Emaia\\LaravelHotwire\\Support\\Stimulus'], 'methodNames' => ['controller'], 'parameters' => [1]], $controllers),
            $this->completion(['classFqn' => ['\\Emaia\\LaravelHotwire\\Support\\Stimulus'], 'methodNames' => ['controllers'], 'parameters' => range(1, 20)], $controllers),
            $this->completion(['classFqn' => ['\\Emaia\\LaravelHotwire\\Support\\Stimulus'], 'methodNames' => ['action'], 'parameters' => [1]], $controllers),
            $this->completion(['classFqn' => ['\\Emaia\\LaravelHotwire\\Support\\Stimulus'], 'methodNames' => ['target'], 'parameters' => [1]], $controllers),
        ];
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, string>  $controllers
     * @return array<string, mixed>
     */
    private function completion(array $condition, array $controllers): array
    {
        return [
            'complete' => 'staticStrings',
            'condition' => [$condition],
            'options' => [
                'stringsWithLocation' => $controllers,
            ],
        ];
    }

    /** @return array<string, string> */
    private function controllerLocations(): array
    {
        $package = array_map(
            fn (ControllerDefinition $controller): string => 'vendor/emaia/laravel-hotwire/'.$controller->source,
            $this->registry->controllers(),
        );

        return array_replace($package, $this->controllerLocations);
    }
}
