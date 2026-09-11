<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Walks a stylesheet into its style rules, each carrying the chain of blocks enclosing it — at-rule
 * preludes included, its own selector last. Reading by structure rather than line by line is what
 * keeps everything built on top independent of how the CSS is formatted.
 */
final class CssRules
{
    /**
     * @return list<array{chain: string[], declarations: string}>
     */
    public function parse(string $css): array
    {
        $rules = [];
        $chain = [];
        $declarations = [];
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

                $buffer = '';

                continue;
            }

            if ($character === '{') {
                $chain[] = trim(preg_replace('/\s+/', ' ', $buffer) ?? '');
                $declarations[] = '';
                $buffer = '';

                continue;
            }

            if ($character === '}') {
                if ($chain === []) {
                    $buffer = '';

                    continue;
                }

                $body = array_pop($declarations).$buffer;
                $buffer = '';

                if (! str_starts_with(end($chain) ?: '', '@')) {
                    $rules[] = ['chain' => $chain, 'declarations' => $body];
                }

                array_pop($chain);

                continue;
            }

            $buffer .= $character;
        }

        return $rules;
    }

    /** Drop comments before scanning, leaving anything that merely looks like one inside a string. */
    public function stripComments(string $css): string
    {
        return (string) preg_replace('#("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')|/\*.*?\*/#s', '$1', $css);
    }

    /** Extract the root selector from an `@scope` prelude. */
    public function scopeRoot(string $scope): ?string
    {
        if (! str_starts_with($scope, '@scope')) {
            return null;
        }

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
     * Split CSS syntax on top-level separators while preserving functional and attribute contents.
     *
     * @return string[]
     */
    public function splitTopLevel(string $value, string $separators): array
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
}
