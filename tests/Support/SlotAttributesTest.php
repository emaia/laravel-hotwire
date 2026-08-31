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

it('removes actions and href when merged attributes disable an as-child anchor', function () {
    $merged = SlotAttributes::mergeIntoFirstElement(
        '<a href="/items/1" data-action="items#destroy">Delete</a>',
        ['aria-disabled' => 'true', 'data-action' => 'alert-dialog#open'],
        disableWhenMerged: true,
    )->toHtml();

    expect($merged)
        ->toContain('aria-disabled="true"')
        ->toContain('tabindex="-1"')
        ->not->toContain('href=')
        ->not->toContain('data-action=');
});

it('preserves merged disabled anchor behavior unless explicitly disabled by the caller', function () {
    $merged = SlotAttributes::mergeIntoFirstElement(
        '<a href="/items/1" data-action="items#destroy">Delete</a>',
        ['aria-disabled' => 'true', 'tabindex' => '0'],
    )->toHtml();

    expect($merged)
        ->toContain('href="/items/1"')
        ->toContain('data-action="items#destroy"')
        ->toContain('aria-disabled="true"')
        ->toContain('tabindex="0"');
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
