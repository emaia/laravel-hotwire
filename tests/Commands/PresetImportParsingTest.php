<?php

use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetSourceResolver;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->appBase = isolateAppPaths();
    $this->cssRoot = $this->appBase.'/package-css';
    File::copyDirectory(dirname(__DIR__, 2).'/resources/css', $this->cssRoot);
    $this->app->instance(CssPresetFiles::class, new CssPresetFiles(
        File::getFacadeRoot(),
        new PresetSourceResolver(File::getFacadeRoot(), $this->cssRoot),
        CssModuleManifest::load(),
    ));
});

afterEach(function () {
    releaseIsolatedAppPaths($this->appBase);
});

dataset('preset generation commands', [
    'clone' => ['hotwire:make-preset brand --from=nova --force --no-interaction', 'css/presets/brand.css'],
    'subset' => ['hotwire:bundle-preset --preset=nova --components=button --force --no-interaction', 'css/hotwire.css'],
]);

it('generates identical artifacts for equivalent supported import syntax', function (string $replacement, string $command, string $output) {
    $this->artisan($command)->assertSuccessful();
    $expected = File::get(resource_path($output));
    $path = $this->cssRoot.'/presets/nova.css';
    File::put($path, str_replace(['@import ', '";'], ['@IMPORT/* source */', $replacement], File::get($path)));

    $this->artisan($command)->assertSuccessful();

    expect(File::get(resource_path($output)))->toBe($expected);
})->with([
    'comments' => '" /* ; */;',
    'URL suffixes' => '?v=1#theme";',
])->with('preset generation commands');

it('preserves existing artifacts when a source contains an unsupported import', function (
    string $css,
    string $diagnostic,
    string $command,
    string $output,
) {
    $this->artisan($command)->assertSuccessful();
    $expected = File::get(resource_path($output));
    // Badge is deliberately outside the subset: validation must precede selection.
    $path = $this->cssRoot.'/presets/nova/badge.css';
    File::put($path, $css);

    $this->artisan($command)->expectsOutputToContain($diagnostic)->assertFailed();

    expect(File::get(resource_path($output)))->toBe($expected);
})->with([
    'nested' => ['@media print { @import "./button.css"; }', 'malformed or misplaced @import'],
    'late' => ['[data-slot="badge"] {} @import "./button.css";', 'malformed or misplaced @import'],
    'malformed' => ['@import "./button.css"', 'malformed or misplaced @import'],
    'conditional' => ['@import "./button.css" supports(display: grid);', 'unsupported import conditions'],
    'invalid syntax' => ['@layer base ); @import "./button.css";', 'invalid CSS syntax in [presets/nova/badge.css]'],
])->with('preset generation commands');
