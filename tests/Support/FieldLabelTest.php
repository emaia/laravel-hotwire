<?php

use Emaia\LaravelHotwire\Support\FieldLabel;

it('reserves the next available label id', function (array $claimedIds, string $expected) {
    expect(FieldLabel::uniqueId('field-label', $claimedIds))->toBe($expected);
})->with([
    'unclaimed base' => [[], 'field-label'],
    'first duplicate' => [['field-label'], 'field-label-2'],
    'several duplicates' => [['field-label', 'field-label-2', 'field-label-3'], 'field-label-4'],
]);
