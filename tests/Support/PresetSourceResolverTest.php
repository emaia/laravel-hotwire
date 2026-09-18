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

it('rejects duplicate shared foundation imports', function () {
    writePresetCss($this->root, 'tokens.css', ':root {}');
    writePresetCss($this->root, 'custom-variants.css', '@custom-variant demo {}');
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "../tokens.css";
        @import "../custom-variants.css";
        @import "../tokens.css";
        @import "./demo/forms.css";
        CSS);

    $this->resolver->resolve($entrypoint);
})->throws(PresetSourceException::class, 'Preset [demo] imports shared foundation [tokens.css] more than once.');

it('requires the public foundation facade to preserve its canonical composition', function (string $foundation) {
    foreach (['tokens.css', 'custom-variants.css', 'structural.css'] as $file) {
        writePresetCss($this->root, $file, "/* {$file} */");
    }

    writePresetCss($this->root, 'foundation.css', $foundation);
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "../foundation.css";');

    $this->resolver->resolve($entrypoint);
})->with([
    'old direct order' => <<<'CSS'
        @import "./custom-variants.css";
        @import "./tokens.css";
        @import "./structural.css";
        CSS,
    'visual declarations' => <<<'CSS'
        @import "./tokens.css";
        @import "./custom-variants.css";
        @import "./structural.css";
        :root { --unexpected: true; }
        CSS,
])->throws(
    PresetSourceException::class,
    'foundation.css must import tokens.css, custom-variants.css, and structural.css in canonical order.',
);

it('requires every internal stylesheet imported by the foundation facade to exist', function () {
    writePresetCss($this->root, 'tokens.css', '/* tokens */');
    writePresetCss($this->root, 'custom-variants.css', '/* variants */');
    writePresetCss($this->root, 'foundation.css', <<<'CSS'
        @import "./tokens.css";
        @import "./custom-variants.css";
        @import "./structural.css";
        CSS);
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "../foundation.css";');

    $this->resolver->resolve($entrypoint);
})->throws(PresetSourceException::class, 'foundation.css cannot resolve canonical import [./structural.css].');

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

// --- Shared import parsing and flattening boundaries ---

it('resolves supported import spellings consistently for clones and selections', function (string $import) {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] { color: blue; }');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', $import);

    foreach ([null, ['presets/demo/forms.css']] as $selection) {
        $source = $this->resolver->resolve($entrypoint, $selection);

        expect($source->visualStylesheetPaths())->toBe(['presets/demo/forms.css'])
            ->and($source->visualCss())->toBe('[data-slot="input"] { color: blue; }');
    }
})->with([
    'comment separator' => '@import/* note */"./demo/forms.css";',
    'compact' => '@import"./demo/forms.css";',
    'case insensitive' => '@IMPORT URL("./demo/forms.css");',
    'comments around url argument' => '@import url(/* path */ "./demo/forms.css" /* end */);',
    'comment instead of conditions' => '@import "./demo/forms.css" /* unconditional */;',
    'semicolon inside comment' => '@import "./demo/forms.css" /* ; */;',
    'UTF-8 BOM' => "\xEF\xBB\xBF".'@import "./demo/forms.css";',
]);

it('rejects malformed and misplaced imports before flattening any source', function (string $css, bool $nested) {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $path = $nested ? 'presets/demo/aggregate.css' : 'presets/demo.css';
    writePresetCss($this->root, $path, str_replace('__PATH__', $nested ? './forms.css' : './demo/forms.css', $css));
    $entrypoint = $nested
        ? writePresetCss($this->root, 'presets/demo.css', '@import "./demo/aggregate.css";')
        : $this->root.'/'.$path;

    foreach ([null, []] as $selection) {
        expect(fn () => $this->resolver->resolve($entrypoint, $selection))
            ->toThrow(PresetSourceException::class, "Preset [demo] contains a malformed or misplaced @import in [{$path}].");
    }
})->with([
    'nested media' => '@media (min-width: 1px) { @import "__PATH__"; }',
    'nested style' => '[data-slot="button"] { @import "__PATH__"; }',
    'after rule' => '[data-slot="button"] { color: red; } @import "__PATH__";',
    'after namespace' => '@namespace svg url(http://www.w3.org/2000/svg); @import "__PATH__";',
    'missing semicolon' => '@import "__PATH__"',
    'missing path' => '@import;',
    'unquoted path' => '@import __PATH__;',
    'unclosed string' => '@import "__PATH__;',
    'unmatched delimiter' => '@layer base ); @import "__PATH__";',
    'after valid import' => '@import "__PATH__"; @import;',
])->with([false, true]);

it('rejects CSS escapes in import paths rather than treating them as filesystem separators', function () {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import ".\\demo\\forms.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] import paths cannot contain CSS escapes.');
});

it('rejects preludes that flattening would move behind imported styles', function (string $css) {
    writePresetCss($this->root, 'presets/demo/forms.css', '@layer first { [data-slot="input"] {} }');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', $css);

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] cannot flatten CSS prelude rules before imports in [presets/demo.css].');
})->with([
    'layer order' => '@layer second, first; @import "./demo/forms.css";',
    'charset' => '@charset "UTF-8"; @import "./demo/forms.css";',
]);

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

it('rejects stylesheets outside the preset private source directory', function () {
    writePresetCss($this->root, 'presets/other.css', '[data-slot="other"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./other.css";');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(
            PresetSourceException::class,
            'Preset [demo] cannot import stylesheet [presets/other.css] outside [presets/demo/].',
        );
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

it('compares Windows drive and UNC paths case-insensitively', function (string $root, string $source, string $sibling) {
    $resolver = new PresetSourceResolver($this->files, $root);
    $insideCssRoot = new ReflectionMethod($resolver, 'insideCssRoot');
    $isVisual = new ReflectionMethod($resolver, 'isVisual');
    $relative = new ReflectionMethod($resolver, 'relative');

    expect($insideCssRoot->invoke($resolver, $source))->toBeTrue()
        ->and($isVisual->invoke($resolver, $source, 'nova'))->toBeTrue()
        ->and($isVisual->invoke($resolver, dirname($source), 'nova'))->toBeFalse()
        ->and($isVisual->invoke($resolver, $sibling, 'nova'))->toBeFalse()
        ->and($relative->invoke($resolver, $source))->toBe('presets/nova/button.css');
})->with([
    'drive' => [
        'C:\\Package\\Resources\\CSS',
        'c:/package/resources/css/presets/nova/button.css',
        'C:/Package/Resources/CSS/presets/bloom/button.css',
    ],
    'UNC' => [
        '\\\\Server\\Share\\CSS',
        '//server/share/css/presets/nova/button.css',
        '//SERVER/Share/CSS/presets/bloom/button.css',
    ],
]);

it('rejects conditions on local imports instead of changing their semantics', function (string $conditions) {
    writePresetCss($this->root, 'presets/demo/forms.css', '[data-slot="input"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', '@import "./demo/forms.css" '.$conditions.';');

    expect(fn () => $this->resolver->resolve($entrypoint))
        ->toThrow(PresetSourceException::class, 'Preset [demo] local import [./demo/forms.css] uses unsupported import conditions.');
})->with(['layer(forms)', 'layer', 'supports(display: grid)', 'screen and (width > 40rem)', '/* note */ print']);

it('delegates optional source selection to the resolved preset', function () {
    writePresetCss($this->root, 'presets/demo/modal.css', '[data-slot="modal"] {}');
    writePresetCss($this->root, 'presets/demo/button.css', '[data-slot="button"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/modal.css";
        @import "./demo/button.css";
        CSS);

    $source = $this->resolver->resolve($entrypoint, ['presets/demo/button.css']);

    expect($source->visualStylesheetPaths())->toBe(['presets/demo/button.css']);
});

it('preserves entrypoint validation for optional source selection', function () {
    writePresetCss($this->root, 'presets/demo/modal.css', '[data-slot="modal"] {}');
    $entrypoint = writePresetCss($this->root, 'presets/demo.css', <<<'CSS'
        @import "./demo/modal.css";
        [data-slot="entrypoint"] {}
        CSS);

    expect(fn () => $this->resolver->resolve($entrypoint, ['presets/demo/modal.css']))
        ->toThrow(PresetSourceException::class, 'Selective preset [demo] entrypoint must contain only imports.');
});
