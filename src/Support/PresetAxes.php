<?php

namespace Emaia\LaravelHotwire\Support;

final class PresetAxes
{
    public function __construct(private readonly CssRules $rules = new CssRules) {}

    /**
     * Extract each slot's attribute axes using names as written in CSS.
     *
     * Count values only for the slot in their compound and represent valueless attributes with an empty list.
     *
     * @return array<string, array<string, string[]>>
     */
    public function extract(string $css): array
    {
        $axes = [];

        foreach ($this->rules->parse($this->rules->stripComments($css)) as ['chain' => $chain, 'declarations' => $declarations]) {
            $selector = (string) end($chain);
            $subject = $this->subject($chain);

            foreach ($chain as $ancestor) {
                $this->collectScope($axes, $ancestor);
            }

            $this->collectSelector($axes, $selector, $subject);
            $this->collectVariants($axes, $subject, $declarations);
        }

        return $axes;
    }

    /**
     * Measure parser coverage against every `[data-slot` mention.
     *
     * Include declarations on both sides because restricting the total to selectors would require the same parse this audits.
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

        preg_match_all('/@scope\s+([^{}]+)\{/', $stripped, $scopes);

        foreach ($scopes[1] as $scope) {
            $visited += preg_match_all('/\[data-slot\s*=/', $scope);
        }

        return ['visited' => $visited, 'total' => (int) preg_match_all('/\[data-slot\s*=/', $stripped)];
    }

    /** Collect axes from a scope root without assigning its limit to the styled subject. */
    private function collectScope(array &$axes, string $ancestor): void
    {
        if (! str_starts_with($ancestor, '@scope') || ($root = $this->scopeRoot($ancestor)) === null) {
            return;
        }

        $this->collectSelector($axes, $root, $this->subjectSlots($root));
    }

    private function scopeRoot(string $scope): ?string
    {
        $prelude = trim(substr($scope, strlen('@scope')));
        if (! str_starts_with($prelude, '(')) {
            return null;
        }

        $depth = 0;
        $quote = null;

        foreach (str_split($prelude) as $index => $character) {
            if ($quote !== null) {
                if ($character === $quote && ($index === 0 || $prelude[$index - 1] !== '\\')) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;

                continue;
            }

            $depth += (int) ($character === '(') - (int) ($character === ')');

            if ($depth === 0) {
                return substr($prelude, 1, $index - 1);
            }
        }

        return null;
    }

    /**
     * Resolve styled slots from the selector or nearest enclosing selector.
     *
     * Walking outward lets `&`-nested rules and at-rule wrappers inherit a subject.
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
     * Collect attributes written directly in a selector, accepting quoted and bare values.
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
     * Assign attributes after an `:is(…)` group to every member and attributes inside it to that member alone.
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
     * Collect axes expressed as Tailwind arbitrary or named variants.
     *
     * Ignore prefixes that target another element or negate state, and exclude pseudo-classes from the preset vocabulary.
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
     * Resolve the slots actually styled by each selector's last compound.
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
     * Split comma-separated selectors and unwrap whole-selector `:is(…)` and `:where(…)` groups.
     *
     * Without unwrapping, `:where(a b c)` appears as one compound and assigns the styling for `c` to `a` and `b`.
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
     * Resolve slots named by a trailing compound, including bare `:is(…)` or `:where(…)` alternatives.
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
     * Collect every concrete attribute in a compound, including native and ARIA attributes.
     *
     * Ignore operator forms such as `[class*="size-"]` because they enumerate no value.
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
