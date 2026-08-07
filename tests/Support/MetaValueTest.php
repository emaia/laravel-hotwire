<?php

use Emaia\LaravelHotwire\Support\MetaValue;

it('accepts a value from the allowlist, normalized', function () {
    expect(MetaValue::enum('  MORPH ', ['replace', 'morph'], 'meta.refresh'))->toBe('morph');
});

it('names the supported values when rejecting one', function () {
    expect(fn () => MetaValue::enum('morf', ['replace', 'morph'], 'meta.refresh method'))
        ->toThrow(InvalidArgumentException::class, 'Unsupported meta.refresh method value. Supported values: replace, morph.');
});

it('reads a flag from the bare attribute, a bound bool and the string spelling alike', function () {
    expect(MetaValue::boolean(true, 'meta.prefetch'))->toBe('true')
        ->and(MetaValue::boolean(false, 'meta.prefetch'))->toBe('false')
        ->and(MetaValue::boolean('true', 'meta.prefetch'))->toBe('true')
        ->and(MetaValue::boolean('false', 'meta.prefetch'))->toBe('false')
        ->and(MetaValue::boolean(' FALSE ', 'meta.prefetch'))->toBe('false');
});

it('rejects a flag that is neither true nor false', function () {
    expect(fn () => MetaValue::boolean('maybe', 'meta.prefetch'))
        ->toThrow(InvalidArgumentException::class, 'Supported values: true, false.');
});
