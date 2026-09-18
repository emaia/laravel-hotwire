<?php

use Illuminate\Support\Facades\File;

it('documents both official presets and their typography boundary', function () {
    $presets = File::get(__DIR__.'/../../docs/presets.md');
    $theming = File::get(__DIR__.'/../../docs/theming.md');

    expect($presets)
        ->toContain('Nova')
        ->toContain('Bloom')
        ->toContain('--preset=bloom')
        ->toContain('--from=bloom')
        ->and($theming)
        ->toContain('Preset-owned tokens');
});

it('documents the tokens a preset owns and how an application still overrides them', function () {
    $theming = File::get(__DIR__.'/../../docs/theming.md');

    expect($theming)
        ->toContain('Preset-owned tokens')
        ->toContain('--radius-control')
        ->toContain('keeps the same name and role')
        ->and(File::get(__DIR__.'/../../docs/presets.md'))
        ->toContain('preset-owned-tokens');
});

it('keeps preset authoring separate from component markup and controller wiring', function () {
    $theming = File::get(__DIR__.'/../../docs/theming.md');
    $registry = File::get(__DIR__.'/../../docs/registry.md');

    expect($theming)
        ->toMatch('/Presets keep the component\'s Blade markup, behavior and accessibility\s+contract/')
        ->and($registry)
        ->toContain('## Slots and controller targets')
        ->toContain('identifier-independent anatomy')
        ->toContain('controller wiring')
        ->toContain('not interchangeable');
});

it('documents the lexical boundary of preset axis diagnostics', function () {
    $registry = File::get(__DIR__.'/../../docs/registry.md');

    expect($registry)
        ->toContain('literal equality and valueless attributes')
        ->toContain('after the slot selector')
        ->toContain('nested rule that names no slot of its own')
        ->toContain('operator selectors')
        ->toContain('relational variants')
        ->toContain('parser coverage')
        ->toContain('does not prove semantic coverage');
});

it('documents how application-owned presets are maintained across package upgrades', function () {
    $presets = File::get(__DIR__.'/../../docs/presets.md');

    expect($presets)
        ->toContain('## Maintain an application preset')
        ->toContain('does not merge')
        ->toMatch('/new visual\s+slots/')
        ->toContain('copied preset-base changes')
        ->toContain('single live `foundation.css` import')
        ->toContain('light and dark themes')
        ->toContain('right-to-left')
        ->toMatch('/reduced\s+motion/')
        ->toContain('forced colors')
        ->toContain('preset-expressiveness.md#executable-contrast-fixture')
        ->toContain('hotwire:check --preset=brand')
        ->toContain('npm run build')
        ->toContain('Static validation cannot prove')
        ->toContain('Generated selective bundles');
});

it('keeps visual preset ownership separate from component template ownership', function () {
    $presets = File::get(__DIR__.'/../../docs/presets.md');

    expect($presets)
        ->toContain('does not require application PHP')
        ->toMatch('/does not require\s+publishing package views/')
        ->toMatch('/application-owned fork of markup\s+and behavior/')
        ->toMatch('/targets, lifecycle and\s+accessibility/');
});

it('documents the official preset token metadata contract without name inference', function () {
    $registry = File::get(__DIR__.'/../../docs/registry.md');
    $presets = File::get(__DIR__.'/../../docs/presets.md');
    $theming = File::get(__DIR__.'/../../docs/theming.md');

    expect($registry)
        ->toContain('`properties`', '`aliases`', '`contrast_pairs`')
        ->toContain('`global`', '`themed`')
        ->toContain('mutually exclusive')
        ->toContain('must not also appear')
        ->toContain('unthemed default')
        ->toContain('optional override')
        ->toContain('any subset')
        ->toContain('inherited classification')
        ->toMatch('/CSS inheritance\s+does not satisfy/')
        ->toContain('full CSS custom-property names')
        ->toContain('does not infer')
        ->toContain('exactly one distinct `var(--...)` target')
        ->and($presets)
        ->toContain('registered additional property')
        ->toContain('all ordered preset-base sources')
        ->toContain('combined declarations of all base sources')
        ->not->toContain('validation evaluates their combined cascade order')
        ->and($theming)
        ->toContain('registered contrast pairs')
        ->toContain('discovers every official preset');
});
