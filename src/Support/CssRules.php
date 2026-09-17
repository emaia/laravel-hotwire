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
     * Stream source-ordered tokens with delimiter state before each token.
     *
     * Strings and comments each produce one event. Escapes emit none at all, so a consumer only
     * sees escaped syntax by slicing the source between events. Invalid offsets retain encounter
     * order for diagnostic recovery. Encountered errors are reported immediately; open strings,
     * comments, and delimiters that remain unresolved are appended after the scan reaches the end.
     *
     * @param  null|callable(array{offset: int, character: string, length: int, depth: int, blockDepth: int, groupDepth: int, type: string, closed?: bool}): void  $consume
     * @return array{pairs: array<int, int>, valid: bool, invalidOffsets: int[]}
     */
    public function scan(
        string $css,
        ?callable $consume = null,
        string $additionalCharacters = '',
        bool $collectPairs = false,
    ): array {
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

                if ($consume !== null) {
                    $consume([
                        'offset' => $offset,
                        'character' => '/*',
                        'length' => $tokenLength,
                        'depth' => count($stack),
                        'blockDepth' => $blockDepth,
                        'groupDepth' => $groupDepth,
                        'type' => 'comment',
                        'closed' => $closed,
                    ]);
                }

                if (! $closed) {
                    $valid = false;
                    $tailInvalidOffsets[] = $offset;
                }

                $offset += $tokenLength - 1;

                continue;
            }

            if ($character === '"' || $character === "'") {
                $end = $offset + 1;
                $closed = false;

                while ($end < $length) {
                    if ($css[$end] === '\\') {
                        $newlineLength = $this->newlineLength($css, $end + 1);

                        if ($newlineLength > 0) {
                            $end += $newlineLength + 1;

                            continue;
                        }

                        if ($end + 1 >= $length) {
                            $end = $length;

                            break;
                        }

                        $end = $this->escapeEnd($css, $end) + 1;

                        continue;
                    }

                    if ($this->newlineLength($css, $end) > 0) {
                        break;
                    }

                    if ($css[$end] === $character) {
                        $closed = true;
                        $end++;

                        break;
                    }

                    $end++;
                }

                $tokenLength = max(1, $end - $offset);

                if ($consume !== null) {
                    $consume([
                        'offset' => $offset,
                        'character' => $character,
                        'length' => $tokenLength,
                        'depth' => count($stack),
                        'blockDepth' => $blockDepth,
                        'groupDepth' => $groupDepth,
                        'type' => 'string',
                        'closed' => $closed,
                    ]);
                }

                if (! $closed) {
                    $valid = false;
                    $invalidOffsets[] = $offset;
                }

                $offset += $tokenLength - 1;

                continue;
            }

            if ($character === '\\') {
                if ($offset + 1 >= $length || $this->newlineLength($css, $offset + 1) > 0) {
                    $valid = false;
                    $invalidOffsets[] = $offset;
                    $offset += $this->newlineLength($css, $offset + 1);

                    continue;
                }

                $offset = $this->escapeEnd($css, $offset);

                continue;
            }

            if (str_contains($eventCharacters, $character) && $consume !== null) {
                $consume([
                    'offset' => $offset,
                    'character' => $character,
                    'length' => 1,
                    'depth' => count($stack),
                    'blockDepth' => $blockDepth,
                    'groupDepth' => $groupDepth,
                    'type' => 'character',
                ]);
            }

            if (in_array($character, ['(', '[', '{'], true)) {
                $stack[] = [$character, $offset];
                $blockDepth += (int) ($character === '{');
                $groupDepth += (int) ($character === '(' || $character === '[');

                continue;
            }

            if (! isset($closers[$character])) {
                continue;
            }

            $opening = end($stack);

            if (($opening[0] ?? null) !== $closers[$character]) {
                $valid = false;
                $invalidOffsets[] = $offset;
            } else {
                array_pop($stack);

                if ($collectPairs) {
                    $pairs[$opening[1]] = $offset;
                }

                if ($opening[0] === '{') {
                    $blockDepth--;
                }
            }

            // Keyed on the closing character: splitTopLevel retains its historical plain
            // parenthesis/bracket counter even while typed delimiter recovery reports an error.
            $groupDepth -= (int) ($character === ')' || $character === ']');
        }

        foreach ($stack as $opening) {
            $tailInvalidOffsets[] = $opening[1];
        }

        if ($stack !== []) {
            $valid = false;
        }

        return [
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
        return $this->structuralAnalysis($css, $includeAtRuleDeclarations, false)['rules'];
    }

    /**
     * Analyze valid style rules and block preludes in one structural pass.
     *
     * @return array{rules: list<array{chain: string[], declarations: string}>, blocks: string[], invalidScopeRoots: string[], valid: bool}
     */
    public function analyze(string $css, bool $includeAtRuleDeclarations = false): array
    {
        return $this->structuralAnalysis($css, $includeAtRuleDeclarations, true);
    }

    /**
     * @return array{rules: list<array{chain: string[], declarations: string}>, blocks: string[], invalidScopeRoots: string[], valid: bool}
     */
    private function structuralAnalysis(string $css, bool $includeAtRuleDeclarations, bool $collectBlocks): array
    {
        $rules = [];
        $blocks = [];
        $frames = [];
        $chain = [];
        $blockFrames = [];
        $declarations = [];
        $buffer = '';
        $cursor = 0;
        $bufferStart = 0;

        $scan = $this->scan($css, function (array $event) use (
            $css,
            $includeAtRuleDeclarations,
            $collectBlocks,
            &$rules,
            &$blocks,
            &$frames,
            &$chain,
            &$blockFrames,
            &$declarations,
            &$buffer,
            &$cursor,
            &$bufferStart,
        ): void {
            $buffer .= substr($css, $cursor, $event['offset'] - $cursor);
            $cursor = $event['offset'] + $event['length'];

            if ($event['type'] === 'comment') {
                return;
            }

            $character = $event['character'];
            $structural = $event['type'] === 'character'
                && $event['depth'] === $event['blockDepth'];

            if (! $structural || ! str_contains(';{}', $character)) {
                $buffer .= substr($css, $event['offset'], $event['length']);

                return;
            }

            if ($character === ';') {
                if ($declarations !== []) {
                    $declarations[array_key_last($declarations)] .= $buffer.';';
                }

                $buffer = '';
                $bufferStart = $cursor;

                return;
            }

            if ($character === '{') {
                $parent = end($blockFrames);
                $frameId = count($frames);
                $prelude = trim(preg_replace('/\s+/', ' ', $buffer) ?? '');
                $chain[] = $prelude;
                $frames[] = [
                    'parent' => $parent === false ? null : $parent,
                    'start' => $bufferStart,
                    'preludeEnd' => $event['offset'],
                    'prelude' => $prelude,
                    'end' => null,
                ];
                $blockFrames[] = $frameId;
                $declarations[] = '';
                $buffer = '';
                $bufferStart = $cursor;

                return;
            }

            if ($chain === []) {
                $buffer = '';
                $bufferStart = $cursor;

                return;
            }

            $body = array_pop($declarations).$buffer;
            $frameId = array_pop($blockFrames);
            $frame = $frames[$frameId];
            $frames[$frameId]['end'] = $event['offset'];
            $prelude = (string) end($chain);
            $buffer = '';
            $bufferStart = $cursor;

            if ($collectBlocks) {
                $blocks[] = [
                    'prelude' => $prelude,
                    'frame' => $frameId,
                    'sourceStart' => $frame['start'],
                ];
            }

            if ($includeAtRuleDeclarations || ! str_starts_with($prelude, '@')) {
                $rules[] = [
                    'chain' => $chain,
                    'declarations' => $body,
                    'frame' => $frameId,
                    'start' => $frame['start'],
                    'end' => $event['offset'],
                ];
            }

            array_pop($chain);
        });

        $invalidOffsets = $scan['invalidOffsets'];
        sort($invalidOffsets, SORT_NUMERIC);
        $invalidPreludeChains = [];

        foreach ($frames as $frameId => $frame) {
            $invalidPreludeChains[$frameId] = $this->hasInvalidOffset(
                $invalidOffsets,
                $frame['start'],
                $frame['preludeEnd'],
            ) || ($frame['parent'] !== null && $invalidPreludeChains[$frame['parent']]);
        }

        $parsed = [];

        foreach ($rules as $rule) {
            $parent = $frames[$rule['frame']]['parent'];
            $invalidAncestor = $parent !== null && $invalidPreludeChains[$parent];

            if (! $invalidAncestor && ! $this->hasInvalidOffset($invalidOffsets, $rule['start'], $rule['end'])) {
                $parsed[] = ['chain' => $rule['chain'], 'declarations' => $rule['declarations']];
            }
        }

        $parsedBlocks = [];

        foreach ($blocks as $block) {
            if (! $invalidPreludeChains[$block['frame']]) {
                $parsedBlocks[$block['sourceStart']] = $block['prelude'];
            }
        }

        ksort($parsedBlocks, SORT_NUMERIC);

        $invalidScopeRoots = [];

        foreach ($frames as $frame) {
            $root = $this->scopeRoot($frame['prelude']);
            $end = $frame['end'] ?? max(0, strlen($css) - 1);

            if ($root !== null && $this->hasInvalidOffset($invalidOffsets, $frame['start'], $end)) {
                $invalidScopeRoots[] = $root;
            }
        }

        return [
            'rules' => $parsed,
            'blocks' => array_values($parsedBlocks),
            'invalidScopeRoots' => array_values(array_unique($invalidScopeRoots)),
            'valid' => $scan['valid'],
        ];
    }

    /** Drop comments while leaving anything that merely looks like one inside a string. */
    public function stripComments(string $css): string
    {
        return $this->transform($css, fn (array $event): ?string => $event['type'] === 'comment' && $event['closed'] ? '' : null);
    }

    /** Drop quoted strings while preserving any unterminated tail for existing diagnostics. */
    public function withoutStrings(string $value): string
    {
        return $this->transform($value, fn (array $event): ?string => $event['type'] === 'string' && $event['closed'] ? '' : null);
    }

    /** Replace comments with spaces so lexical searches retain source offsets. */
    public function maskComments(string $value): string
    {
        return $this->transform(
            $value,
            fn (array $event): ?string => $event['type'] === 'comment' ? str_repeat(' ', $event['length']) : null,
        );
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

        $closing = $this->scan($prelude, collectPairs: true)['pairs'][0] ?? null;

        return $closing === null ? null : substr($prelude, 1, $closing - 1);
    }

    /**
     * Split CSS syntax outside parentheses and brackets while preserving strings and their
     * contents. Comments are dropped from every part.
     *
     * @return string[]
     */
    public function splitTopLevel(string $value, string $separators): array
    {
        $parts = [''];
        $cursor = 0;

        $this->scan($value, function (array $event) use ($value, $separators, &$parts, &$cursor): void {
            $parts[array_key_last($parts)] .= substr($value, $cursor, $event['offset'] - $cursor);
            $cursor = $event['offset'] + $event['length'];

            if ($event['type'] === 'comment') {
                return;
            }

            if ($event['type'] === 'character'
                && $event['groupDepth'] === 0
                && str_contains($separators, $event['character'])) {
                $parts[] = '';

                return;
            }

            $parts[array_key_last($parts)] .= substr($value, $event['offset'], $event['length']);
        }, $separators);

        $parts[array_key_last($parts)] .= substr($value, $cursor);

        return array_values(array_filter($parts, fn (string $part): bool => trim($part) !== ''));
    }

    /**
     * Replace selected tokens in one pass while preserving every untouched source byte.
     *
     * @param  callable(array{offset: int, character: string, length: int, depth: int, blockDepth: int, groupDepth: int, type: string, closed?: bool}): ?string  $replacement
     */
    private function transform(string $value, callable $replacement): string
    {
        $result = '';
        $cursor = 0;
        $changed = false;

        $this->scan($value, function (array $event) use ($value, $replacement, &$result, &$cursor, &$changed): void {
            $replaceWith = $replacement($event);

            if ($replaceWith === null) {
                return;
            }

            $result .= substr($value, $cursor, $event['offset'] - $cursor).$replaceWith;
            $cursor = $event['offset'] + $event['length'];
            $changed = true;
        });

        return $changed ? $result.substr($value, $cursor) : $value;
    }

    /** @param int[] $offsets */
    private function hasInvalidOffset(array $offsets, int $start, int $end): bool
    {
        $low = 0;
        $high = count($offsets) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);

            if ($offsets[$middle] < $start) {
                $low = $middle + 1;
            } else {
                $high = $middle - 1;
            }
        }

        return isset($offsets[$low]) && $offsets[$low] <= $end;
    }

    /** Return the final byte consumed by a valid CSS escape beginning at the given backslash. */
    private function escapeEnd(string $value, int $offset): int
    {
        $length = strlen($value);
        $end = $offset + 1;

        if (! ctype_xdigit($value[$end])) {
            return $end;
        }

        for ($digits = 0; $end < $length && $digits < 6 && ctype_xdigit($value[$end]); $digits++) {
            $end++;
        }

        $whitespaceLength = $this->whitespaceLength($value, $end);

        return $whitespaceLength > 0 ? $end + $whitespaceLength - 1 : $end - 1;
    }

    private function whitespaceLength(string $value, int $offset): int
    {
        if (($value[$offset] ?? null) === "\r" && ($value[$offset + 1] ?? null) === "\n") {
            return 2;
        }

        return isset($value[$offset]) && str_contains(" \t\n\r\f", $value[$offset]) ? 1 : 0;
    }

    private function newlineLength(string $value, int $offset): int
    {
        $character = $value[$offset] ?? null;

        if ($character === "\r" && ($value[$offset + 1] ?? null) === "\n") {
            return 2;
        }

        return $character === "\n" || $character === "\r" || $character === "\f" ? 1 : 0;
    }
}
