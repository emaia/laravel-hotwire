<?php

use Emaia\LaravelHotwire\Components\Button;
use Emaia\LaravelHotwire\Components\Card;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;
use Emaia\LaravelHotwire\Support\ApplicationPresetValidator;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->root = sys_get_temp_dir().'/hotwire-application-preset-'.uniqid();
    $this->files->copyDirectory(
        __DIR__.'/../Fixtures/css/application-preset',
        $this->root,
    );

    foreach (['tokens.css', 'custom-variants.css', 'structural.css'] as $foundation) {
        $path = $this->root.'/vendor/emaia/laravel-hotwire/resources/css/'.$foundation;
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, "/* {$foundation} */\n");
    }

    $this->files->put(
        $this->root.'/vendor/emaia/laravel-hotwire/resources/css/foundation.css',
        "@import \"./tokens.css\";\n@import \"./custom-variants.css\";\n@import \"./structural.css\";\n",
    );

    $this->entrypoint = $this->root.'/resources/css/presets/constellation.css';
    $this->validator = app(ApplicationPresetValidator::class);
    $this->registry = applicationPresetRegistry();
});

afterEach(function () {
    $this->files->deleteDirectory($this->root);
});

it('accepts a complete synthetic preset without requiring official selector vocabulary', function () {
    $result = $this->validator->validate(
        $this->entrypoint,
        $this->registry,
        $this->root.'/resources/css',
    );

    expect($result['errors'])->toBe([])
        ->and($result['warnings'])->toBe([])
        ->and($result['styledSlots'])->toEqualCanonicalizing([
            'fixture-panel',
            'fixture-action',
            'fixture-status',
        ]);
});

it('resolves local import URLs with query strings and fragments', function () {
    $this->files->put($this->entrypoint, str_replace('.css";', '.css?v=1#theme";', $this->files->get($this->entrypoint)));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])->and($result['warnings'])->toBe([]);
});

it('reports missing visual slots and undeclared slot references as proven errors', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, str_replace(
        'fixture-status',
        'fixture-stauts',
        $this->files->get($path),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])
        ->toContain('Preset [constellation] is missing visual slots: fixture-status.')
        ->toContain('Preset [constellation] references undeclared slots: fixture-stauts.');
});

it('reports each missing registry-owned preset property', function (string $property) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, preg_replace(
        '/^\s*'.preg_quote($property, '/').':[^;]+;\s*$/m',
        '',
        $this->files->get($path),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        "Preset [constellation] is missing required preset property [{$property}] on [data-slot=\"fixture-panel\"]."
    );
})->with(['--fixture-panel-inset', '--fixture-panel-edge']);

it('does not credit required properties declared on a descendant', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, str_replace(
        '[data-slot="fixture-panel"] {',
        '[data-slot="fixture-panel"] [data-slot="fixture-action"] {',
        $this->files->get($path),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-inset] on [data-slot="fixture-panel"].')
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-edge] on [data-slot="fixture-panel"].');
});

it('does not credit required properties declared on a non-element subject', function (string $selector) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, str_replace(
        '[data-slot="fixture-panel"] {',
        "{$selector} {",
        $this->files->get($path),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-inset] on [data-slot="fixture-panel"].')
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-edge] on [data-slot="fixture-panel"].');
})->with([
    'pseudo-element' => '[data-slot="fixture-panel"]::before',
    'legacy pseudo-element' => '[data-slot="fixture-panel"]:before',
    'negated slot' => '[data-slot="fixture-action"]:not([data-slot="fixture-panel"])',
]);

it('does not count a pseudo-element rule as visual coverage for its slot', function (string $css) {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, $css);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-status.')
        ->and($result['styledSlots'])->not->toContain('fixture-status');
})->with([
    'direct subject' => '[data-slot="fixture-status"]::before { color: red; }',
    'nested subject' => '[data-slot="fixture-status"] { &::before { color: red; } }',
    'scope subject' => '@scope ([data-slot="fixture-status"]) { :scope::before { color: red; } }',
]);

it('credits required properties on a functional subject selector', function (string $selector) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, str_replace(
        '[data-slot="fixture-panel"] {',
        "{$selector} {",
        $this->files->get($path),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([]);
})->with([
    'functional compound' => ':where([data-slot="fixture-panel"]).compact',
    'functional descendant subject' => '[data-theme] :where([data-slot="fixture-panel"])',
]);

it('does not credit required properties from a chained functional ancestor', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        :where([data-slot="fixture-panel"]) :where([data-slot="fixture-action"]) {
            color: red;
            --fixture-panel-inset: 0rem;
            --fixture-panel-edge: 0px;
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-inset] on [data-slot="fixture-panel"].')
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-edge] on [data-slot="fixture-panel"].');
});

it('credits required properties in a nested refinement of the slot', function (string $nestedSelector) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $css = (string) preg_replace(
        '/\[data-slot="fixture-panel"\]\s*\{\s*--fixture-panel-inset:\s*0rem;\s*--fixture-panel-edge:\s*0px;\s*\}/',
        <<<CSS
            [data-slot="fixture-panel"] {
                {$nestedSelector} {
                    --fixture-panel-inset: 0rem;
                    --fixture-panel-edge: 0px;
                }
            }
            CSS,
        $this->files->get($path),
    );
    $this->files->put($path, $css);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($css)->toContain($nestedSelector)
        ->and($result['errors'])->toBe([]);
})->with([
    'direct refinement' => '&[data-size="compact"]',
    'reversed context' => '.theme &',
    'functional nesting subject' => ':where(&).compact',
    'selector list retaining the parent' => '&, & > .child',
]);

it('does not credit required properties on a nested child or scoped pseudo-element', function (string $css) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, $css."\n[data-slot=\"fixture-action\"] { color: red; }");

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-inset] on [data-slot="fixture-panel"].')
        ->toContain('Preset [constellation] is missing required preset property [--fixture-panel-edge] on [data-slot="fixture-panel"].');
})->with([
    'nested child' => <<<'CSS'
        [data-slot="fixture-panel"] {
            color: red;
            & > .child { --fixture-panel-inset: 0rem; --fixture-panel-edge: 0px; }
        }
        CSS,
    'scope pseudo-element' => <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            :scope { color: red; }
            :scope::before { --fixture-panel-inset: 0rem; --fixture-panel-edge: 0px; }
        }
        CSS,
]);

it('warns when required preset properties cannot be proven', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        [data-slot="fixture-panel"] { color: red; }
        [data-slot="fixture-panel" { --fixture-panel-inset: 0rem; --fixture-panel-edge: 0px; }
        [data-slot="fixture-action"] { color: red; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and(implode(' ', $result['warnings']))->toContain('CSS analysis is incomplete')
        ->and($result['warnings'])
        ->toContain('Preset [constellation] could not prove required preset properties on [data-slot="fixture-panel"]: --fixture-panel-edge, --fixture-panel-inset.');
});

it('warns when required preset properties are inside a malformed scoped rule', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            :scope { color: red; }
            :scope { --fixture-panel-inset: 0rem; --fixture-panel-edge: 0px; ); }
        }
        [data-slot="fixture-action"] { color: red; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'])
        ->toContain('Preset [constellation] could not prove required preset properties on [data-slot="fixture-panel"]: --fixture-panel-edge, --fixture-panel-inset.');
});

it('does not count empty rules as visual coverage', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, <<<'CSS'
        @layer components {
            [data-slot="fixture-status"] {}
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-status.');
});

it('counts a scope root styled through the scope pseudo-class as visual coverage', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            :scope {
                color: red;
                --fixture-panel-inset: 0rem;
                --fixture-panel-edge: 0px;
            }
        }

        [data-slot="fixture-action"] { color: red; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['styledSlots'])->toContain('fixture-panel');
});

it('counts functional scope subjects without crediting scoped descendants', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            :where(:scope) {
                color: red;
                --fixture-panel-inset: 0rem;
                --fixture-panel-edge: 0px;
            }
            :scope:is(.compact, .spacious) [data-slot="fixture-action"] { color: red; }
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['styledSlots'])->toContain('fixture-panel', 'fixture-action');
});

it('does not count a scope root when only its descendant has declarations', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            :scope:is(.compact, .spacious) [data-slot="fixture-action"] { color: red; }
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-panel.')
        ->and($result['styledSlots'])->toContain('fixture-action')
        ->not->toContain('fixture-panel');
});

it('does not count negated or textual scope mentions as root coverage', function (string $selector) {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<CSS
        @scope ([data-slot="fixture-panel"]) {
            {$selector} { color: red; }
        }

        [data-slot="fixture-action"] { color: red; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-panel.')
        ->and($result['styledSlots'])->not->toContain('fixture-panel');
})->with([
    'negated' => ':not(:scope)',
    'nested negation' => ':where(:not(:scope))',
    'relational argument' => ':has(:scope)',
    'attribute string' => '[data-label=":scope"]',
    'functional descendant' => ':where(:scope [data-slot="fixture-action"])',
    'functional child' => ':is(:scope > [data-slot="fixture-action"])',
    'functional column' => ':where(:scope||[data-slot="fixture-action"])',
    'functional pseudo-element' => ':where(:scope::before)',
]);

it('credits only the nearest root of nested scopes', function () {
    $path = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $this->files->put($path, <<<'CSS'
        @scope ([data-slot="fixture-panel"]) {
            @scope ([data-slot="fixture-action"]) {
                :scope { color: red; }
            }
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-panel.')
        ->and($result['styledSlots'])->toContain('fixture-action')
        ->not->toContain('fixture-panel');
});

it('ignores slot-like text in comments and declaration strings', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, $this->files->get($path).<<<'CSS'

        /* [data-slot="fixture-comment"] */
        [data-slot="fixture-status"] {
            content: '[data-slot="fixture-string"] data-[slot=fixture-variant-string]';
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['referencedSlots'])->not->toContain('fixture-comment')
        ->and($result['referencedSlots'])->not->toContain('fixture-string')
        ->and($result['referencedSlots'])->not->toContain('fixture-variant-string');
});

it('reports Tailwind arbitrary-value underscores in raw CSS with an actionable declaration', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->append($path, <<<'CSS'

        [data-slot="fixture-status"] {
            background: linear-gradient(in_oklch, red, blue);
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([
        'Preset [constellation] uses invalid interpolation method [in_oklch] in raw CSS declaration [background: linear-gradient(in_oklch, red, blue)] in [feedback.css]. Write [in oklch]; Tailwind underscores represent spaces only inside arbitrary values ([...]).',
    ]);
});

it('allows interpolation underscores inside application arbitrary values', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->append($path, <<<'CSS'

        [data-slot="fixture-status"] {
            @apply shadow-[0_1px_2px_0_color-mix(in_oklch,var(--foreground),transparent_80%)];
        }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([]);
});

it('requires the package foundation facade exactly once', function (Closure $mutate) {
    $this->files->put($this->entrypoint, $mutate($this->files->get($this->entrypoint)));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import package foundation [foundation.css] exactly once.'
    );
})->with([
    'missing' => fn (): Closure => fn (string $css): string => str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";'."\n",
        '',
        $css,
    ),
    'duplicate' => fn (): Closure => fn (string $css): string => str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";'."\n".
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";',
        $css,
    ),
]);

it('applies the foundation facade contract without legacy topology detection', function () {
    $this->files->put($this->entrypoint, str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";',
        implode("\n", [
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";',
        ]),
        $this->files->get($this->entrypoint),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import package foundation [foundation.css] exactly once.'
    );
});

it('validates the package-owned composition behind the foundation facade', function () {
    $foundation = $this->root.'/vendor/emaia/laravel-hotwire/resources/css/foundation.css';
    $this->files->put($foundation, <<<'CSS'
        @import "./structural.css";
        @import "./tokens.css";
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'foundation.css must import tokens.css, custom-variants.css, and structural.css in canonical order.'
    );
});

it('rejects additional package stylesheets as foundations', function () {
    $extra = $this->root.'/vendor/emaia/laravel-hotwire/resources/css/extra.css';
    $this->files->put($extra, '/* extra */');
    $this->files->put($this->entrypoint, str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/foundation.css";'."\n".
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/extra.css";',
        $this->files->get($this->entrypoint),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import package foundation [foundation.css] exactly once.'
    );
});

it('requires foundations before application visual imports', function () {
    $css = $this->files->get($this->entrypoint);
    $visual = '@import "./constellation/surfaces.css";'."\n";
    $this->files->put($this->entrypoint, $visual.str_replace($visual, '', $css));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import shared foundations before visual stylesheets.'
    );
});

it('rejects malformed or misplaced imports instead of treating them as visual css', function () {
    $this->files->append($this->entrypoint, "\nbody {} @import \"./constellation/missing.css\";");

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] contains a malformed or misplaced @import in [constellation.css].'
    );
});

it('reports invalid entrypoint syntax instead of blaming later imports', function () {
    $this->files->put(
        $this->entrypoint,
        ".typo { color: red; )\n".$this->files->get($this->entrypoint),
    );

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([
        'Preset [constellation] contains invalid CSS syntax in [constellation.css].',
    ])->and($result['warnings'])->toBe([]);
});

it('rejects escaped import paths instead of interpreting css escapes as separators', function () {
    $css = str_replace(
        './constellation/surfaces.css',
        './constellation/\\73 urfaces.css',
        $this->files->get($this->entrypoint),
    );
    $this->files->put($this->entrypoint, $css);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] import paths cannot contain CSS escapes.'
    );
});

it('reports missing imports and import cycles without reading outside the application css root', function () {
    $surfaces = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $feedback = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($surfaces, '@import "./feedback.css";');
    $this->files->put($feedback, '@import "./surfaces.css";');

    $cycle = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($cycle['errors'][0])->toContain('CSS import cycle in preset [constellation]');

    $this->files->put($feedback, '@import "./missing.css";');
    $missing = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($missing['errors'][0])->toContain('cannot resolve local import [./missing.css]');
});

it('rejects imports that traverse outside the application css root', function () {
    $outside = $this->root.'/private.css';
    $this->files->put($outside, '[data-slot="fixture-status"] { color: red; }');
    $this->files->put($this->entrypoint, str_replace(
        '@import "./constellation/feedback.css";',
        '@import "../../../private.css";',
        $this->files->get($this->entrypoint),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] local import [../../../private.css] leaves the application CSS directory.'
    );
});

it('resolves the css root before comparing entrypoints and imports', function () {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('Symbolic-link path semantics are covered on Unix-like systems.');
    }

    $link = $this->root.'-current';
    symlink($this->root, $link);

    try {
        $result = $this->validator->validate(
            realpath($this->entrypoint),
            $this->registry,
            $link.'/resources/css',
        );

        expect($result['errors'])->toBe([]);
    } finally {
        @unlink($link);
    }
});

it('downgrades unprovable coverage to a warning when CSS parsing is incomplete', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, '[data-slot="fixture-status" { color: red; }');

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete')
        ->and($result['warnings'][1])->toContain('could not prove visual coverage: fixture-status');
});

it('does not accept declarations from a rule with mismatched delimiters as visual coverage', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, '[data-slot="fixture-status"] { ); }');

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete')
        ->and($result['warnings'][1])->toContain('could not prove visual coverage: fixture-status');
});

it('does not accept visual coverage enclosed by a malformed ancestor', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, '.invalid] { [data-slot="fixture-status"] { color: red; } }');

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete')
        ->and($result['warnings'][1])->toContain('could not prove visual coverage: fixture-status');
});

it('reports undeclared Tailwind slot variants from discarded rules', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, <<<'CSS'
        .card { @apply data-[slot=ghost]:hidden; ); }
        [data-slot="fixture-status"] { color: red; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] references undeclared slots: ghost.')
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete');
});

it('does not promote incomplete slot syntax to an undeclared reference', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->append($path, "\n[data-slot=\"unclosed\" { color: red; }");

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete')
        ->and($result['referencedSlots'])->not->toContain('unclosed');
});

it('ignores slot-like strings when their rule is discarded', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->append($path, <<<'CSS'

        .invalid { content: '[data-slot="static"] data-[slot=variant]'; ); }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'][0])->toContain('CSS analysis is incomplete')
        ->and($result['referencedSlots'])->not->toContain('static')
        ->and($result['referencedSlots'])->not->toContain('variant');
});

it('reports invalid syntax when every slot mention remains accounted for', function () {
    $path = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($path, <<<'CSS'
        .invalid { color: red; ); }
        [data-slot="fixture-status"] { color: green; }
        CSS);

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toBe([])
        ->and($result['warnings'])->toBe([
            'Preset [constellation] CSS analysis is incomplete because the stylesheet contains invalid syntax.',
        ]);
});

it('keeps unrelated missing slots as errors when one slot reference is unprovable', function () {
    $surfaces = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $feedback = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($surfaces, '[data-slot="fixture-panel"] { color: red; }');
    $this->files->put($feedback, '[data-slot="fixture-status" { color: red; }');

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-action.')
        ->and($result['warnings'])->toContain('Preset [constellation] could not prove visual coverage: fixture-status.');
});

function applicationPresetRegistry(): HotwireRegistry
{
    return HotwireRegistry::fromCatalog([
        'components' => [
            'fixture-panel' => [
                'class' => Card::class,
                'view' => 'fixture',
                'docs' => 'fixture.md',
                'category' => 'display',
                'styling' => [
                    'slots' => [
                        'fixture-panel' => 'visual',
                        'fixture-shell' => 'structural',
                    ],
                    'preset_properties' => [
                        'fixture-panel' => [
                            '--fixture-panel-inset' => '0rem',
                            '--fixture-panel-edge' => '0px',
                        ],
                    ],
                ],
            ],
            'fixture-action' => [
                'class' => Button::class,
                'view' => 'fixture',
                'docs' => 'fixture.md',
                'category' => 'display',
                'styling' => ['slots' => ['fixture-action' => 'visual']],
            ],
        ],
        'controllers' => [
            'fixture-status' => [
                'source' => 'resources/js/controllers/fixture_status_controller.js',
                'docs' => 'fixture.md',
                'category' => 'feedback',
                'styling' => ['slots' => ['fixture-status' => 'visual']],
            ],
        ],
    ], __DIR__);
}
