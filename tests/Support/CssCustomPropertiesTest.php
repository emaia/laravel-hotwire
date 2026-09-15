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
        'scopes' => [
            'root' => ['--status', '--font-family', '--escaped', '--status\:emphasis'],
            'default' => [],
            'light' => [],
            'dark' => ['--status', '--status-foreground'],
            'unscoped' => [],
        ],
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
        'scopes' => [
            'root' => ['--status'],
            'default' => [],
            'light' => [],
            'dark' => [],
            'unscoped' => [],
        ],
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
        'scopes' => [
            'root' => [],
            'default' => [],
            'light' => [],
            'dark' => [],
            'unscoped' => ['--conditional'],
        ],
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
        'scopes' => [
            'root' => ['--label'],
            'default' => [],
            'light' => [],
            'dark' => [],
            'unscoped' => [],
        ],
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
        'scopes' => [
            'root' => ['--status'],
            'default' => [],
            'light' => [],
            'dark' => [],
            'unscoped' => [],
        ],
        'aliases' => ['--color-status' => '--status'],
        'violations' => ['invalid CSS syntax'],
    ]);
});

it('distinguishes the unthemed fallback from explicit light and dark scopes', function () {
    $css = <<<'CSS'
        :where(:root:not([data-theme="dark"])) {
            --surface: white;
            --default-only: silver;
        }

        [data-theme="light"] {
            --surface: white;
            --light-only: gray;
        }

        [data-theme="dark"] {
            --surface: black;
        }
        CSS;

    expect((new CssCustomProperties)->inspectPresetBase($css)['scopes'])->toBe([
        'root' => [],
        'default' => ['--surface', '--default-only'],
        'light' => ['--surface', '--light-only'],
        'dark' => ['--surface'],
        'unscoped' => [],
    ]);
});

it('records declarations outside supported top-level token selectors', function () {
    $css = <<<'CSS'
        html { --html-token: red; }

        :root, body { --mixed-token: blue; }

        @layer base {
            :root { --nested-token: green; }
        }

        :root {
            @media (width > 0px) { --conditional-token: purple; }
        }

        @theme static { --theme-token: orange; }
        CSS;

    expect((new CssCustomProperties)->inspectStylesheet($css)['scopes'])->toBe([
        'root' => ['--mixed-token'],
        'default' => [],
        'light' => [],
        'dark' => [],
        'unscoped' => ['--theme-token', '--html-token', '--mixed-token', '--nested-token', '--conditional-token'],
    ]);
});

it('classifies supported scope selector quoting without relying on unmatched capture groups', function (string $selector, string $scope) {
    expect((new CssCustomProperties)->scopeFor($selector))->toBe($scope);
})->with([
    'root' => [':root', 'root'],
    'default' => [':where(:root:not([data-theme="dark"]))', 'default'],
    'double quoted light' => ['[data-theme="light"]', 'light'],
    'single quoted dark' => ["[data-theme='dark']", 'dark'],
    'unquoted light' => ['[data-theme=light]', 'light'],
]);

it('owns the canonical selector vocabulary', function (string $scope, string $selector) {
    expect((new CssCustomProperties)->selectorFor($scope))->toBe($selector);
})->with([
    'root' => ['root', ':root'],
    'default' => ['default', ':where(:root:not([data-theme="dark"]))'],
    'light' => ['light', '[data-theme="light"]'],
    'dark' => ['dark', '[data-theme="dark"]'],
]);

it('classifies extra conditions case-insensitively and gives dark precedence', function () {
    $properties = new CssCustomProperties;

    expect($properties->scopeFor('[data-theme="LIGHT"]:not([data-flat])', allowConditions: true))->toBe('light')
        ->and($properties->scopeFor('[data-theme="light"][data-theme="dark"]', allowConditions: true))->toBe('dark');
});

it('ignores theme-like text inside quoted selector values', function () {
    expect((new CssCustomProperties)->scopeFor(
        '[data-theme="light"][data-example=\'[data-theme="dark"]\']',
        allowConditions: true,
    ))->toBe('light');
});
