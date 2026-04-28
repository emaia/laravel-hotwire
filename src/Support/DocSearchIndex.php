<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\ComponentDefinition;
use Emaia\LaravelHotwire\Registry\ControllerDefinition;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

class DocSearchIndex
{
    /** @return array<int, array{label: string, search: string, docs: string}> */
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

    /** @return array{label: string, search: string, docs: string} */
    private function controllerEntry(ControllerDefinition $controller): array
    {
        $label = sprintf(
            '%-26s %-10s  %s',
            $controller->identifier,
            "[{$controller->category}]",
            $controller->description,
        );

        return [
            'label' => $label,
            'search' => strtolower("{$controller->identifier} {$controller->category} {$controller->description} controller"),
            'docs' => $controller->docs,
        ];
    }

    /** @return array{label: string, search: string, docs: string} */
    private function componentEntry(ComponentDefinition $component, string $prefix): array
    {
        $label = sprintf(
            '%-26s %-10s  %s',
            "<x-{$prefix}::{$component->key}>",
            "[{$component->category}]",
            $component->description,
        );

        return [
            'label' => $label,
            'search' => strtolower("{$component->key} {$component->category} {$component->description} component"),
            'docs' => $component->docs,
        ];
    }
}
