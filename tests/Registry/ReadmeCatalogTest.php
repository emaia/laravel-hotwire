<?php

it('keeps component and controller tables alphabetized', function () {
    $readme = file_get_contents(__DIR__.'/../../README.md');
    $tables = [
        'components' => explode('## Controllers', explode('## Components', $readme, 2)[1], 2)[0],
        'controllers' => explode('## Styling', explode('## Controllers', $readme, 2)[1], 2)[0],
    ];

    foreach ($tables as $name => $table) {
        preg_match_all('/\[([^]]+)\]\(docs\/(?:components|controllers)\/[^)]+\)/', $table, $matches);
        $sorted = $matches[1];
        usort($sorted, strcasecmp(...));

        expect($matches[1])->toBe(array_values($sorted), "README {$name} table is not alphabetized.");
    }
});

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
