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

        foreach ($this->rules->parse($this->rules->stripComments($css)) as ['chain' => $chain, 'declarations' => $declarations]) {
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

        foreach ($this->rules->parse($this->rules->stripComments($css)) as ['chain' => $chain, 'declarations' => $declarations]) {
            foreach ($chain as $selector) {
                preg_match_all('/\[data-slot\s*=\s*["\']?([a-z0-9-]+)["\']?\s*\]/', $selector, $matches);
                $referenced = [...$referenced, ...$matches[1]];
            }

            preg_match_all(
                '/(?:"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\')(*SKIP)(*F)|data-\[slot\s*=\s*["\']?([a-z0-9-]+)["\']?\]/',
                $declarations,
                $variants,
            );
            $referenced = [...$referenced, ...$variants[1]];
        }

        return array_values(array_unique(array_filter($referenced)));
    }

    /** @param string[] $chain */
    private function styledScopeRoot(array $chain): ?string
    {
        $selector = (string) end($chain);

        foreach ($this->rules->splitTopLevel($selector, ',') as $single) {
            $compounds = $this->rules->splitTopLevel($single, ' >+~|');
            $subject = trim((string) end($compounds));

            if (! $this->containsScopeSubject($subject)) {
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
        $length = strlen($selector);

        for ($index = 0; $index < $length; $index++) {
            $character = $selector[$index];

            if ($character === '"' || $character === "'") {
                $index = $this->skipString($selector, $index);

                continue;
            }

            if ($character === '[') {
                $index = $this->matchingDelimiter($selector, $index, '[', ']');

                continue;
            }

            $previous = $index > 0 ? $selector[$index - 1] : '';

            if ($character !== ':' || $previous === '\\' || $previous === ':') {
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
            $end = $this->matchingDelimiter($selector, $open, '(', ')');

            if (in_array(strtolower($function[1]), ['is', 'where'], true)
                && $this->functionalArgumentsTargetScope(substr($selector, $open + 1, $end - $open - 1))) {
                return true;
            }

            $index = $end;
        }

        return false;
    }

    private function functionalArgumentsTargetScope(string $arguments): bool
    {
        foreach ($this->rules->splitTopLevel($arguments, ',') as $selector) {
            $compounds = $this->rules->splitTopLevel($selector, ' >+~|');

            if ($this->containsScopeSubject(trim((string) end($compounds)))) {
                return true;
            }
        }

        return false;
    }

    private function matchingDelimiter(string $value, int $offset, string $open, string $close): int
    {
        $depth = 0;
        $length = strlen($value);

        for ($index = $offset; $index < $length; $index++) {
            if ($value[$index] === '"' || $value[$index] === "'") {
                $index = $this->skipString($value, $index);

                continue;
            }

            $depth += (int) ($value[$index] === $open) - (int) ($value[$index] === $close);

            if ($depth === 0) {
                return $index;
            }
        }

        return $length - 1;
    }

    private function skipString(string $value, int $offset): int
    {
        $quote = $value[$offset];
        $length = strlen($value);

        for ($index = $offset + 1; $index < $length; $index++) {
            if ($value[$index] === '\\') {
                $index++;

                continue;
            }

            if ($value[$index] === $quote) {
                return $index;
            }
        }

        return $length - 1;
    }
}
