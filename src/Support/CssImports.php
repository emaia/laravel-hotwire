<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final class CssImports
{
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
        $length = strlen($content);
        $depth = 0;
        $ruleStart = true;
        $importsAllowed = true;

        for ($offset = $bomLength; $offset < $length; $offset++) {
            if (substr($content, $offset, 2) === '/*') {
                $offset = $this->skipComment($content, $offset);

                continue;
            }

            if ($content[$offset] === '"' || $content[$offset] === "'") {
                if ($depth === 0) {
                    if ($ruleStart) {
                        $importsAllowed = false;
                    }

                    $ruleStart = false;
                }

                $offset = $this->skipString($content, $offset);

                continue;
            }

            if ($content[$offset] === '{') {
                if ($depth === 0) {
                    $importsAllowed = false;
                    $ruleStart = false;
                }

                $depth++;

                continue;
            }

            if ($content[$offset] === '}') {
                $depth = max(0, $depth - 1);

                if ($depth === 0) {
                    $ruleStart = true;
                }

                continue;
            }

            if ($depth !== 0 || ctype_space($content[$offset])) {
                continue;
            }

            if ($content[$offset] === ';') {
                $ruleStart = true;

                continue;
            }

            if (! $ruleStart) {
                continue;
            }

            if (strncasecmp(substr($content, $offset, 7), '@import', 7) !== 0) {
                if (! $this->startsAllowedPrelude($content, $offset)) {
                    $importsAllowed = false;
                }

                $ruleStart = false;

                continue;
            }

            if (! $importsAllowed) {
                $ruleStart = false;

                continue;
            }

            $boundary = $content[$offset + 7] ?? '';

            if ($boundary !== '' && ! ctype_space($boundary) && $boundary !== '"' && $boundary !== "'"
                && substr($content, $offset + 7, 2) !== '/*') {
                $ruleStart = false;

                continue;
            }

            $ruleStart = false;

            for ($end = $offset + 7; $end < $length; $end++) {
                if (substr($content, $end, 2) === '/*') {
                    $end = $this->skipComment($content, $end);

                    continue;
                }

                if ($content[$end] === '"' || $content[$end] === "'") {
                    $end = $this->skipString($content, $end);

                    continue;
                }

                if ($content[$end] === ';') {
                    $ruleLength = $end - $offset + 1;
                    $rules[] = [
                        'content' => substr($content, $offset, $ruleLength),
                        'offset' => $offset,
                        'length' => $ruleLength,
                    ];
                    $offset = $end;
                    $ruleStart = true;

                    break;
                }

                if ($content[$end] === '{') {
                    break;
                }
            }
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

    private function skipComment(string $content, int $offset): int
    {
        $end = strpos($content, '*/', $offset + 2);

        return $end === false ? strlen($content) - 1 : $end + 1;
    }

    private function skipString(string $content, int $offset): int
    {
        $quote = $content[$offset];
        $length = strlen($content);

        for ($end = $offset + 1; $end < $length; $end++) {
            if ($content[$end] === '\\') {
                $end++;

                continue;
            }

            if ($content[$end] === $quote) {
                return $end;
            }
        }

        return $length - 1;
    }
}
