<?php

it('links every registered component in the README component table', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $readme = file_get_contents(__DIR__.'/../../README.md');
    $componentTable = explode('## Controllers', explode('## Components', $readme, 2)[1], 2)[0];

    foreach (array_count_values(array_column($catalog['components'], 'docs')) as $docs => $count) {
        expect(substr_count($componentTable, "]({$docs})"))->toBe($count);
    }
});

it('links every registered controller in the README controller table', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $readme = file_get_contents(__DIR__.'/../../README.md');
    $controllerTable = explode('## Styling', explode('## Controllers', $readme, 2)[1], 2)[0];

    foreach (array_count_values(array_column($catalog['controllers'], 'docs')) as $docs => $count) {
        expect(substr_count($controllerTable, "]({$docs})"))->toBe($count);
    }
});
