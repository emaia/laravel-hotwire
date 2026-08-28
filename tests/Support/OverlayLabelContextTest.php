<?php

use Emaia\LaravelHotwire\Support\OverlayLabelContext;
use Illuminate\Support\HtmlString;

it('finds registered ids written with equivalent HTML entity encodings', function (string $id, string $encoded) {
    $context = new OverlayLabelContext('dialog', 'modal');
    $context->register('modal-title', $id);

    expect($context->referencesFor(new HtmlString("<h2 id=\"{$encoded}\">Title</h2>"))['title'])->toBe($id);
})->with([
    'numeric quote entity' => ['a"b', 'a&#34;b'],
    'numeric ampersand entity' => ['a&b', 'a&#38;b'],
]);

it('skips reserved and registered ids when generating label ids', function () {
    $context = new OverlayLabelContext('dialog', 'modal', ['dialog-title']);

    expect($context->register('modal-title'))->toBe('dialog-title-2')
        ->and($context->register('modal-title', 'custom-title'))->toBe('custom-title')
        ->and($context->register('modal-title'))->toBe('dialog-title-3');
});

it('rejects explicit label ids already owned by the overlay', function () {
    $context = new OverlayLabelContext('dialog', 'modal', ['frame-title']);

    expect(fn () => $context->register('modal-title', 'frame-title'))
        ->toThrow(InvalidArgumentException::class, 'Overlay label id [frame-title] is already in use.');
});
