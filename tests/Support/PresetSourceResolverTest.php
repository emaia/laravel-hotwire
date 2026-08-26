<?php

use Emaia\LaravelHotwire\Support\PresetSourceException;
use Emaia\LaravelHotwire\Support\PresetSourceResolver;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->files = new Filesystem;
    $this->root = sys_get_temp_dir().'/hotwire-preset-source-'.uniqid();
    $this->files->ensureDirectoryExists($this->root.'/presets/demo');
    $this->resolver = new PresetSourceResolver($this->files, $this->root);
});

afterEach(function () {
    $this->files->deleteDirectory($this->root);
});

function writePresetCss(string $root, string $path, string $contents): string
{
    $file = $root.'/'.$path;
    (new Filesystem)->ensureDirectoryExists(dirname($file));
    file_put_contents($file, $contents);

    return $file;
}

it('resolves visual stylesheets depth first in CSS import order', function () {
    writePresetCss($this->root, 'tokens.css', ':root { --color: red; }');
    writePresetCss($this->root, 'presets/demo/shared.css', '[data-slot="shared"] { color: red; }');
    writePresetCss($this->root, 'presets/demo/forms.css', <<<'CSS'
        @import "./shared.css";
        [data-slot="input"] { color: blue; }
        CSS);
    writePresetCss($this->root, 'presets/demo/actions.css', '[data-slot="button"] { color: green; }');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "../tokens.css";
        @import "./demo/forms.css";
        @import url("./demo/actions.css");
        [data-slot="entry"] { color: black; }
        CSS);

    $source = $this->resolver->resolve($entrypoint);

    expect($source->name)->toBe('demo')
        ->and($source->foundationImports())->toBe(['tokens.css'])
        ->and($source->visualStylesheets())->toBe([
            '[data-slot="shared"] { color: red; }',
            '[data-slot="input"] { color: blue; }',
            '[data-slot="button"] { color: green; }',
            '[data-slot="entry"] { color: black; }',
        ])
        ->and($source->visualCss())
        ->toContain('[data-slot="shared"]')
        ->not->toContain('@import');
});

it('deduplicates shared foundation imports at first inclusion', function () {
    writePresetCss($this->root, 'tokens.css', ':root {}');
    writePresetCss($this->root, 'custom-variants.css', '@custom-variant demo {}');
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "../tokens.css";
        @import "../custom-variants.css";
        @import "../tokens.css";
        @import "./demo/forms.css";
        CSS);

    expect($this->resolver->resolve($entrypoint)->foundationImports())->toBe([
        'tokens.css',
        'custom-variants.css',
    ]);
});

it('rejects imports that cannot be preserved when visual sources are flattened', function (string $import) {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', "@import {$import};");

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] supports only local CSS imports.');
})->with([
    'bare' => '"tailwindcss"',
    'remote' => 'url("https://example.com/theme.css")',
]);

it('ignores imports inside comments and strings', function () {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        /* @import "tailwindcss"; */
        [data-slot="button"] { content: "@import './also-missing.css';"; }
        CSS);

    expect($this->resolver->resolve($entrypoint)->visualCss())
        ->toContain("@import './also-missing.css';");
});

it('requires shared foundations before visual sources', function () {
    writePresetCss($this->root, 'tokens.css', ':root {}');
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/forms.css";
        @import "../tokens.css";
        CSS);

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] must import shared foundations before visual sources.');
});

it('rejects foundation imports from private visual sources', function () {
    writePresetCss($this->root, 'tokens.css', ':root {}');
    writePresetCss($this->root, 'presets/demo/forms.css', <<<'CSS'
        @import "../../tokens.css";
        [data-slot="input"] {}
        CSS);
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/forms.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] visual source [presets/demo/forms.css] cannot import shared foundations.');
});

it('fails when a local import is missing', function () {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/missing.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] cannot resolve local import [./demo/missing.css] from [presets/demo.css].');
});

it('reports the complete CSS import cycle', function () {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/forms.css";');
    writePresetCss($this->root, 'presets/demo/forms.css', '@import "../demo.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'CSS import cycle in preset [demo]: presets/demo.css -> presets/demo/forms.css -> presets/demo.css.');
});

it('rejects a visual stylesheet included through two branches', function () {
    writePresetCss($this->root, 'presets/demo/shared.css', '[data-slot="shared"] {}');
    writePresetCss($this->root, 'presets/demo/forms.css', '@import "./shared.css";');
    writePresetCss($this->root, 'presets/demo/actions.css', '@import "./shared.css";');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/forms.css";
        @import "./demo/actions.css";
        CSS);

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] includes visual stylesheet [presets/demo/shared.css] more than once.');
});

it('rejects imports that leave the package CSS directory', function () {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "../../private.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] local import [../../private.css] from [presets/demo.css] leaves the package CSS directory.');
});

it('rejects imports that escape through a symlink', function () {
    $outside = sys_get_temp_dir().'/hotwire-preset-outside-'.uniqid().'.css';
    file_put_contents($outside, '[data-slot="private"] {}');
    symlink($outside, $this->root.'/presets/demo/external.css');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/external.css";');

    try {
        expect(fn () => $this->resolver->resolve($entrypoint))
            ->toThrow(PresetSourceException::class, 'Preset [demo] local import [./demo/external.css] from [presets/demo.css] leaves the package CSS directory.');
    } finally {
        @unlink($outside);
    }
});

it('normalizes Windows-style separators for roots and entrypoints', function () {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/forms.css";');
    $resolver = new PresetSourceResolver($this->files, str_replace('/', '\\', $this->root));

    expect($resolver->resolve(str_replace('/', '\\', $entrypoint))->visualCss())
        ->toContain('[data-slot="input"]');
});

it('compares Windows drive and UNC paths case-insensitively', function (string $root, string $source) {
    $resolver = new PresetSourceResolver($this->files, $root);
    $insideCssRoot = new ReflectionMethod($resolver, 'insideCssRoot');
    $isVisual = new ReflectionMethod($resolver, 'isVisual');
    $relative = new ReflectionMethod($resolver, 'relative');

    expect($insideCssRoot->invoke($resolver, $source))->toBeTrue()
        ->and($isVisual->invoke($resolver, $source))->toBeTrue()
        ->and($relative->invoke($resolver, $source))->toBe('presets/nova/button.css');
})->with([
    'drive' => ['C:\\Package\\Resources\\CSS', 'c:/package/resources/css/presets/nova/button.css'],
    'UNC' => ['\\\\Server\\Share\\CSS', '//server/share/css/presets/nova/button.css'],
]);

it('rejects conditions on local imports instead of changing their semantics', function () {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/forms.css" layer(forms);');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] local import [./demo/forms.css] uses unsupported import conditions.');
});

it('filters visual sources without changing their canonical import order', function () {
    writePresetCss($this->root, 'tokens.css', ':root {}');
    writePresetCss($this->root, 'presets/demo/modal.css', '[data-slot="modal"] {}');
    writePresetCss($this->root, 'presets/demo/button.css', '[data-slot="button"] {}');
    writePresetCss($this->root, 'presets/demo/carousel.css', '[data-slot="carousel"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "../tokens.css";
        @import "./demo/modal.css";
        @import "./demo/button.css";
        @import "./demo/carousel.css";
        CSS);

    $source = $this->resolver->resolve($entrypoint, [
        'presets/demo/modal.css',
        'presets/demo/button.css',
    ]);

    expect($source->foundationImports())->toBe(['tokens.css'])
        ->and($source->visualStylesheets())->toBe([
            '[data-slot="modal"] {}',
            '[data-slot="button"] {}',
        ])
        ->and($source->visualCss())->not->toContain('carousel');
});

it('rejects visual declarations in an entrypoint used for selective resolution', function () {
    writePresetCss($this->root, 'presets/demo/modal.css', '[data-slot="modal"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/modal.css";
        [data-slot="entrypoint"] {}
        CSS);

    expect(fn () => $this->resolver->resolve($entrypoint, ['presets/demo/modal.css']))
        ->toThrow(PresetSourceException::class, 'Selective preset [demo] entrypoint must contain only imports.');
});

it('rejects selected sources outside canonical import order', function () {
    writePresetCss($this->root, 'presets/demo/a.css', '[data-slot="a"] {}');
    writePresetCss($this->root, 'presets/demo/b.css', '[data-slot="b"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/a.css";
        @import "./demo/b.css";
        CSS);

    expect(fn () => $this->resolver->resolve($entrypoint, [
        'presets/demo/b.css',
        'presets/demo/a.css',
    ]))->toThrow(
        PresetSourceException::class,
        'Selected visual sources for preset [demo] do not follow canonical import order.',
    );
});
