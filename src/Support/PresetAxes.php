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
            [$selector, $body] = array_pad(explode('{', $line, 2), 2, '');
            $selector = trim($selector);

            if (! str_contains($selector, '[data-slot=')) {
                continue;
            }

            $this->collectVariants($axes, $this->subjectSlots($selector), $body);

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
     * Axes written as Tailwind data variants — `data-[orientation=vertical]:flex`. Prefixed forms
     * (`has-`, `group-`, `peer-`, `**:`) describe another element, so they are not axes of this slot.
     *
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function collectVariants(array &$axes, array $slots, string $body): void
    {
        preg_match_all('/([a-z*:]*-?)data-\[([a-z-]+)=([a-z0-9-]+)\]/', $body, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $prefix, $attribute, $value]) {
            if ($prefix !== '' || $attribute === 'slot') {
                continue;
            }

            foreach ($slots as $slot) {
                $axes[$slot][$attribute] = array_values(array_unique([...$axes[$slot][$attribute] ?? [], $value]));
            }
        }
    }

    /**
     * The slots a rule actually styles: those in the last compound of each comma-separated selector.
     * In `[data-slot="navbar"][data-variant="line"] [data-slot="navbar-item"]` the subject is
     * `navbar-item`.
     *
     * @return string[]
     */
    private function subjectSlots(string $selector): array
    {
        $slots = [];

        foreach ($this->splitTopLevel($selector, ',') as $single) {
            $compounds = $this->splitTopLevel($single, ' >+~');
            preg_match_all('/\[data-slot="([a-z0-9-]+)"\]/', (string) end($compounds), $matches);
            $slots = [...$slots, ...$matches[1]];
        }

        return array_values(array_unique($slots));
    }

    /** @return string[] */
    private function splitTopLevel(string $value, string $separators): array
    {
        $parts = [''];
        $depth = 0;

        foreach (str_split($value) as $character) {
            $depth += (int) in_array($character, ['(', '['], true) - (int) in_array($character, [')', ']'], true);

            if ($depth === 0 && str_contains($separators, $character)) {
                $parts[] = '';

                continue;
            }

            $parts[array_key_last($parts)] .= $character;
        }

        return array_values(array_filter($parts, fn (string $part): bool => trim($part) !== ''));
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
