<?php

use Emaia\LaravelHotwire\Support\CssPath;

it('normalizes CSS paths lexically without losing their roots', function (string $path, string $expected) {
    expect(CssPath::normalize($path))->toBe($expected);
})->with([
    'relative' => ['presets/./nova/../nova.css', 'presets/nova.css'],
    'absolute' => ['/app/resources//css/../css/app.css', '/app/resources/css/app.css'],
    'drive' => ['C:\\App\\resources\\css\\presets\\brand.css', 'C:/App/resources/css/presets/brand.css'],
    'UNC' => ['\\\\Server\\Share\\resources\\css\\presets\\brand.css', '//Server/Share/resources/css/presets/brand.css'],
]);

it('compares drive and UNC paths case-insensitively without changing POSIX semantics', function () {
    expect(CssPath::comparable('C:/App/Preset.css'))->toBe('c:/app/preset.css')
        ->and(CssPath::comparable('//Server/Share/Preset.css'))->toBe('//server/share/preset.css')
        ->and(CssPath::comparable('/App/Preset.css'))->toBe('/App/Preset.css');
});

it('recognizes roots and descendants without accepting sibling prefixes', function (string $root, string $path, bool $expected) {
    expect(CssPath::contains($root, $path))->toBe($expected);
})->with([
    'root' => ['/app/resources/css', '/app/resources/css', true],
    'descendant' => ['/app/resources/css', '/app/resources/css/presets/nova.css', true],
    'sibling prefix' => ['/app/resources/css', '/app/resources/css-private/nova.css', false],
    'drive case' => ['C:/App/Resources/CSS', 'c:/app/resources/css/presets/nova.css', true],
    'UNC case' => ['//Server/Share/CSS', '//server/share/css/presets/nova.css', true],
]);
