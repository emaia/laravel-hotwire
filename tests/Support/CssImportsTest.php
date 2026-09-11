<?php

use Emaia\LaravelHotwire\Support\CssImports;

it('parses compact imports and preserves opposite quotes in paths', function (string $css, string $path) {
    expect((new CssImports)->parse($css)[0])->toMatchArray([
        'path' => $path,
        'conditions' => '',
    ]);
})->with([
    'compact double quoted' => ['@import"./theme.css";', './theme.css'],
    'compact single quoted' => ["@import'./theme.css';", './theme.css'],
    'apostrophe in double quoted path' => ['@import "./designer\'s-theme.css";', "./designer's-theme.css"],
    'quote in single quoted path' => ['@import \'./say"hello.css\';', './say"hello.css'],
]);
