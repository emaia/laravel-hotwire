<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final class CssImports
{
    public function __construct(private readonly CssRules $rules = new CssRules) {}

    /**
     * Read legal top-level CSS imports in source order.
     *
     * @return list<array{path: string, conditions: string, offset: int, length: int}>
     */
    public function parse(string $content): array
    {
        $pattern = <<<'REGEX'
            ~^@import(?:\s|/\*.*?\*/)*(?:
                    "(?<double_path>(?:\\.|[^"\\])*)"
                    |
                    '(?<single_path>(?:\\.|[^'\\])*)'
                    |
                    url\((?:\s|/\*.*?\*/)*(?:
                        "(?<url_double_path>(?:\\.|[^"\\])*)"
                        |
                        '(?<url_single_path>(?:\\.|[^'\\])*)'
                        |
                        (?<url_path>[^)\s]+)
                    )(?:\s|/\*.*?\*/)*\)
                )(?<conditions>[^;]*);
            ~isx
            REGEX;
        $imports = [];

        foreach ($this->topLevelRules($content) as $rule) {
            if (preg_match($pattern, $rule['content'], $match, PREG_UNMATCHED_AS_NULL) !== 1) {
                continue;
            }

            $path = $match['double_path']
                ?? $match['single_path']
                ?? $match['url_double_path']
                ?? $match['url_single_path']
                ?? $match['url_path'];

            if (! is_string($path)) {
                continue;
            }

            $imports[] = [
                'path' => $path,
                'conditions' => trim(preg_replace('~/\*.*?\*/~s', ' ', (string) $match['conditions']) ?? (string) $match['conditions']),
                'offset' => $rule['offset'],
                'length' => $rule['length'],
            ];
        }

        return $imports;
    }

    /**
     * Remove imports previously returned by `parse` without changing the remaining source.
     *
     * @param  list<array{offset: int, length: int}>  $imports
     */
    public function remove(string $content, array $imports): string
    {
        foreach (array_reverse($imports) as $import) {
            $content = substr_replace($content, '', $import['offset'], $import['length']);
        }

        return $content;
    }

    /** @return list<array{content: string, offset: int, length: int}> */
    private function topLevelRules(string $content): array
    {
        $bomLength = str_starts_with($content, "\xEF\xBB\xBF") ? 3 : 0;
        $rules = [];
        $start = null;
        $importsAllowed = true;
        $cursor = $bomLength;

        foreach ($this->rules->tokenize($content)['events'] as $event) {
            if ($event['offset'] < $bomLength) {
                continue;
            }

            if ($start === null && $event['depth'] === 0) {
                $segment = substr($content, $cursor, $event['offset'] - $cursor);
                $whitespace = strspn($segment, " \t\n\r\v\f");

                if ($whitespace < strlen($segment)) {
                    $start = $cursor + $whitespace;
                }
            }

            $cursor = $event['offset'] + $event['length'];

            if ($event['type'] === 'comment') {
                continue;
            }

            $characterEvent = $event['type'] === 'character';

            if ($event['blockDepth'] !== 0) {
                if ($characterEvent && $event['character'] === '}' && $event['blockDepth'] === 1) {
                    $start = null;
                }

                continue;
            }

            $character = $event['character'];

            if ($characterEvent && $character === '{' && $event['depth'] === 0) {
                $importsAllowed = false;
                $start = null;

                continue;
            }

            if ($characterEvent && $character === '}' && $event['depth'] === 0) {
                $start = null;

                continue;
            }

            if ($characterEvent && $character === ';' && $event['depth'] === 0) {
                if ($start !== null) {
                    $ruleLength = $event['offset'] - $start + 1;
                    $statement = substr($content, $start, $ruleLength);
                    $isImport = strncasecmp($statement, '@import', 7) === 0;
                    $boundary = $statement[7] ?? '';
                    $validImportBoundary = $boundary === '' || ctype_space($boundary)
                        || $boundary === '"' || $boundary === "'" || substr($statement, 7, 2) === '/*';

                    if ($isImport && $validImportBoundary && $importsAllowed) {
                        $rules[] = ['content' => $statement, 'offset' => $start, 'length' => $ruleLength];
                    } elseif (! $isImport && ! $this->startsAllowedPrelude($content, $start)) {
                        $importsAllowed = false;
                    }
                }

                $start = null;

                continue;
            }

            if ($start !== null) {
                continue;
            }

            $start = $event['offset'];
        }

        return $rules;
    }

    private function startsAllowedPrelude(string $content, int $offset): bool
    {
        foreach (['@charset', '@layer'] as $keyword) {
            if (strncasecmp(substr($content, $offset, strlen($keyword)), $keyword, strlen($keyword)) !== 0) {
                continue;
            }

            $boundary = $content[$offset + strlen($keyword)] ?? '';

            if ($boundary === '' || ctype_space($boundary) || substr($content, $offset + strlen($keyword), 2) === '/*') {
                return true;
            }
        }

        return false;
    }
}
