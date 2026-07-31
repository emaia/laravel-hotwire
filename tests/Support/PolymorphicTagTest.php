<?php

use Emaia\LaravelHotwire\Support\PolymorphicTag;

it('normalizes allowed polymorphic tags', function () {
    expect(PolymorphicTag::normalize(' A ', ['span', 'a'], 'badge'))->toBe('a');
});

it('rejects unsupported and injectable polymorphic tags', function (string $tag) {
    expect(fn () => PolymorphicTag::normalize($tag, ['span', 'a'], 'badge'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported badge tag. Supported values: span, a.');
})->with([
    'unsupported' => 'script',
    'attributes' => 'img src=x onerror=alert(1)',
    'empty' => '   ',
]);

it('normalizes native button types', function () {
    expect(PolymorphicTag::buttonType(' SUBMIT '))->toBe('submit')
        ->and(fn () => PolymorphicTag::buttonType('invalid'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported button type. Supported values: button, submit, reset.');
});
