<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Tokenize CSS and walk its style rules without depending on how the source is formatted.
 *
 * This class owns the package's lexical CSS vocabulary. Consumers interpret its events for their
 * own extraction policy rather than maintaining independent quote, comment, and delimiter state.
 *
 * @internal
 */
final class CssRules
{
    private const string TOKEN_CHARACTERS = '()[]{}:;';

    /**
     * Expose source-ordered tokens with delimiter state before each token.
     *
     * Strings and comments each produce one event, while escapes suppress syntax events for the
     * escaped pair. Invalid offsets retain encounter order for diagnostic recovery.
     *
     * @return array{
     *     events: list<array{offset: int, character: string, length: int, depth: int, blockDepth: int, groupDepth: int, type: string, closed?: bool}>,
     *     pairs: array<int, int>,
     *     valid: bool,
     *     invalidOffsets: int[]
     * }
     */
    public function tokenize(string $css, string $additionalCharacters = ''): array
    {
        $events = [];
        $pairs = [];
        $stack = [];
        $blockDepth = 0;
        $groupDepth = 0;
        $valid = true;
        $invalidOffsets = [];
        $tailInvalidOffsets = [];
        $length = strlen($css);
        $closers = [')' => '(', ']' => '[', '}' => '{'];
        $eventCharacters = self::TOKEN_CHARACTERS.$additionalCharacters;

        for ($offset = 0; $offset < $length; $offset++) {
            $character = $css[$offset];

            if ($character === '/' && ($css[$offset + 1] ?? null) === '*') {
                $end = strpos($css, '*/', $offset + 2);
                $closed = $end !== false;
                $tokenLength = $closed ? $end - $offset + 2 : $length - $offset;
                $events[] = [
                    'offset' => $offset,
                    'character' => '/*',
                    'length' => $tokenLength,
                    'depth' => count($stack),
                    'blockDepth' => $blockDepth,
                    'groupDepth' => $groupDepth,
                    'type' => 'comment',
                    'closed' => $closed,
                ];

                if (! $closed) {
                    $valid = false;
                    $tailInvalidOffsets[] = $offset;
                }

                $offset += $tokenLength - 1;

                continue;
            }

            if ($character === '"' || $character === "'") {
                $end = $offset + 1;

                while ($end < $length) {
                    if ($css[$end] === '\\') {
                        $end += 2;

                        continue;
                    }

                    if ($css[$end] === $character) {
                        break;
                    }

                    $end++;
                }

                $closed = $end < $length;
                $tokenLength = $closed ? $end - $offset + 1 : $length - $offset;
                $events[] = [
                    'offset' => $offset,
                    'character' => $character,
                    'length' => $tokenLength,
                    'depth' => count($stack),
                    'blockDepth' => $blockDepth,
                    'groupDepth' => $groupDepth,
                    'type' => 'string',
                    'closed' => $closed,
                ];

                if (! $closed) {
                    $valid = false;
                    $tailInvalidOffsets[] = $offset;
                }

                $offset += $tokenLength - 1;

                continue;
            }

            if ($character === '\\' && $offset + 1 < $length) {
                $offset++;

                continue;
            }

            if (str_contains($eventCharacters, $character)) {
                $events[] = [
                    'offset' => $offset,
                    'character' => $character,
                    'length' => 1,
                    'depth' => count($stack),
                    'blockDepth' => $blockDepth,
                    'groupDepth' => $groupDepth,
                    'type' => 'character',
                ];
            }

            if (in_array($character, ['(', '[', '{'], true)) {
                $stack[] = ['character' => $character, 'offset' => $offset];
                $blockDepth += (int) ($character === '{');
                $groupDepth += (int) ($character === '(' || $character === '[');

                continue;
            }

            if (! isset($closers[$character])) {
                continue;
            }

            $opening = array_pop($stack);

            if (($opening['character'] ?? null) !== $closers[$character]) {
                $valid = false;
                $invalidOffsets[] = $offset;

                if ($opening !== null) {
                    $invalidOffsets[] = $opening['offset'];
                }
            } elseif ($opening !== null) {
                $pairs[$opening['offset']] = $offset;
            }

            if (($opening['character'] ?? null) === '{') {
                $blockDepth--;
            }

            $groupDepth -= (int) ($character === ')' || $character === ']');
        }

        foreach ($stack as $opening) {
            $tailInvalidOffsets[] = $opening['offset'];
        }

        if ($stack !== []) {
            $valid = false;
        }

        return [
            'events' => $events,
            'pairs' => $pairs,
            'valid' => $valid,
            'invalidOffsets' => array_values(array_unique([...$invalidOffsets, ...$tailInvalidOffsets])),
        ];
    }

    /**
     * Parse style rules, optionally retaining declaration-bearing at-rule blocks.
     *
     * @return list<array{chain: string[], declarations: string}>
     */
    public function parse(string $css, bool $includeAtRuleDeclarations = false): array
    {
        $rules = [];
        $chain = [];
        $declarations = [];
        $buffer = '';
        $cursor = 0;

        foreach ($this->tokenize($css)['events'] as $event) {
            $buffer .= substr($css, $cursor, $event['offset'] - $cursor);
            $cursor = $event['offset'] + $event['length'];

            if ($event['type'] === 'comment') {
                continue;
            }

            $character = $event['character'];
            $structural = $event['type'] === 'character'
                && $event['depth'] === $event['blockDepth'];

            if (! $structural || ! str_contains(';{}', $character)) {
                $buffer .= substr($css, $event['offset'], $event['length']);

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

            if ($chain === []) {
                $buffer = '';

                continue;
            }

            $body = array_pop($declarations).$buffer;
            $buffer = '';

            if ($includeAtRuleDeclarations || ! str_starts_with(end($chain) ?: '', '@')) {
                $rules[] = ['chain' => $chain, 'declarations' => $body];
            }

            array_pop($chain);
        }

        return $rules;
    }

    /** Drop comments while leaving anything that merely looks like one inside a string. */
    public function stripComments(string $css): string
    {
        $comments = array_values(array_filter(
            $this->tokenize($css)['events'],
            fn (array $event): bool => $event['type'] === 'comment' && $event['closed'],
        ));

        foreach (array_reverse($comments) as $comment) {
            $css = substr_replace($css, '', $comment['offset'], $comment['length']);
        }

        return $css;
    }

    /** Drop quoted strings while preserving any unterminated tail for existing diagnostics. */
    public function withoutStrings(string $value): string
    {
        $strings = array_values(array_filter(
            $this->tokenize($value)['events'],
            fn (array $event): bool => $event['type'] === 'string' && $event['closed'],
        ));

        foreach (array_reverse($strings) as $string) {
            $value = substr_replace($value, '', $string['offset'], $string['length']);
        }

        return $value;
    }

    /** Replace comments with spaces so lexical searches retain source offsets. */
    public function maskComments(string $value): string
    {
        $comments = array_values(array_filter(
            $this->tokenize($value)['events'],
            fn (array $event): bool => $event['type'] === 'comment',
        ));

        foreach (array_reverse($comments) as $comment) {
            $value = substr_replace($value, str_repeat(' ', $comment['length']), $comment['offset'], $comment['length']);
        }

        return $value;
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

        $closing = $this->matchingDelimiter($prelude, 0);

        return $closing === null ? null : substr($prelude, 1, $closing - 1);
    }

    /** Return the matching closing delimiter for an opening source offset. */
    public function matchingDelimiter(string $value, int $openingOffset): ?int
    {
        return $this->tokenize($value)['pairs'][$openingOffset] ?? null;
    }

    /**
     * Split CSS syntax outside parentheses and brackets while preserving strings and their contents.
     *
     * @return string[]
     */
    public function splitTopLevel(string $value, string $separators): array
    {
        $parts = [''];
        $cursor = 0;

        foreach ($this->tokenize($value, $separators)['events'] as $event) {
            $parts[array_key_last($parts)] .= substr($value, $cursor, $event['offset'] - $cursor);
            $cursor = $event['offset'] + $event['length'];

            if ($event['type'] === 'comment') {
                continue;
            }

            if ($event['type'] === 'character'
                && $event['groupDepth'] === 0
                && str_contains($separators, $event['character'])) {
                $parts[] = '';

                continue;
            }

            $parts[array_key_last($parts)] .= substr($value, $event['offset'], $event['length']);
        }

        $parts[array_key_last($parts)] .= substr($value, $cursor);

        return array_values(array_filter($parts, fn (string $part): bool => trim($part) !== ''));
    }
}
