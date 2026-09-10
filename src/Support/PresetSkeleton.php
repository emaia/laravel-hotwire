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
     * Render ordered scaffold groups as empty data-slot CSS rules.
     *
     * @param  list<array{id: string, label: string, slots: list<string>}>  $groups
     * @return string[]
     */
    public function render(array $groups): array
    {
        $seen = [];
        $lines = [];

        foreach ($groups as ['label' => $label, 'slots' => $slots]) {
            $rules = [];

            foreach ($slots as $slot) {
                if (isset($seen[$slot])) {
                    continue;
                }

                $seen[$slot] = true;
                $rules[] = "    [data-slot=\"{$slot}\"] {}";
            }

            if ($rules !== []) {
                $lines = [...$lines, '', "    /* {$label} */", ...$rules];
            }
        }

        return $lines;
    }
}
