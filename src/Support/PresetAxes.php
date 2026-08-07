<?php

namespace Emaia\LaravelHotwire\Support;

final class PresetAxes
{
    public function __construct(private readonly CssRules $rules = new CssRules) {}

    /**
     * Map slot => attribute => values, keyed by the attribute as written (`data-variant`,
     * `aria-expanded`, `open`). A value counts only for the slot in its own compound, so in
     * `[data-slot="sidebar"][data-variant="floating"] [data-slot="sidebar-container"]` the variant
     * belongs to `sidebar`. An attribute written without a value maps to an empty list.
     *
     * @return array<string, array<string, string[]>>
     */
    public function extract(string $css): array
    {
        $axes = [];

        foreach ($this->rules->parse($this->rules->stripComments($css)) as ['chain' => $chain, 'declarations' => $declarations]) {
            $selector = (string) end($chain);
            $subject = $this->subject($chain);

            $this->collectSelector($axes, $selector, $subject);
            $this->collectVariants($axes, $subject, $declarations);
        }

        return $axes;
    }

    /**
     * How many `[data-slot` mentions the scanner accounted for, against how many the stylesheet
     * holds. Unread input is otherwise indistinguishable from a slot that varies by nothing, which
     * is how a reformatted preset can empty the inventory without failing a test. Mentions inside
     * declarations count on both sides, since restricting the total to selector position would
     * need the very parse this audits.
     *
     * @return array{visited: int, total: int}
     */
    public function coverage(string $css): array
    {
        $stripped = $this->rules->stripComments($css);
        $visited = 0;

        foreach ($this->rules->parse($stripped) as ['chain' => $chain, 'declarations' => $declarations]) {
            $visited += preg_match_all('/\[data-slot\s*=/', end($chain).' '.$declarations);
        }

        return ['visited' => $visited, 'total' => (int) preg_match_all('/\[data-slot\s*=/', $stripped)];
    }

    /**
     * The slots a rule styles: those of its own selector, or of the nearest enclosing rule that names
     * any. Inheriting up the chain is what makes `&`-nested rules and `@layer`, `@media` and
     * `@supports` wrappers work — none of them names a subject of its own.
     *
     * @param  string[]  $chain
     * @return string[]
     */
    private function subject(array $chain): array
    {
        for ($index = count($chain) - 1; $index >= 0; $index--) {
            if (str_starts_with($chain[$index], '@')) {
                continue;
            }

            if (($slots = $this->subjectSlots($chain[$index])) !== []) {
                return $slots;
            }
        }

        return [];
    }

    /**
     * Attributes written in the selector. Quoted or bare, since selector text cannot be confused
     * with the arbitrary variants that carry unquoted attributes inside declarations.
     *
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $subject
     */
    private function collectSelector(array &$axes, string $selector, array $subject): void
    {
        if (! str_contains($selector, '[data-slot')) {
            // A nested rule like `&[data-variant="ghost"]` names no slot: it refines the parent's.
            foreach ($this->splitTopLevel($selector, ',') as $single) {
                $compounds = $this->splitTopLevel($single, ' >+~');
                $this->collect($axes, $subject, (string) end($compounds));
            }

            return;
        }

        preg_match_all('/:is\(([^)]*)\)((?:\[[^\]]*\])*)/', $selector, $groups, PREG_SET_ORDER);

        foreach ($groups as $group) {
            preg_match_all('/\[data-slot\s*=\s*["\']?([^"\'\]\s]+)["\']?\]/', $group[1], $slots);
            $this->collect($axes, $slots[1], $group[2]);
            $this->collectCompounds($axes, $group[1]);
        }

        $this->collectCompounds($axes, (string) preg_replace('/:is\([^)]*\)(?:\[[^\]]*\])*/', ' ', $selector));
    }

    /**
     * Attributes trailing a slot in the same compound. Written after an `:is(…)` group they belong to
     * every member, written inside one they belong to that member alone.
     *
     * @param  array<string, array<string, string[]>>  $axes
     */
    private function collectCompounds(array &$axes, string $selector): void
    {
        preg_match_all('/\[data-slot\s*=\s*["\']?([^"\'\]\s]+)["\']?\]((?:\[[^\]]*\])*)/', $selector, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $this->collect($axes, [$match[1]], $match[2]);
        }
    }

    /**
     * Axes written as Tailwind variants — arbitrary `data-[orientation=vertical]:flex` and named
     * `aria-expanded:bg-muted`. Prefixed forms (`has-`, `group-`, `peer-`, `not-`, `**:`) describe
     * another element or negate the state, and so do the unquoted attributes that appear inside
     * arbitrary `[&…]` variants, which never match either shape. Pseudo-class variants such as
     * `disabled:` stay out: they are DOM state, not part of the attribute vocabulary a preset styles.
     *
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function collectVariants(array &$axes, array $slots, string $body): void
    {
        preg_match_all('/([a-z*:]*-?)(data|aria)-\[([a-z-]+)=([^\]\s]+)\]/i', $body, $arbitrary, PREG_SET_ORDER);

        foreach ($arbitrary as [, $prefix, $namespace, $attribute, $value]) {
            if ($prefix !== '' || $attribute === 'slot') {
                continue;
            }

            $this->push($axes, $slots, "$namespace-$attribute", trim($value, '"\''));
        }

        preg_match_all('/([a-z*:]*-?)aria-([a-z]+):/', $body, $named, PREG_SET_ORDER);

        foreach ($named as [, $prefix, $attribute]) {
            if ($prefix === '') {
                $this->push($axes, $slots, "aria-$attribute", 'true');
            }
        }
    }

    /**
     * The slots a rule actually styles: those in the last compound of each comma-separated selector.
     *
     * @return string[]
     */
    private function subjectSlots(string $selector): array
    {
        $slots = [];

        foreach ($this->singles($selector) as $single) {
            $compounds = $this->splitTopLevel($single, ' >+~');
            $slots = [...$slots, ...$this->subjectCompound((string) end($compounds))];
        }

        return array_values(array_unique($slots));
    }

    /**
     * Comma-separated selectors, with a `:is(…)`/`:where(…)` that wraps a whole one unwrapped. Left
     * wrapped, a descendant chain like `:where(a b c)` would read as a single compound naming three
     * subjects, which hands `a` and `b` the styling written for `c`.
     *
     * @return string[]
     */
    private function singles(string $selector): array
    {
        $singles = [];

        foreach ($this->splitTopLevel($selector, ',') as $single) {
            $singles = preg_match('/^:(?:is|where)\((.*)\)$/s', trim($single), $inner) === 1
                ? [...$singles, ...$this->singles($inner[1])]
                : [...$singles, trim($single)];
        }

        return $singles;
    }

    /**
     * The slots a trailing compound names — those of the alternatives when it is a bare `:is(…)`
     * group, as in `:is([data-slot="carousel-prev-button"], [data-slot="carousel-next-button"])`.
     *
     * @return string[]
     */
    private function subjectCompound(string $compound): array
    {
        return preg_match('/^:(?:is|where)\((.*)\)$/s', trim($compound), $inner) === 1
            ? $this->subjectSlots($inner[1])
            : $this->slotNames($compound);
    }

    /** @return string[] */
    private function slotNames(string $compound): array
    {
        preg_match_all('/\[data-slot\s*=\s*["\']?([^"\'\]\s]+)["\']?\]/', $compound, $matches);

        return array_values(array_unique($matches[1]));
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
     * Every attribute of a compound, not only the `data-` ones: `[open]`, `[type="date"]` and
     * `[aria-disabled="true"]` differentiate a slot just as much. Operator forms (`[class*="size-"]`)
     * enumerate nothing and fall outside the name charset.
     *
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function collect(array &$axes, array $slots, string $attributes): void
    {
        preg_match_all('/\[([a-z-]+)\s*(?:=\s*["\']?([^"\'\]\s]+)["\']?)?\]/', $attributes, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1] === 'data-slot') {
                continue;
            }

            $this->push($axes, $slots, $match[1], $match[2] ?? null);
        }
    }

    /**
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function push(array &$axes, array $slots, string $attribute, ?string $value): void
    {
        foreach ($slots as $slot) {
            $collected = $axes[$slot][$attribute] ?? [];
            $axes[$slot][$attribute] = $value === null
                ? $collected
                : array_values(array_unique([...$collected, $value]));
        }
    }
}
