<?php

use Symfony\Component\Yaml\Yaml;

it('checks PHP formatting with project dependencies and read-only permissions', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/fix-php-code-style-issues.yml');
    $steps = $workflow['jobs']['php-code-styling']['steps'];
    $commands = implode("\n", array_column($steps, 'run'));
    $actions = array_column($steps, 'uses');

    expect($workflow['permissions']['contents'])->toBe('read')
        ->and($commands)->toContain('vendor/bin/pint --test')->not->toContain('composer global')
        ->and(array_filter($actions, fn (string $action): bool => str_starts_with($action, 'ramsey/composer-install@')))->not->toBeEmpty()
        ->and(array_filter($actions, fn (string $action): bool => str_starts_with($action, 'stefanzweifel/git-auto-commit-action@')))->toBe([]);
});

it('provides mbstring before installing and running the project formatter', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/fix-php-code-style-issues.yml');
    $steps = $workflow['jobs']['php-code-styling']['steps'];
    $setupIndex = array_key_first(array_filter($steps, fn (array $step): bool => str_starts_with($step['uses'] ?? '', 'shivammathur/setup-php@')));
    $installIndex = array_key_first(array_filter($steps, fn (array $step): bool => str_starts_with($step['uses'] ?? '', 'ramsey/composer-install@')));
    $checkIndex = array_key_first(array_filter($steps, fn (array $step): bool => str_contains($step['run'] ?? '', 'vendor/bin/pint --test')));
    $extensions = explode(',', str_replace(' ', '', $steps[$setupIndex]['with']['extensions'] ?? ''));

    expect($extensions)->toContain('mbstring')
        ->and($setupIndex)->toBeLessThan($installIndex)
        ->and($installIndex)->toBeLessThan($checkIndex);
});

it('checks PHP style on pull requests and configuration or dependency changes', function () {
    $workflow = Yaml::parseFile(__DIR__.'/../../.github/workflows/fix-php-code-style-issues.yml');

    foreach (['push', 'pull_request'] as $event) {
        expect($workflow['on'][$event]['paths'] ?? [])->toContain(
            '**.php',
            'composer.json',
            'composer.lock',
            'pint.json',
            'pint.json.dist',
            '.github/workflows/fix-php-code-style-issues.yml',
        );
    }
});
