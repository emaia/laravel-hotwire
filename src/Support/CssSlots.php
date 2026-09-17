<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final readonly class CssSlots
{
    public function __construct(private CssRules $rules) {}

    /**
     * Return slots participating in a style rule that has declarations.
     *
     * @return string[]
     */
    public function withDeclarations(string $css): array
    {
        $styled = [];

        foreach ($this->rules->parse($css) as ['chain' => $chain, 'declarations' => $declarations]) {
            if (trim($declarations) === '') {
                continue;
            }

            $selectorChain = implode(' ', array_filter($chain, fn (string $block): bool => ! str_starts_with($block, '@')));
            preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $selectorChain, $matches);
            $styled = [...$styled, ...$matches[1]];

            if (($root = $this->styledScopeRoot($chain)) !== null) {
                preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $root, $scopeMatches);
                $styled = [...$styled, ...$scopeMatches[1]];
            }
        }

        return array_values(array_unique($styled));
    }

    /**
     * Return slots referenced by selectors or Tailwind data-slot variants.
     *
     * @return string[]
     */
    public function referenced(string $css): array
    {
        $referenced = [];

        foreach ($this->rules->parse($css) as ['chain' => $chain, 'declarations' => $declarations]) {
            foreach ($chain as $selector) {
                preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $selector, $matches);
                $referenced = [...$referenced, ...$matches[1]];
            }

            preg_match_all(
                '/data-\[slot\s*=\s*["\']?([a-z0-9-]+)["\']?\]/',
                $this->rules->withoutStrings($declarations),
                $variants,
            );
            $referenced = [...$referenced, ...$variants[1]];
        }

        return array_values(array_unique(array_filter($referenced)));
    }

    /**
     * Return custom properties declared on rules whose subject is the requested slot.
     *
     * @return string[]
     */
    public function customPropertiesFor(string $css, string $slot): array
    {
        $properties = [];

        foreach ($this->rules->parse($css) as ['chain' => $chain, 'declarations' => $declarations]) {
            if (! in_array($slot, $this->subjectSlots($chain), true)) {
                continue;
            }

            preg_match_all(
                '/(?:^|;)\s*(--[a-z0-9_-]+)\s*:/i',
                $this->rules->withoutStrings($declarations),
                $matches,
            );
            $properties = [...$properties, ...$matches[1]];
        }

        return array_values(array_unique($properties));
    }

    /** @param string[] $chain */
    private function styledScopeRoot(array $chain): ?string
    {
        $selector = (string) end($chain);

        foreach ($this->rules->splitTopLevel($selector, ',') as $single) {
            $compounds = $this->rules->splitTopLevel($single, ' >+~|');
            $subject = trim((string) end($compounds));

            if (! $this->containsScopeSubject($subject) || $this->compoundHasPseudoElement($subject)) {
                continue;
            }

            for ($index = count($chain) - 2; $index >= 0; $index--) {
                if (($root = $this->rules->scopeRoot($chain[$index])) !== null) {
                    return $root;
                }
            }
        }

        return null;
    }

    private function containsScopeSubject(string $selector): bool
    {
        $ignoreThrough = -1;
        $events = [];
        $scan = $this->rules->scan(
            $selector,
            function (array $event) use (&$events): void {
                if ($event['type'] === 'character') {
                    $events[] = $event;
                }
            },
            collectPairs: true,
        );

        foreach ($events as $event) {
            $index = $event['offset'];

            if ($index <= $ignoreThrough) {
                continue;
            }

            $character = $event['character'];

            if ($character === '[') {
                $ignoreThrough = $scan['pairs'][$index] ?? strlen($selector) - 1;

                continue;
            }

            $previous = $index > 0 ? $selector[$index - 1] : '';

            if ($character !== ':' || $previous === ':') {
                continue;
            }

            if (strncasecmp(substr($selector, $index, 6), ':scope', 6) === 0) {
                $boundary = $selector[$index + 6] ?? '';

                if ($boundary === '' || preg_match('/[a-z0-9_-]/i', $boundary) !== 1) {
                    return true;
                }
            }

            if (preg_match('/\A:([a-z-]+)\(/i', substr($selector, $index), $function) !== 1) {
                continue;
            }

            $open = $index + strlen($function[0]) - 1;
            $end = $scan['pairs'][$open] ?? strlen($selector) - 1;

            if (in_array(strtolower($function[1]), ['is', 'where'], true)
                && $this->functionalArgumentsTargetScope(substr($selector, $open + 1, $end - $open - 1))) {
                return true;
            }

            $ignoreThrough = $end;
        }

        return false;
    }

    /**
     * Resolve slots targeted by the innermost selector, following only explicit nesting refinements.
     *
     * @param  string[]  $chain
     * @return string[]
     */
    private function subjectSlots(array $chain): array
    {
        return $this->subjectSlotsAt($chain, count($chain) - 1);
    }

    /**
     * @param  string[]  $chain
     * @return string[]
     */
    private function subjectSlotsAt(array $chain, int $index): array
    {
        while ($index >= 0 && str_starts_with(trim($chain[$index]), '@')) {
            $index--;
        }

        if ($index < 0) {
            return [];
        }

        $slots = [];

        foreach ($this->rules->splitTopLevel($chain[$index], ',') as $single) {
            $single = trim($single);

            if (($direct = $this->selectorSubjectSlots($single)) !== []) {
                $slots = [...$slots, ...$direct];

                continue;
            }

            $compounds = $this->rules->splitTopLevel($single, ' >+~|');
            $subject = trim((string) end($compounds));

            if ($this->containsScopeSubject($subject) && ! $this->compoundHasPseudoElement($subject)) {
                for ($ancestor = $index - 1; $ancestor >= 0; $ancestor--) {
                    if (($root = $this->rules->scopeRoot($chain[$ancestor])) !== null) {
                        $slots = [...$slots, ...$this->selectorSubjectSlots($root)];

                        break;
                    }
                }

                continue;
            }

            if ($this->refinesParentSubject($single)) {
                $slots = [...$slots, ...$this->subjectSlotsAt($chain, $index - 1)];
            }
        }

        return array_values(array_unique($slots));
    }

    /** @return string[] */
    private function selectorSubjectSlots(string $selector): array
    {
        $slots = [];

        foreach ($this->rules->splitTopLevel($selector, ',') as $single) {
            $single = trim($single);

            if (($inner = $this->wholeFunctionalArguments($single)) !== null) {
                $slots = [...$slots, ...$this->selectorSubjectSlots($inner)];

                continue;
            }

            $compounds = $this->rules->splitTopLevel($single, ' >+~|');
            $slots = [...$slots, ...$this->compoundSlots((string) end($compounds))];
        }

        return array_values(array_unique($slots));
    }

    private function wholeFunctionalArguments(string $selector): ?string
    {
        if (preg_match('/^:(?:is|where)\(/i', $selector, $function) !== 1) {
            return null;
        }

        $open = strlen($function[0]) - 1;
        $scan = $this->rules->scan($selector, collectPairs: true);
        $end = $scan['pairs'][$open] ?? null;

        return $end === strlen($selector) - 1
            ? substr($selector, $open + 1, $end - $open - 1)
            : null;
    }

    /** @return string[] */
    private function compoundSlots(string $compound): array
    {
        $attributeOffsets = [];
        $functionOffsets = [];
        $scan = $this->rules->scan(
            $compound,
            function (array $event) use (&$attributeOffsets, &$functionOffsets, $compound): void {
                if ($event['type'] !== 'character' || $event['groupDepth'] !== 0) {
                    return;
                }

                if ($event['character'] === '[') {
                    $attributeOffsets[] = $event['offset'];
                }

                if ($event['character'] === ':'
                    && preg_match('/\A:(?:is|where)\(/i', substr($compound, $event['offset']), $function) === 1) {
                    $functionOffsets[] = $event['offset'] + strlen($function[0]) - 1;
                }
            },
            additionalCharacters: ':',
            collectPairs: true,
        );

        if ($this->compoundHasPseudoElement($compound)) {
            return [];
        }

        $slots = [];

        foreach ($attributeOffsets as $offset) {
            $end = $scan['pairs'][$offset] ?? null;

            if ($end === null) {
                continue;
            }

            $attribute = substr($compound, $offset, $end - $offset + 1);

            if (preg_match('/^\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]$/', $attribute, $match) === 1) {
                $slots[] = $match[1];
            }
        }

        foreach ($functionOffsets as $offset) {
            $end = $scan['pairs'][$offset] ?? null;

            if ($end !== null) {
                $slots = [
                    ...$slots,
                    ...$this->selectorSubjectSlots(substr($compound, $offset + 1, $end - $offset - 1)),
                ];
            }
        }

        return array_values(array_unique($slots));
    }

    private function refinesParentSubject(string $selector): bool
    {
        foreach ($this->rules->splitTopLevel($selector, ',') as $single) {
            $compounds = $this->rules->splitTopLevel($single, ' >+~|');
            $subject = trim((string) end($compounds));

            if (! $this->compoundContainsNestingSubject($subject) || $this->compoundHasPseudoElement($subject)) {
                return false;
            }
        }

        return true;
    }

    private function compoundContainsNestingSubject(string $compound): bool
    {
        $contains = false;
        $functionOffsets = [];
        $scan = $this->rules->scan(
            $compound,
            function (array $event) use (&$contains, &$functionOffsets, $compound): void {
                if ($event['type'] !== 'character' || $event['groupDepth'] !== 0) {
                    return;
                }

                if ($event['character'] === '&') {
                    $contains = true;
                }

                if ($event['character'] === ':'
                    && preg_match('/\A:(?:is|where)\(/i', substr($compound, $event['offset']), $function) === 1) {
                    $functionOffsets[] = $event['offset'] + strlen($function[0]) - 1;
                }
            },
            additionalCharacters: '&:',
            collectPairs: true,
        );

        foreach ($functionOffsets as $offset) {
            $end = $scan['pairs'][$offset] ?? null;

            if ($end === null) {
                continue;
            }

            foreach ($this->rules->splitTopLevel(substr($compound, $offset + 1, $end - $offset - 1), ',') as $selector) {
                $compounds = $this->rules->splitTopLevel($selector, ' >+~|');

                if ($this->compoundContainsNestingSubject(trim((string) end($compounds)))) {
                    return true;
                }
            }
        }

        return $contains;
    }

    private function compoundHasPseudoElement(string $compound): bool
    {
        $contains = false;
        $this->rules->scan(
            $compound,
            function (array $event) use (&$contains, $compound): void {
                if ($event['type'] !== 'character' || $event['groupDepth'] !== 0 || $event['character'] !== ':') {
                    return;
                }

                $suffix = substr($compound, $event['offset']);

                if (str_starts_with($suffix, '::')
                    || preg_match('/\A:(?:before|after|first-line|first-letter)(?![a-z0-9_-])/i', $suffix) === 1) {
                    $contains = true;
                }
            },
            additionalCharacters: ':',
        );

        return $contains;
    }

    private function functionalArgumentsTargetScope(string $arguments): bool
    {
        foreach ($this->rules->splitTopLevel($arguments, ',') as $selector) {
            $compounds = $this->rules->splitTopLevel($selector, ' >+~|');
            $subject = trim((string) end($compounds));

            if ($this->containsScopeSubject($subject) && ! $this->compoundHasPseudoElement($subject)) {
                return true;
            }
        }

        return false;
    }
}
