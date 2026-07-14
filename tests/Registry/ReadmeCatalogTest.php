<?php

it('lists every registered component in the README component table', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $readme = file_get_contents(__DIR__.'/../../README.md');

    foreach ($catalog['components'] as $name => $component) {
        expect($readme)
            ->toContain("`<hw:{$name}>`")
            ->toContain($component['docs']);
    }
});

it('lists every registered controller in the README controller tables', function () {
    $catalog = require __DIR__.'/../../src/Registry/catalog.php';
    $readme = file_get_contents(__DIR__.'/../../README.md');

    foreach ($catalog['controllers'] as $identifier => $controller) {
        expect($readme)
            ->toContain("`{$identifier}`")
            ->toContain($controller['docs']);
    }
});
