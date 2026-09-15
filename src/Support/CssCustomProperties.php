<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final class CssCustomProperties
{
    private const array SCOPE_SELECTORS = [
        'root' => ':root',
        'default' => ':where(:root:not([data-theme="dark"]))',
        'light' => '[data-theme="light"]',
        'dark' => '[data-theme="dark"]',
    ];

    /**
     * Inspect custom properties and Tailwind aliases declared by a preset base.
     *
     * @return array{properties: string[], scopes: array{root: string[], default: string[], light: string[], dark: string[], unscoped: string[]}, aliases: array<string, string|null>, violations: string[]}
     */
    public function inspectPresetBase(string $css): array
    {
        $inspection = $this->inspect($css, true);

        return [
            'properties' => $inspection['properties'],
            'scopes' => $inspection['scopes'],
            'aliases' => $inspection['aliases'],
            'violations' => $inspection['violations'],
        ];
    }

    /**
     * Extract custom properties and aliases without imposing preset scope policy.
     * Validity covers CSS delimiters and `@theme inline` declaration shape, not selector or at-rule scope.
     * The scope inventory separates supported top-level token selectors from every other declaration context.
     *
     * @return array{properties: string[], scopes: array{root: string[], default: string[], light: string[], dark: string[], unscoped: string[]}, aliases: array<string, string|null>, valid: bool}
     */
    public function inspectStylesheet(string $css): array
    {
        $inspection = $this->inspect($css, false);

        return [
            'properties' => $inspection['properties'],
            'scopes' => $inspection['scopes'],
            'aliases' => $inspection['aliases'],
            'valid' => $inspection['valid'],
        ];
    }

    /**
     * @return array{properties: string[], scopes: array{root: string[], default: string[], light: string[], dark: string[], unscoped: string[]}, aliases: array<string, string|null>, violations: string[], valid: bool}
     */
    private function inspect(string $css, bool $auditPresetScopes): array
    {
        $rules = new CssRules;
        [, $validSource] = $this->scan($css);
        $css = $rules->stripComments($css);
        $aliases = [];
        $violations = [];
        $ranges = [];
        // Top-level @theme blocks are removed before CssRules collects every other nested declaration below.
        $unscopedAtRuleProperties = [];

        [$atRules, $validSyntax, $invalidOffsets] = $this->topLevelAtRules($css);
        $validStylesheet = $validSource && $validSyntax;

        if (! $validStylesheet) {
            $violations[] = 'invalid CSS syntax';
        }

        foreach ($atRules as $atRule) {
            if (! str_starts_with($atRule['prelude'], '@theme')) {
                if ($auditPresetScopes) {
                    $violations[] = $atRule['prelude'];
                }

                continue;
            }

            $ranges[] = [$atRule['start'], $atRule['end']];

            if (preg_replace('/\s+/', ' ', $atRule['prelude']) !== '@theme inline') {
                if ($auditPresetScopes) {
                    $violations[] = '@theme';
                } else {
                    [$declarations] = $this->customPropertyDeclarations($atRule['body']);

                    foreach (array_keys($declarations) as $name) {
                        $unscopedAtRuleProperties[$name] = true;
                    }
                }

                continue;
            }

            [$declarations, $valid] = $this->customPropertyDeclarations($atRule['body']);

            if (! $valid) {
                $validStylesheet = false;

                if ($auditPresetScopes) {
                    $violations[] = '@theme inline';
                }
            }

            foreach ($declarations as $name => $value) {
                $aliases[$name] = $this->aliasTarget($value);
            }
        }

        foreach (array_reverse($ranges) as [$start, $end]) {
            $css = substr_replace($css, str_repeat(' ', $end - $start), $start, $end - $start);
        }

        foreach ($invalidOffsets as $offset) {
            $css[$offset] = ' ';
        }

        if ($auditPresetScopes && preg_match('/@theme\b/', $this->withoutStrings($css)) === 1) {
            $violations[] = '@theme';
        }

        $properties = $unscopedAtRuleProperties;
        $scopes = [
            'root' => [],
            'default' => [],
            'light' => [],
            'dark' => [],
            'unscoped' => $unscopedAtRuleProperties,
        ];

        foreach ($rules->parse($css, includeAtRuleDeclarations: ! $auditPresetScopes) as ['chain' => $chain, 'declarations' => $body]) {
            $selector = (string) end($chain);
            $branches = $rules->splitTopLevel($selector, ',');
            $branchScopes = array_map($this->scopeFor(...), $branches);
            $supported = $branches !== [] && ! in_array(null, $branchScopes, true);
            [$declarations, $valid] = $this->customPropertyDeclarations($body);
            $recordProperties = ! $auditPresetScopes || (count($chain) === 1 && $supported);

            if ($auditPresetScopes && (count($chain) !== 1 || ! $supported || ! $valid)) {
                $violations[] = $selector;
            }

            if ($recordProperties) {
                foreach (array_keys($declarations) as $name) {
                    $properties[$name] = true;
                }
            }

            if ($recordProperties && (count($chain) !== 1 || ! $supported)) {
                foreach (array_keys($declarations) as $name) {
                    $scopes['unscoped'][$name] = true;
                }
            }

            if (count($chain) === 1 && (! $auditPresetScopes || $supported)) {
                // Foundation inspection preserves recognized branches from mixed selector lists; preset
                // inspection reaches this block only after every branch passes its stricter scope policy.
                foreach (array_unique(array_filter($branchScopes)) as $scope) {
                    foreach (array_keys($declarations) as $name) {
                        $scopes[$scope][$name] = true;
                    }
                }
            }
        }

        return [
            'properties' => array_keys($properties),
            'scopes' => array_map('array_keys', $scopes),
            'aliases' => $aliases,
            'violations' => array_values(array_unique($violations)),
            'valid' => $validStylesheet,
        ];
    }

    /**
     * Classify a token selector, optionally retaining extra conditions when scaffolding package tokens.
     * Permissive matching is case-insensitive and gives dark precedence when conditions conflict.
     */
    public function scopeFor(string $selector, bool $allowConditions = false): ?string
    {
        $selector = trim($selector);

        if ($selector === ':root') {
            return 'root';
        }

        if (preg_match('~^:where\(\s*:root\s*:not\(\s*\[\s*data-theme\s*=\s*(?:"dark"|\'dark\'|dark)\s*\]\s*\)\s*\)$~', $selector) === 1) {
            return 'default';
        }

        if (preg_match('~^\[\s*data-theme\s*=\s*(?|"(light|dark)"|\'(light|dark)\'|(light|dark))\s*\]$~', $selector, $matches) === 1) {
            return $matches[1];
        }

        if ($allowConditions) {
            $declared = preg_replace('/:not(\((?:[^()]++|(?1))*\))/i', '', $selector) ?? $selector;

            foreach (['dark', 'light'] as $scope) {
                if ($this->hasThemeScope($declared, $scope)) {
                    return $scope;
                }
            }
        }

        return null;
    }

    /** Return the canonical selector for a supported token scope. */
    public function selectorFor(string $scope): string
    {
        return self::SCOPE_SELECTORS[$scope]
            ?? throw new \InvalidArgumentException("Unknown CSS token scope [{$scope}].");
    }

    private function hasThemeScope(string $selector, string $scope): bool
    {
        $scope = preg_quote($scope, '~');
        $quoted = '"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'';

        return preg_match(
            '~(?:'.$quoted.')(*SKIP)(*F)|\[\s*data-theme\s*=\s*(?:"'.$scope.'"|\''.$scope.'\'|'.$scope.')\s*\]~i',
            $selector,
        ) === 1;
    }

    /**
     * @return array{array<string, string>, bool}
     */
    private function customPropertyDeclarations(string $body): array
    {
        $declarations = [];
        $valid = false;
        [$parts, $validSyntax] = $this->splitDeclarations($body);
        $invalid = ! $validSyntax;

        foreach ($parts as $declaration) {
            [$colon, $validSyntax] = $this->topLevelColon($declaration);

            if ($colon === null || ! $validSyntax) {
                $invalid = true;

                continue;
            }

            $name = trim(substr($declaration, 0, $colon));
            $value = trim(substr($declaration, $colon + 1));

            if (! CssCustomPropertyName::isValid($name) || $value === '') {
                $invalid = true;

                continue;
            }

            $declarations[$name] = $value;
            $valid = true;
        }

        return [$declarations, $valid && ! $invalid];
    }

    /** @return array{string[], bool} */
    private function splitDeclarations(string $body): array
    {
        $parts = [];
        $start = 0;

        [$events, $valid] = $this->scan($body);

        foreach ($events as ['offset' => $offset, 'character' => $character, 'depth' => $depth]) {
            if ($character === ';' && $depth === 0) {
                $parts[] = substr($body, $start, $offset - $start);
                $start = $offset + 1;
            }
        }

        $parts[] = substr($body, $start);

        return [array_values(array_filter(array_map('trim', $parts))), $valid];
    }

    /** @return array{int|null, bool} */
    private function topLevelColon(string $declaration): array
    {
        [$events, $valid] = $this->scan($declaration);

        foreach ($events as ['offset' => $offset, 'character' => $character, 'depth' => $depth]) {
            if ($character === ':' && $depth === 0) {
                return [$offset, $valid];
            }
        }

        return [null, $valid];
    }

    private function aliasTarget(string $value): ?string
    {
        $unquoted = $this->withoutStrings($value);
        preg_match_all('/(?<![-_a-zA-Z0-9])var\(\s*(--[^\s,;)]+)/', $unquoted, $matches);
        $targets = array_values(array_unique($matches[1]));

        return count($targets) === 1 && CssCustomPropertyName::isValid($targets[0]) ? $targets[0] : null;
    }

    private function withoutStrings(string $value): string
    {
        return preg_replace('/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'/s', '', $value) ?? $value;
    }

    /**
     * @return array{list<array{prelude: string, body: string, start: int, end: int}>, bool, int[]}
     */
    private function topLevelAtRules(string $css): array
    {
        $atRules = [];
        $start = 0;
        $block = null;
        [$events, $valid, $invalidOffsets] = $this->scan($css);

        foreach ($events as ['offset' => $offset, 'character' => $character, 'depth' => $depth]) {
            if ($character === ';' && $depth === 0 && $block === null) {
                $start = $offset + 1;
            } elseif ($character === '{' && $depth === 0 && $block === null) {
                $block = [
                    'opening' => $offset,
                    'prelude' => trim(substr($css, $start, $offset - $start)),
                    'start' => $start,
                ];
            } elseif ($character === '}' && $depth === 1 && $block !== null) {
                $end = $offset + 1;

                if (str_starts_with($block['prelude'], '@')) {
                    $atRules[] = [
                        'prelude' => $block['prelude'],
                        'body' => substr($css, $block['opening'] + 1, $offset - $block['opening'] - 1),
                        'start' => $block['start'],
                        'end' => $end,
                    ];
                }

                $start = $end;
                $block = null;
            }
        }

        return [$atRules, $valid, $invalidOffsets];
    }

    /**
     * @return array{list<array{offset: int, character: string, depth: int}>, bool, int[]}
     */
    private function scan(string $value): array
    {
        $events = [];
        $stack = [];
        $quote = null;
        $quoteStart = null;
        $commentStart = null;
        $valid = true;
        $invalidOffsets = [];
        $length = strlen($value);
        $closers = [')' => '(', ']' => '[', '}' => '{'];

        for ($offset = 0; $offset < $length; $offset++) {
            $character = $value[$offset];

            if ($commentStart !== null) {
                if ($character === '*' && ($value[$offset + 1] ?? null) === '/') {
                    $commentStart = null;
                    $offset++;
                }

                continue;
            }

            if ($quote !== null) {
                if ($character === '\\') {
                    $offset++;
                } elseif ($character === $quote) {
                    $quote = null;
                    $quoteStart = null;
                }

                continue;
            }

            if ($character === '\\') {
                $offset++;
            } elseif ($character === '/' && ($value[$offset + 1] ?? null) === '*') {
                $commentStart = $offset;
                $offset++;
            } elseif ($character === '"' || $character === "'") {
                $quote = $character;
                $quoteStart = $offset;
            } elseif (in_array($character, ['(', '[', '{'], true)) {
                $events[] = ['offset' => $offset, 'character' => $character, 'depth' => count($stack)];
                $stack[] = ['character' => $character, 'offset' => $offset];
            } elseif (isset($closers[$character])) {
                $events[] = ['offset' => $offset, 'character' => $character, 'depth' => count($stack)];
                $opening = array_pop($stack);

                if (($opening['character'] ?? null) !== $closers[$character]) {
                    $valid = false;
                    $invalidOffsets[] = $offset;

                    if ($opening !== null) {
                        $invalidOffsets[] = $opening['offset'];
                    }
                }
            } elseif ($character === ':' || $character === ';') {
                $events[] = ['offset' => $offset, 'character' => $character, 'depth' => count($stack)];
            }
        }

        if ($quoteStart !== null) {
            $invalidOffsets[] = $quoteStart;
        }

        if ($commentStart !== null) {
            $invalidOffsets[] = $commentStart;
        }

        foreach ($stack as $opening) {
            $invalidOffsets[] = $opening['offset'];
        }

        $valid = $valid && $quote === null && $commentStart === null && $stack === [];

        return [$events, $valid, array_values(array_unique($invalidOffsets))];
    }
}
