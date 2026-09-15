<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final readonly class CssInterpolationSyntax
{
    private const string INVALID_METHOD = <<<'REGEX'
        ~("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')(*SKIP)(*F)|\bin_(?:oklch|oklab|srgb-linear|srgb|display-p3|a98-rgb|prophoto-rgb|rec2020|lab|lch|hsl|hwb|xyz[a-z0-9-]*)\b~i
        REGEX;

    public function __construct(private CssRules $rules) {}

    /**
     * Find interpolation methods whose Tailwind space syntax leaked into raw CSS declarations.
     *
     * @return list<array{method: string, declaration: string}>
     */
    public function invalidDeclarations(string $css): array
    {
        $violations = [];
        foreach ($this->rules->parse($css, true) as $rule) {
            foreach ($this->rules->splitTopLevel($rule['declarations'], ';') as $declaration) {
                $raw = preg_replace('/\[[^\]]*\]/s', '', $declaration) ?? $declaration;

                if (preg_match(self::INVALID_METHOD, $raw, $match) !== 1) {
                    continue;
                }

                $violations[] = [
                    'method' => $match[0],
                    'declaration' => trim(preg_replace('/\s+/', ' ', $declaration) ?? $declaration),
                ];
            }
        }

        return $violations;
    }
}
