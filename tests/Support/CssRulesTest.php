<?php

use Emaia\LaravelHotwire\Support\CssRules;

it('tokenizes syntax outside strings and comments with delimiter state', function () {
    $scan = (new CssRules)->tokenize(<<<'CSS'
        a { content: "}"; /* ] */ value: fn([x]); }
        CSS);

    $syntax = array_values(array_map(
        fn (array $event): array => array_intersect_key($event, array_flip(['character', 'depth', 'blockDepth'])),
        array_filter(
            $scan['events'],
            fn (array $event): bool => str_contains('{}()[]:;', $event['character']),
        ),
    ));

    expect($scan['valid'])->toBeTrue()
        ->and($syntax)->toBe([
            ['character' => '{', 'depth' => 0, 'blockDepth' => 0],
            ['character' => ':', 'depth' => 1, 'blockDepth' => 1],
            ['character' => ';', 'depth' => 1, 'blockDepth' => 1],
            ['character' => ':', 'depth' => 1, 'blockDepth' => 1],
            ['character' => '(', 'depth' => 1, 'blockDepth' => 1],
            ['character' => '[', 'depth' => 2, 'blockDepth' => 1],
            ['character' => ']', 'depth' => 3, 'blockDepth' => 1],
            ['character' => ')', 'depth' => 2, 'blockDepth' => 1],
            ['character' => ';', 'depth' => 1, 'blockDepth' => 1],
            ['character' => '}', 'depth' => 1, 'blockDepth' => 1],
        ]);
});

it('emits sparse events instead of allocating one record per source byte', function () {
    $css = '.example { content: '.str_repeat('x', 10_000).'; color: red; }';

    expect((new CssRules)->tokenize($css)['events'])->toHaveCount(6);
});

it('reports lexical failures in encounter order', function (string $css, array $invalidOffsets) {
    $scan = (new CssRules)->tokenize($css);

    expect($scan['valid'])->toBeFalse()
        ->and($scan['invalidOffsets'])->toBe($invalidOffsets);
})->with([
    'unterminated string' => ['"unfinished', [0]],
    'open comment' => ['/* unfinished', [0]],
    'open delimiter' => ['(', [0]],
    'mismatched delimiters' => ['([)]', [2, 1, 3, 0]],
]);

it('preserves nested rule order and parent declarations around children', function () {
    $rules = (new CssRules)->parse(<<<'CSS'
        [data-slot="parent"] {
            color: red;
            & [data-slot="child"] { content: "}"; }
            background: blue;
        }
        CSS);

    expect($rules)->toBe([
        [
            'chain' => ['[data-slot="parent"]', '& [data-slot="child"]'],
            'declarations' => ' content: "}"; ',
        ],
        [
            'chain' => ['[data-slot="parent"]'],
            'declarations' => "\n    color: red;\n    background: blue;\n",
        ],
    ]);
});

it('splits only on separators outside strings and delimiters', function () {
    expect((new CssRules)->splitTopLevel('content: "a;b"; color: rgb(0; 0; 0); display: block', ';'))->toBe([
        'content: "a;b"',
        ' color: rgb(0; 0; 0)',
        ' display: block',
    ]);
});

it('keeps top-level splitting limited to functional and attribute delimiters', function () {
    $rules = new CssRules;

    expect($rules->splitTopLevel('a{b,c}, d', ','))->toBe(['a{b', 'c}', ' d'])
        ->and($rules->splitTopLevel('a{(b},c),d', ','))->toBe(['a{(b},c)', 'd']);
});

it('exposes tokenizer-backed literal helpers', function () {
    $comment = '/* hidden */';
    $css = 'before'.$comment.'after "'.$comment.'"';
    $rules = new CssRules;

    expect($rules->stripComments($css))->toBe('beforeafter "'.$comment.'"')
        ->and($rules->maskComments($css))->toBe('before'.str_repeat(' ', strlen($comment)).'after "'.$comment.'"')
        ->and($rules->withoutStrings('var(--real) "var(--fake)" \'also fake\''))->toBe('var(--real)  ');
});

it('drops comments from the parts it splits', function () {
    expect((new CssRules)->splitTopLevel('a/* x */, /* y */b', ','))->toBe(['a', ' b']);
});

it('closes an @scope prelude on the delimiter the tokenizer paired', function (string $scope, ?string $root) {
    expect((new CssRules)->scopeRoot($scope))->toBe($root);
})->with([
    'plain root' => ['@scope ([data-slot="root"])', '[data-slot="root"]'],
    'closing paren inside a string' => ['@scope ([data-label=")"] a)', '[data-label=")"] a'],
    'escaped closing paren' => ['@scope (a\\))', 'a\\)'],
    'nested parentheses' => ['@scope (:is(a, b) c)', ':is(a, b) c'],
    'ignores the to-clause' => ['@scope (a) to (b)', 'a'],
    'unbalanced prelude' => ['@scope (unfinished', null],
    'not a scope prelude' => ['@media (width > 0)', null],
]);
