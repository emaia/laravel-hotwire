<?php

declare(strict_types=1);

use Emaia\LaravelHotwire\Support\CssModuleManifest;
use Emaia\LaravelHotwire\Support\CssPresetFiles;
use Emaia\LaravelHotwire\Support\PresetContrastManifest;
use Emaia\LaravelHotwire\Support\PresetSourceResolver;
use Illuminate\Filesystem\Filesystem;

require __DIR__.'/../vendor/autoload.php';

try {
    $manifestPath = $argv[1] ?? __DIR__.'/../src/Registry/styles.php';
    $cssRoot = $argv[2] ?? __DIR__.'/../resources/css';
    $manifestPath = realpath($manifestPath) ?: $manifestPath;
    $cssRoot = realpath($cssRoot) ?: $cssRoot;
    $definition = require $manifestPath;

    if (! is_array($definition)) {
        throw new RuntimeException("CSS manifest [{$manifestPath}] must return an array.");
    }

    $files = new Filesystem;
    $manifest = CssModuleManifest::fromArray($definition);
    $presets = new CssPresetFiles($files, new PresetSourceResolver($files, $cssRoot), $manifest);
    $payload = ['presets' => (new PresetContrastManifest($manifest, $presets))->toArray()];

    fwrite(STDOUT, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(1);
}
