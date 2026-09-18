<?php

use Emaia\LaravelHotwire\Support\CssRules;

it('strips only the leading UTF-8 byte order mark', function () {
    $css = '[data-slot="input"] { content: "'."\xEF\xBB\xBF".'"; }';

    expect((new CssRules)->stripBom("\xEF\xBB\xBF".$css))->toBe($css)
        ->and((new CssRules)->stripBom($css))->toBe($css);
});

it('tokenizes syntax outside strings and comments with delimiter state', function () {
    $events = [];
    $scan = (new CssRules)->scan(<<<'CSS'
        a { content: "}"; /* ] */ value: fn([x]); }
        CSS, function (array $event) use (&$events): void {
        $events[] = $event;
    });

    $syntax = array_values(array_map(
        fn (array $event): array => array_intersect_key($event, array_flip(['character', 'depth', 'blockDepth'])),
        array_filter(
            $events,
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
    $events = 0;

    (new CssRules)->scan($css, function () use (&$events): void {
        $events++;
    });

    expect($events)->toBe(6);
});

it('streams dense token events without retaining an event collection', function () {
    $count = 0;
    $scan = (new CssRules)->scan(str_repeat('a:b;', 10_000), function () use (&$count): void {
        $count++;
    });

    expect($count)->toBe(20_000)
        ->and($scan)->not->toHaveKey('events');
});

it('reports lexical failure offsets', function (string $css, array $invalidOffsets) {
    $scan = (new CssRules)->scan($css);

    expect($scan['valid'])->toBeFalse()
        ->and($scan['invalidOffsets'])->toBe($invalidOffsets);
})->with([
    'unterminated string' => ['"unfinished', [0]],
    'open comment' => ['/* unfinished', [0]],
    'open delimiter' => ['(', [0]],
]);

it('orders encountered errors before opening delimiters left unresolved at the end', function () {
    expect((new CssRules)->scan('([)]')['invalidOffsets'])->toBe([2, 0]);
});

it('preserves nested rule order and parent declarations around children', function () {
    $css = <<<'CSS'
        [data-slot="parent"] {
            color: red;
            & [data-slot="child"] { content: "}"; }
            background: blue;
        }
        CSS;
    $newline = str_contains($css, "\r\n") ? "\r\n" : "\n";
    $rules = (new CssRules)->parse($css);

    expect($rules)->toBe([
        [
            'chain' => ['[data-slot="parent"]', '& [data-slot="child"]'],
            'declarations' => ' content: "}"; ',
        ],
        [
            'chain' => ['[data-slot="parent"]'],
            'declarations' => "{$newline}    color: red;{$newline}    background: blue;{$newline}",
        ],
    ]);
});

it('drops a malformed rule without losing later valid rules', function () {
    expect((new CssRules)->parse(<<<'CSS'
        [data-slot="invalid"] { ); }
        [data-slot="valid"] { color: red; }
        CSS))->toBe([
        [
            'chain' => ['[data-slot="valid"]'],
            'declarations' => ' color: red; ',
        ],
    ]);
});

it('reports structural validity alongside recovered rules', function () {
    $analysis = (new CssRules)->analyze(<<<'CSS'
        [data-slot="invalid"] { ); }
        [data-slot="valid"] { color: red; }
        CSS);

    expect($analysis['valid'])->toBeFalse()
        ->and($analysis['rules'])->toHaveCount(1);
});

it('drops descendants enclosed by a malformed rule', function () {
    expect((new CssRules)->parse(<<<'CSS'
        .invalid] {
            [data-slot="ghost"] { color: red; }
        }
        [data-slot="valid"] { color: green; }
        CSS))->toBe([
        [
            'chain' => ['[data-slot="valid"]'],
            'declarations' => ' color: green; ',
        ],
    ]);
});

it('retains valid nested rules after a malformed sibling', function () {
    expect((new CssRules)->parse(<<<'CSS'
        .parent {
            .invalid { ); }
            [data-slot="valid"] { color: green; }
        }
        CSS))->toBe([
        [
            'chain' => ['.parent', '[data-slot="valid"]'],
            'declarations' => ' color: green; ',
        ],
    ]);
});

it('retains valid nested rules after a malformed ancestor declaration', function () {
    expect((new CssRules)->parse(<<<'CSS'
        .parent {
            invalid: );
            [data-slot="valid"] { color: green; }
        }
        CSS))->toBe([
        [
            'chain' => ['.parent', '[data-slot="valid"]'],
            'declarations' => ' color: green; ',
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

it('keeps CSS hexadecimal escape terminators out of separator events', function () {
    expect((new CssRules)->splitTopLevel('.a\\2c .b .c', ' '))->toBe(['.a\\2c .b', '.c']);
});

it('rejects malformed string and escape syntax', function (string $css) {
    expect((new CssRules)->scan($css)['valid'])->toBeFalse();
})->with([
    'raw newline in string' => ["a { content: \"first\nsecond\"; }"],
    'trailing escape' => ['a\\'],
    'escaped newline outside string' => ["a\\\nb"],
]);

it('accepts an escaped newline inside a string', function () {
    expect((new CssRules)->scan("a { content: \"first\\\nsecond\"; }")['valid'])->toBeTrue();
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
