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

it('finds ids without whitespace before the attribute', function () {
    $context = new OverlayLabelContext('dialog', 'modal');
    $context->register('modal-title', 'dialog-title');

    $references = $context->referencesFor(
        new HtmlString('<h2 class="heading"id="dialog-title" data-slot="modal-title">Title</h2>'),
    );

    expect($references['title'])->toBe('dialog-title');
});

it('rejects malformed authored attributes that collide with a registered id', function () {
    $context = new OverlayLabelContext('dialog', 'modal');
    $context->register('modal-title', 'dialog-title');

    expect(fn () => $context->assertNoIdCollisions(
        new HtmlString('<span class="copy"id="dialog-title">Authored content</span>'),
    ))->toThrow(
        InvalidArgumentException::class,
        'Overlay label id [dialog-title] conflicts with another element in its content.',
    );
});

it('invalidates inspected fragments when a label registers later', function () {
    $context = new OverlayLabelContext('dialog', 'modal');
    $contents = new HtmlString('<span id="dialog-title">Authored content</span>');

    $context->assertNoIdCollisions($contents);
    $context->register('modal-title');

    expect(fn () => $context->assertNoIdCollisions($contents))->toThrow(
        InvalidArgumentException::class,
        'Overlay label id [dialog-title] conflicts with another element in its content.',
    );
});

it('ignores semantic labels inside inert templates during root validation', function () {
    $context = new OverlayLabelContext('dialog', 'modal');

    expect($context->validateRoot(new HtmlString(
        '<template><h2 data-slot="modal-title">Deferred title</h2></template>',
    )))->toBeNull();
});

it('ignores registered id collisions inside inert templates', function () {
    $context = new OverlayLabelContext('dialog', 'modal');
    $context->register('modal-title');

    expect($context->validateRoot(new HtmlString(
        '<template><div id="dialog-title"></div></template><div data-slot="modal-overlay"><h2 id="dialog-title" data-slot="modal-title">Title</h2></div>',
    )))->toBeNull();
});
