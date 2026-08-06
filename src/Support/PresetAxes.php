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

        foreach ($this->rules($this->stripComments($css)) as [$selector, $declarations, $subject]) {
            $this->collectSelector($axes, $selector, $subject);
            $this->collectVariants($axes, $subject, $declarations);
        }

        return $axes;
    }

    /**
     * How many `[data-slot` mentions the scanner accounted for, against how many the stylesheet
     * holds. Unread input is otherwise indistinguishable from a slot that varies by nothing, which
     * is how a reformatted preset can empty the inventory without failing a test.
     *
     * Mentions inside declarations count on both sides on purpose: restricting the total to
     * selector position would need the parse this metric exists to audit. Parity therefore breaks
     * only when text lands in no rule at all, which is exactly the signal wanted.
     *
     * @return array{visited: int, total: int}
     */
    public function coverage(string $css): array
    {
        $stripped = $this->stripComments($css);
        $visited = 0;

        foreach ($this->rules($stripped) as [$selector, $declarations]) {
            $visited += preg_match_all('/\[data-slot\s*=/', $selector.' '.$declarations);
        }

        return ['visited' => $visited, 'total' => (int) preg_match_all('/\[data-slot\s*=/', $stripped)];
    }

    /**
     * Walk the stylesheet into `[selector, declarations, subject slots]`, so extraction depends on
     * the structure rather than on each rule sitting on its own line. At-rule preludes carry the
     * parent's subject down, which is what makes `@layer`, `@media` and `@supports` work by design.
     *
     * @return list<array{0: string, 1: string, 2: string[]}>
     */
    private function rules(string $css): array
    {
        $rules = [];
        $selectors = [];
        $declarations = [];
        $subjects = [];
        $buffer = '';
        $depth = 0;
        $quote = null;
        $length = strlen($css);

        for ($index = 0; $index < $length; $index++) {
            $character = $css[$index];

            if ($quote !== null) {
                $buffer .= $character;

                if ($character === $quote && ($index === 0 || $css[$index - 1] !== '\\')) {
                    $quote = null;
                }

                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                $buffer .= $character;

                continue;
            }

            if ($character === '(' || $character === '[') {
                $depth++;
            } elseif ($character === ')' || $character === ']') {
                $depth--;
            }

            if ($depth !== 0) {
                $buffer .= $character;

                continue;
            }

            if ($character === ';') {
                if ($declarations !== []) {
                    $declarations[array_key_last($declarations)] .= $buffer.';';
                }

                // A statement outside any rule — `@import`, `@source` — carries no declarations.
                $buffer = '';

                continue;
            }

            if ($character === '{') {
                $selector = trim($buffer);
                $parent = end($subjects) ?: [];
                $selectors[] = $selector;
                $declarations[] = '';
                $subjects[] = str_starts_with($selector, '@') ? $parent : ($this->subjectSlots($selector) ?: $parent);
                $buffer = '';

                continue;
            }

            if ($character === '}') {
                if ($selectors === []) {
                    $buffer = '';

                    continue;
                }

                $selector = array_pop($selectors);
                $body = array_pop($declarations).$buffer;
                $subject = array_pop($subjects);
                $buffer = '';

                if (! str_starts_with($selector, '@')) {
                    $rules[] = [$selector, $body, $subject];
                }

                continue;
            }

            $buffer .= $character;
        }

        return $rules;
    }

    /** Drop comments before scanning, leaving anything that merely looks like one inside a string. */
    private function stripComments(string $css): string
    {
        return (string) preg_replace('#("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')|/\*.*?\*/#s', '$1', $css);
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
        }

        $singles = (string) preg_replace('/:is\([^)]*\)(?:\[[^\]]*\])*/', ' ', $selector);
        preg_match_all('/\[data-slot\s*=\s*["\']?([^"\'\]\s]+)["\']?\]((?:\[[^\]]*\])*)/', $singles, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $this->collect($axes, [$match[1]], $match[2]);
        }
    }

    /**
     * Axes written as Tailwind data variants — `data-[orientation=vertical]:flex`. Prefixed forms
     * (`has-`, `group-`, `peer-`, `**:`) describe another element, and so do the unquoted attributes
     * that appear inside arbitrary `[&…]` variants, which never match this shape.
     *
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function collectVariants(array &$axes, array $slots, string $body): void
    {
        preg_match_all('/([a-z*:]*-?)data-\[([a-z-]+)=([^\]\s]+)\]/i', $body, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $prefix, $attribute, $value]) {
            if ($prefix !== '' || $attribute === 'slot') {
                continue;
            }

            $this->push($axes, $slots, $attribute, trim($value, '"\''));
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
            preg_match_all('/\[data-slot\s*=\s*["\']?([^"\'\]\s]+)["\']?\]/', (string) end($compounds), $matches);
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
        preg_match_all('/\[data-([a-z-]+)\s*=\s*["\']?([^"\'\]\s]+)["\']?\]/', $attributes, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $attribute, $value]) {
            if ($attribute === 'slot') {
                continue;
            }

            $this->push($axes, $slots, $attribute, $value);
        }
    }

    /**
     * @param  array<string, array<string, string[]>>  $axes
     * @param  string[]  $slots
     */
    private function push(array &$axes, array $slots, string $attribute, string $value): void
    {
        foreach ($slots as $slot) {
            $axes[$slot][$attribute] = array_values(array_unique([...$axes[$slot][$attribute] ?? [], $value]));
        }
    }
}
