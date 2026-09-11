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
            :scope { color: red; }
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
            :where(:scope) { color: red; }
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
        ->and($result['referencedSlots'])->not->toContain(
            'fixture-comment',
            'fixture-string',
            'fixture-variant-string',
        );
});

it('requires package foundations exactly once in canonical order', function (Closure $mutate) {
    $this->files->put($this->entrypoint, $mutate($this->files->get($this->entrypoint)));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import package foundations once in this order: tokens.css, custom-variants.css, structural.css.'
    );
})->with([
    'missing' => fn (): Closure => fn (string $css): string => str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";'."\n",
        '',
        $css,
    ),
    'duplicate' => fn (): Closure => fn (string $css): string => str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";'."\n".
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
        $css,
    ),
    'reordered' => fn (): Closure => fn (string $css): string => str_replace(
        [
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
        ],
        [
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/custom-variants.css";',
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/tokens.css";',
        ],
        $css,
    ),
]);

it('rejects additional package stylesheets as foundations', function () {
    $extra = $this->root.'/vendor/emaia/laravel-hotwire/resources/css/extra.css';
    $this->files->put($extra, '/* extra */');
    $this->files->put($this->entrypoint, str_replace(
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";',
        '@import "../../../vendor/emaia/laravel-hotwire/resources/css/structural.css";'."\n".
            '@import "../../../vendor/emaia/laravel-hotwire/resources/css/extra.css";',
        $this->files->get($this->entrypoint),
    ));

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain(
        'Preset [constellation] must import package foundations once in this order: tokens.css, custom-variants.css, structural.css.'
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

it('keeps unrelated missing slots as errors when one slot reference is unprovable', function () {
    $surfaces = $this->root.'/resources/css/presets/constellation/surfaces.css';
    $feedback = $this->root.'/resources/css/presets/constellation/feedback.css';
    $this->files->put($surfaces, '[data-slot="fixture-panel"] { color: red; }');
    $this->files->put($feedback, '[data-slot="fixture-status" { color: red; }');

    $result = $this->validator->validate($this->entrypoint, $this->registry, $this->root.'/resources/css');

    expect($result['errors'])->toContain('Preset [constellation] is missing visual slots: fixture-action.')
        ->and($result['warnings'])->toContain('Preset [constellation] could not prove visual coverage: fixture-status.');
});

it('normalizes Windows drive and UNC paths without losing their roots', function (string $path, string $expected) {
    $canonical = new ReflectionMethod($this->validator, 'canonical');

    expect($canonical->invoke($this->validator, $path))->toBe($expected);
})->with([
    'drive' => ['C:\\App\\resources\\css\\presets\\brand.css', 'C:/App/resources/css/presets/brand.css'],
    'UNC' => ['\\\\Server\\Share\\resources\\css\\presets\\brand.css', '//Server/Share/resources/css/presets/brand.css'],
]);

function applicationPresetRegistry(): HotwireRegistry
{
    return HotwireRegistry::fromCatalog([
        'components' => [
            'fixture-panel' => [
                'class' => Card::class,
                'view' => 'fixture',
                'docs' => 'fixture.md',
                'category' => 'display',
                'styling' => ['slots' => [
                    'fixture-panel' => 'visual',
                    'fixture-shell' => 'structural',
                ]],
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
