<?php

use Emaia\LaravelHotwire\Support\SlotAttributes;

it('merges attributes into one interactive root while preserving complex attributes', function () {
    $html = '<button data-label="a > b" x-on:click="open = true" x-bind:[aria-label]="label">Open</button >';

    $merged = SlotAttributes::mergeIntoFirstElement($html, [
        'data-action' => 'dropdown#toggle',
        'aria-expanded' => 'false',
    ])->toHtml();

    expect($merged)
        ->toContain('type="button"')
        ->toContain('data-label="a > b"')
        ->toContain('x-on:click="open = true"')
        ->toContain('x-bind:[aria-label]="label"')
        ->toContain('data-action="dropdown#toggle"')
        ->toContain('aria-expanded="false"');
});

it('rejects invalid as-child slot roots', function (string $html) {
    expect(fn () => SlotAttributes::mergeIntoFirstElement($html, []))
        ->toThrow(InvalidArgumentException::class, 'as-child requires exactly one button or anchor root element.');
})->with([
    'empty' => '',
    'text' => 'Open',
    'multiple roots' => '<button>One</button><button>Two</button>',
    'non interactive' => '<div>Open</div>',
    'comment before root' => '<!-- trigger --><button>Open</button>',
]);
