<?php

use Emaia\LaravelHotwire\Support\CssInterpolationSyntax;

it('detects Tailwind arbitrary-value underscores in raw CSS interpolation methods', function (string $declaration, string $method) {
    $violations = app(CssInterpolationSyntax::class)->invalidDeclarations(".fixture { {$declaration}; }");

    expect($violations)->toBe([[
        'method' => $method,
        'declaration' => $declaration,
    ]]);
})->with([
    'color mix' => ['box-shadow: 0 1px 2px color-mix(in_oklch, black, transparent)', 'in_oklch'],
    'linear gradient' => ['background: linear-gradient(in_srgb, red, blue)', 'in_srgb'],
    'radial gradient' => ['background: radial-gradient(in_lab, red, blue)', 'in_lab'],
    'conic gradient' => ['background: conic-gradient(in_xyz-d65, red, blue)', 'in_xyz-d65'],
    'hyphenated color space' => ['background: linear-gradient(in_srgb-linear, red, blue)', 'in_srgb-linear'],
    'display p3' => ['background: linear-gradient(in_display-p3, red, blue)', 'in_display-p3'],
    'a98 rgb' => ['background: linear-gradient(in_a98-rgb, red, blue)', 'in_a98-rgb'],
    'prophoto rgb' => ['background: linear-gradient(in_prophoto-rgb, red, blue)', 'in_prophoto-rgb'],
    'rec2020' => ['background: linear-gradient(in_rec2020, red, blue)', 'in_rec2020'],
]);

it('detects raw interpolation methods in declaration-bearing at-rules', function (string $css, string $declaration) {
    expect(app(CssInterpolationSyntax::class)->invalidDeclarations($css))->toBe([[
        'method' => 'in_oklch',
        'declaration' => $declaration,
    ]]);
})->with([
    '@theme inline' => [
        '@theme inline { --color-accent: color-mix(in_oklch, red, blue); }',
        '--color-accent: color-mix(in_oklch, red, blue)',
    ],
    '@property' => [
        '@property --accent { initial-value: color-mix(in_oklch, red, blue); }',
        'initial-value: color-mix(in_oklch, red, blue)',
    ],
]);

it('allows Tailwind interpolation underscores inside arbitrary values', function (string $declaration) {
    expect(app(CssInterpolationSyntax::class)->invalidDeclarations(".fixture { {$declaration}; }"))->toBe([]);
})->with([
    'background utility' => ['@apply hover:bg-[color-mix(in_oklch,var(--background),transparent_20%)]'],
    'shadow utility' => ['@apply shadow-[0_1px_2px_0_color-mix(in_oklch,var(--foreground),transparent_80%)]'],
]);
