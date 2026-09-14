<?php

use Emaia\LaravelHotwire\Support\CssCustomProperties;

it('extracts preset properties and inline theme aliases without regex block assumptions', function () {
    $css = <<<'CSS'
        /* @theme inline { --ignored: var(--ignored); } */
        :root {
            --status: oklch(0.5 0.2 140);
            --font-family: "Fixture; Sans";
            --escaped: fixture\;value;
            --status\:emphasis: strong;
        }

        [data-theme="dark"] {
            --status: oklch(0.8 0.1 140);
            --status-foreground: oklch(0.1 0 0);
        }

        @theme inline {
            --color-status: var(--status);
            --radius-action: calc(var(--radius) * 1.2);
        }

        @theme   inline {
            --color-status: color-mix(in oklab, var(--status-strong), transparent 10%);
        }
        CSS;

    expect((new CssCustomProperties)->inspectPresetBase($css))->toBe([
        'properties' => ['--status', '--font-family', '--escaped', '--status\:emphasis', '--status-foreground'],
        'aliases' => [
            '--color-status' => '--status-strong',
            '--radius-action' => '--radius',
        ],
        'violations' => [],
    ]);
});

it('reports unsupported selectors, declarations, theme blocks, and ambiguous aliases', function () {
    $css = <<<'CSS'
        @theme static { --color-status: var(--status); }
        @theme inline {
            color: red;
            --color-notice: var(--notice, var(--fallback));
            --color-fake: myvar(--status);
        }
        :root { --status: green; }
        [data-slot="button"] { --button-radius: 1rem; }
        [data-theme="dark"] { accent-color: red; }
        CSS;

    expect((new CssCustomProperties)->inspectPresetBase($css))->toBe([
        'properties' => ['--status'],
        'aliases' => ['--color-notice' => null, '--color-fake' => null],
        'violations' => ['@theme', '@theme inline', '[data-slot="button"]', '[data-theme="dark"]'],
    ]);
});

it('extracts foundation properties without applying preset base scope restrictions', function () {
    $css = <<<'CSS'
        @media (prefers-color-scheme: dark) {
            :root { --conditional: black; }
        }

        @layer base {
            body { color: var(--conditional); }
        }
        CSS;

    expect((new CssCustomProperties)->inspectStylesheet($css))->toBe([
        'properties' => ['--conditional'],
        'aliases' => [],
        'valid' => true,
    ]);
});

it('reports syntax and declaration validity without applying preset scope policy', function (string $css) {
    expect((new CssCustomProperties)->inspectStylesheet($css)['valid'])->toBeFalse();
})->with([
    'unterminated quote' => <<<'CSS'
        :root { --before: red; --broken: "oops; }
        :root { --after: blue; }
        CSS,
    'non-custom declaration in theme' => <<<'CSS'
        @theme inline {
            --color-status: var(--status);
            color: red;
        }
        CSS,
    'unterminated comment' => <<<'CSS'
        :root { --before: red; }
        /* unfinished
        CSS,
]);

it('audits scopes and nested theme tokens without rewriting quoted strings', function () {
    $css = <<<'CSS'
        :root { --label: "@theme"; }
        [data-theme="d a r k"] { --invalid-theme: black; }
        CSS;

    expect((new CssCustomProperties)->inspectPresetBase($css))->toBe([
        'properties' => ['--label'],
        'aliases' => [],
        'violations' => ['[data-theme="d a r k"]'],
    ]);
});

it('reports malformed delimiters without losing later top-level theme blocks', function () {
    $css = <<<'CSS'
        );
        @theme inline { --color-status: var(--status); }
        :root { --status: green; }
        CSS;

    expect((new CssCustomProperties)->inspectPresetBase($css))->toBe([
        'properties' => ['--status'],
        'aliases' => ['--color-status' => '--status'],
        'violations' => ['invalid CSS syntax'],
    ]);
});
