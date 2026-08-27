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
