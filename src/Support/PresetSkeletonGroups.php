<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use RuntimeException;

/** @internal */
final class PresetSkeletonGroups
{
    /**
     * Project registry slots into ordered groups for a preset scaffold.
     *
     * @return list<array{id: string, label: string, slots: list<string>, properties?: array<string, array<string, string>>}>
     */
    public function project(HotwireRegistry $registry): array
    {
        $components = $registry->components();
        $groups = [];
        $componentGroups = [];
        $controllerGroups = [];
        $ownerGroups = [];
        $slotGroups = [];

        foreach ($components as $key => $component) {
            $componentGroups[$key] = count($groups);
            $ownerGroups[$component->class] ??= $componentGroups[$key];
            $groups[] = [
                'id' => "component:{$key}",
                'label' => $component->displayName(),
                'slots' => [],
            ];
        }

        foreach ($registry->controllers() as $identifier => $controller) {
            $controllerGroups[$identifier] = count($groups);
            $groups[] = [
                'id' => "controller:{$identifier}",
                'label' => str($controller->identifier)->replace('--', ' ')->replace('-', ' ')->title().' controller',
                'slots' => [],
            ];
        }

        $seen = [];

        // Give a family's own declaration priority even when a consumer sorts before it.
        foreach ([true, false] as $ownerDeclarationPhase) {
            foreach ($components as $key => $component) {
                foreach ($component->styling->visualSlots() as $slot) {
                    $group = $this->componentGroup(
                        $key,
                        $component->styling->slotOwner($slot),
                        $componentGroups,
                        $ownerGroups,
                    );

                    if (($group === $componentGroups[$key]) !== $ownerDeclarationPhase) {
                        continue;
                    }

                    if ($this->firstOccurrence($seen, $slot)) {
                        $groups[$group]['slots'][] = $slot;
                        $slotGroups[$slot] = $group;
                    }

                    if (($properties = $component->styling->presetPropertiesFor($slot)) !== []) {
                        $target = $slotGroups[$slot];
                        $groups[$target]['properties'][$slot] = $this->mergeProperties(
                            $groups[$target]['properties'][$slot] ?? [],
                            $slot,
                            $properties,
                        );
                    }
                }
            }
        }

        foreach ($registry->controllers() as $identifier => $controller) {
            foreach ($controller->styling->visualSlots() as $slot) {
                if ($this->firstOccurrence($seen, $slot)) {
                    $groups[$controllerGroups[$identifier]]['slots'][] = $slot;
                    $slotGroups[$slot] = $controllerGroups[$identifier];
                }

                if (($properties = $controller->styling->presetPropertiesFor($slot)) !== []) {
                    $target = $slotGroups[$slot];
                    $groups[$target]['properties'][$slot] = $this->mergeProperties(
                        $groups[$target]['properties'][$slot] ?? [],
                        $slot,
                        $properties,
                    );
                }
            }
        }

        return array_values(array_filter($groups, fn (array $group): bool => $group['slots'] !== []));
    }

    /**
     * @param  array<string, int>  $componentGroups
     * @param  array<class-string, int>  $ownerGroups
     */
    private function componentGroup(string $key, ?string $owner, array $componentGroups, array $ownerGroups): int
    {
        return $owner === null ? $componentGroups[$key] : $ownerGroups[$owner] ?? $componentGroups[$key];
    }

    /**
     * @param  array<string, true>  $seen
     */
    private function firstOccurrence(array &$seen, string $slot): bool
    {
        if (isset($seen[$slot])) {
            return false;
        }

        $seen[$slot] = true;

        return true;
    }

    /**
     * @param  array<string, string>  $existing
     * @param  array<string, string>  $properties
     * @return array<string, string>
     */
    private function mergeProperties(array $existing, string $slot, array $properties): array
    {
        foreach ($properties as $property => $value) {
            $current = $existing[$property] ?? null;

            if ($current !== null && $current !== $value) {
                throw new RuntimeException("Conflicting preset property defaults for [{$slot}] [{$property}].");
            }

            $existing[$property] = $value;
        }

        return $existing;
    }
}
