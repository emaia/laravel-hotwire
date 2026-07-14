<?php

it('keeps package component views independent of the configured public prefix', function () {
    $offenders = [];

    foreach (glob(__DIR__.'/../../resources/views/component-views/*.blade.php') as $path) {
        $contents = file_get_contents($path);

        if (str_contains($contents, '<hw:') || str_contains($contents, '</hw:')) {
            $offenders[] = basename($path);
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps public documentation examples on the short configurable tag syntax', function () {
    $offenders = [];
    $root = realpath(__DIR__.'/../../docs');
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($files as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'md') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        if (str_contains($contents, '<x-hw::') || str_contains($contents, '</x-hw::')) {
            $offenders[] = str_replace($root.'/', '', $file->getPathname());
        }
    }

    expect($offenders)->toBe([]);
});
