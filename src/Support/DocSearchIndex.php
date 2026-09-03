<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\ComponentDefinition;
use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

class DocSearchIndex
{
    /**
     * @return array<int, array{
     *     type: 'controller'|'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tags?: list<string>,
     *     npm?: array<string, string>,
     *     controllers?: string[]
     * }>
     */
    public function build(
        HotwireRegistry $registry,
        bool $includeControllers,
        bool $includeComponents,
        string $prefix,
    ): array {
        $entries = [];
        $controllers = $includeControllers ? $registry->controllers() : [];
        $components = $includeComponents ? $registry->components() : [];
        $labelWidth = 26;

        foreach ($controllers as $controller) {
            $labelWidth = max($labelWidth, strlen($controller->identifier));
        }

        foreach ($components as $component) {
            $labelWidth = max($labelWidth, strlen($component->tag($prefix)));
        }

        foreach ($controllers as $controller) {
            $entries[] = $this->controllerEntry($controller, $labelWidth);
        }

        foreach ($components as $component) {
            $entries[] = $this->componentEntry($component, $prefix, $labelWidth);
        }

        return $entries;
    }

    /**
     * @return array{
     *     type: 'controller',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     npm: array<string, string>
     * }
     */
    public function forController(ControllerDefinition $controller): array
    {
        return $this->controllerEntry($controller, 26);
    }

    /**
     * @return array{
     *     type: 'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tags: list<string>,
     *     controllers: string[]
     * }
     */
    public function forComponent(ComponentDefinition $component, string $prefix): array
    {
        return $this->componentEntry($component, $prefix, 26);
    }

    /**
     * @return array{
     *     type: 'controller',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     npm: array<string, string>
     * }
     */
    private function controllerEntry(ControllerDefinition $controller, int $labelWidth): array
    {
        $label = sprintf(
            "%-{$labelWidth}s %-10s  %s",
            $controller->identifier,
            "[{$controller->category->value}]",
            $controller->description,
        );

        return [
            'type' => 'controller',
            'key' => $controller->identifier,
            'title' => $controller->identifier,
            'label' => $label,
            'search' => strtolower("{$controller->identifier} {$controller->category->value} {$controller->description} controller"),
            'docs' => $controller->docs,
            'category' => $controller->category->value,
            'description' => $controller->description,
            'npm' => $controller->npm,
        ];
    }

    /**
     * @return array{
     *     type: 'component',
     *     key: string,
     *     title: string,
     *     label: string,
     *     search: string,
     *     docs: string,
     *     category: string,
     *     description: string,
     *     tags: list<string>,
     *     controllers: string[]
     * }
     */
    private function componentEntry(ComponentDefinition $component, string $prefix, int $labelWidth): array
    {
        $tags = $component->tags(ComponentAliases::prefixes($prefix));
        $label = sprintf(
            "%-{$labelWidth}s %-10s  %s",
            $tags[0],
            "[{$component->category->value}]",
            $component->description,
        );

        return [
            'type' => 'component',
            'key' => $component->key,
            'title' => $component->displayName(),
            'label' => $label,
            'search' => strtolower("{$component->key} {$component->category->value} {$component->description} component"),
            'docs' => $component->docs,
            'category' => $component->category->value,
            'description' => $component->description,
            'tags' => $tags,
            'controllers' => $component->controllers,
        ];
    }
}
