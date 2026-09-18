<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Render preset-neutral empty CSS rules from the registry scaffold projection.
 *
 * @internal
 */
final class PresetSkeleton
{
    /**
     * Render ordered scaffold groups as data-slot CSS rules with required neutral defaults.
     *
     * @param  list<array{id: string, label: string, slots: list<string>, properties?: array<string, array<string, string>>}>  $groups
     * @return string[]
     */
    public function render(array $groups): array
    {
        $seen = [];
        $lines = [];

        foreach ($groups as $group) {
            ['label' => $label, 'slots' => $slots] = $group;
            $rules = [];
            $properties = $group['properties'] ?? [];

            foreach ($slots as $slot) {
                if (isset($seen[$slot])) {
                    continue;
                }

                $seen[$slot] = true;
                $defaults = $properties[$slot] ?? [];

                if ($defaults === []) {
                    $rules[] = "    [data-slot=\"{$slot}\"] {}";

                    continue;
                }

                $rules[] = "    [data-slot=\"{$slot}\"] {";

                foreach ($defaults as $property => $value) {
                    $rules[] = "        {$property}: {$value};";
                }

                $rules[] = '    }';
            }

            if ($rules !== []) {
                $lines = [...$lines, '', "    /* {$label} */", ...$rules];
            }
        }

        return $lines;
    }
}
