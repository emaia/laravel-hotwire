<?php

use Emaia\LaravelHotwire\Support\CssImports;

it('retains comment-like text inside quoted import conditions', function () {
    $imports = (new CssImports)->parse('@import "./theme.css" supports(font-family: "/* literal */") /* comment */;');

    expect($imports[0]['conditions'])->toBe('supports(font-family: "/* literal */")');
});

it('preserves original offsets and remaining content when parsing after a BOM', function () {
    $parser = new CssImports;
    $css = "\xEF\xBB\xBF".'@import "./theme.css"; body {}';
    $imports = $parser->parse($css);

    expect($imports[0]['offset'])->toBe(3)
        ->and($parser->remove($css, $imports))->toBe("\xEF\xBB\xBF".' body {}');
});

it('detects remaining import tokens independently of placement without reading strings or comments', function (string $css, bool $expected) {
    expect((new CssImports)->contains($css))->toBe($expected);
})->with([
    'nested' => ['@media print { @import "./theme.css"; }', true],
    'late' => ['.example {} @IMPORT "./theme.css";', true],
    'missing path' => ['@import;', true],
    'unclosed path' => ['@import "./theme.css', true],
    'comment' => ['/* @import "./theme.css"; */', false],
    'string' => ['.example { content: "@import"; }', false],
    'escaped at sign' => ['.\\@import {}', false],
    'longer name' => ['@import-theme;', false],
]);

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
    'comment separator' => ['@import/* note */"./theme.css";', './theme.css'],
    'semicolon inside comment' => ['@import "./theme.css" /* ; */;', './theme.css'],
]);

it('keeps import order and ignores nested, late, quoted, and commented imports', function () {
    $css = <<<'CSS'
        @charset "UTF-8";
        @layer reset, components;
        @import "./first.css" layer(base);
        @import url('./second.css') screen and (width > 40rem);
        .example { content: '@import "./quoted.css";'; }
        @import "./late.css";
        /* @import "./commented.css"; */
        CSS;

    $imports = array_map(
        fn (array $import): array => array_intersect_key($import, array_flip(['path', 'conditions'])),
        (new CssImports)->parse($css),
    );

    expect($imports)->toBe([
        ['path' => './first.css', 'conditions' => 'layer(base)'],
        ['path' => './second.css', 'conditions' => 'screen and (width > 40rem)'],
    ]);
});

it('does not expose imports after an unterminated string or comment', function (string $css) {
    expect((new CssImports)->parse($css))->toBe([]);
})->with([
    'string' => ['"unfinished @import "./ghost.css";'],
    'comment' => ['/* unfinished @import "./ghost.css";'],
]);

it('does not let an unmatched top-level closer erase preceding content', function () {
    expect((new CssImports)->parse('garbage } @import "./ghost.css";'))->toBe([]);
});

it('does not accept imports after a bare unmatched top-level closer', function () {
    expect((new CssImports)->parse('} @import "./ghost.css";'))->toBe([]);
});

it('does not accept imports after malformed allowed preludes', function (string $css) {
    expect((new CssImports)->parse($css))->toBe([]);
})->with([
    'layer with unmatched paren' => ['@layer base ); @import "./ghost.css";'],
    'charset with unmatched bracket' => ['@charset "UTF-8"]; @import "./ghost.css";'],
]);
