<?php

namespace Emaia\LaravelHotwire\Support;

final class PresetAxes
{
    /**
     * Map slot => attribute => values. A value counts only for the slot in its own compound, so in
     * `[data-slot="sidebar"][data-variant="floating"] [data-slot="sidebar-container"]` the variant
     * belongs to `sidebar`.
     *
     * @return array<string, array<string, string[]>>
     */
    public function extract(string $css): array
    {
        $axes = [];

        foreach (explode("\n", $css) as $line) {
            $selector = trim(explode('{', $line)[0]);

            if (! str_contains($selector, '[data-slot=')) {
                continue;
            }

            preg_match_all('/:is\(([^)]*)\)((?:\[[^\]]*\])*)/', $selector, $groups, PREG_SET_ORDER);

            foreach ($groups as $group) {
                preg_match_all('/\[data-slot="([a-z0-9-]+)"\]/', $group[1], $slots);
                $this->collect($axes, $slots[1], $group[2]);
            }

            $singles = (string) preg_replace('/:is\([^)]*\)(?:\[[^\]]*\])*/', ' ', $selector);
            preg_match_all('/\[data-slot="([a-z0-9-]+)"\]((?:\[[^\]]*\])*)/', $singles, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $this->collect($axes, [$match[1]], $match[2]);
            }
        }

        return $axes;
    }

    /**
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function collect(array &$axes, array $slots, string $attributes): void
    {
        preg_match_all('/\[data-([a-z-]+)="([a-z0-9-]+)"\]/', $attributes, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $attribute, $value]) {
            if ($attribute === 'slot') {
                continue;
            }

            foreach ($slots as $slot) {
                $axes[$slot][$attribute] = array_values(array_unique([...$axes[$slot][$attribute] ?? [], $value]));
            }
        }
    }
}
