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
     *     tag?: string,
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

        if ($includeControllers) {
            foreach ($registry->controllers() as $controller) {
                $entries[] = $this->controllerEntry($controller);
            }
        }

        if ($includeComponents) {
            foreach ($registry->components() as $component) {
                $entries[] = $this->componentEntry($component, $prefix);
            }
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
        return $this->controllerEntry($controller);
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
     *     tag: string,
     *     controllers: string[]
     * }
     */
    public function forComponent(ComponentDefinition $component, string $prefix): array
    {
        return $this->componentEntry($component, $prefix);
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
    private function controllerEntry(ControllerDefinition $controller): array
    {
        $label = sprintf(
            '%-26s %-10s  %s',
            $controller->identifier,
            "[{$controller->category}]",
            $controller->description,
        );

        return [
            'type' => 'controller',
            'key' => $controller->identifier,
            'title' => $controller->identifier,
            'label' => $label,
            'search' => strtolower("{$controller->identifier} {$controller->category} {$controller->description} controller"),
            'docs' => $controller->docs,
            'category' => $controller->category,
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
     *     tag: string,
     *     controllers: string[]
     * }
     */
    private function componentEntry(ComponentDefinition $component, string $prefix): array
    {
        $tag = $component->tag($prefix);
        $label = sprintf(
            '%-26s %-10s  %s',
            $tag,
            "[{$component->category}]",
            $component->description,
        );

        return [
            'type' => 'component',
            'key' => $component->key,
            'title' => $component->displayName(),
            'label' => $label,
            'search' => strtolower("{$component->key} {$component->category} {$component->description} component"),
            'docs' => $component->docs,
            'category' => $component->category,
            'description' => $component->description,
            'tag' => $tag,
            'controllers' => $component->controllers,
        ];
    }
}
