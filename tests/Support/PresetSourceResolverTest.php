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
    writePresetCss($this->root, 'presets/demo/forms.css', <<<'CSS'
        @import "../../tokens.css";
        [data-slot="input"] {}
        CSS);
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "../tokens.css";
        @import "./demo/forms.css";
        CSS);

    expect($this->resolver->resolve($entrypoint)->foundationImports())->toBe(['tokens.css']);
});

it('leaves bare and remote imports in visual CSS and ignores commented imports', function () {
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        /* @import "./missing.css"; */
        @import "tailwindcss";
        @import url("https://example.com/theme.css");
        [data-slot="button"] { content: "@import './also-missing.css';"; }
        CSS);

    expect($this->resolver->resolve($entrypoint)->visualCss())
        ->toContain('@import "tailwindcss";')
        ->toContain('@import url("https://example.com/theme.css");')
        ->toContain("@import './also-missing.css';");
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
