<?php

namespace Emaia\LaravelHotwire\Support;

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

/** @internal */
final class PresetSkeletonGroups
{
    /**
     * Project registry slots into ordered groups for a preset scaffold.
     *
     * @return list<array{id: string, label: string, slots: list<string>}>
     */
    public function project(HotwireRegistry $registry): array
    {
        $components = $registry->components();
        $groups = [];
        $componentGroups = [];
        $controllerGroups = [];
        $ownerGroups = [];

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
                    }
                }
            }
        }

        foreach ($registry->controllers() as $identifier => $controller) {
            foreach ($controller->styling->visualSlots() as $slot) {
                if ($this->firstOccurrence($seen, $slot)) {
                    $groups[$controllerGroups[$identifier]]['slots'][] = $slot;
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
}
