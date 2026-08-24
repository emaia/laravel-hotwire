<?php

use Emaia\LaravelHotwire\Support\ProgressTracks;

it('detects a direct Progress track', function () {
    expect(ProgressTracks::declaresTrack('<div data-slot="progress-track"></div>'))->toBeTrue();
});

it('ignores track signatures in comments and quoted attributes', function (string $html) {
    expect(ProgressTracks::declaresTrack($html))->toBeFalse();
})->with([
    'comment' => '<!-- <div data-slot="progress-track"></div> -->',
    'quoted attribute' => '<div title=\'<div data-slot="progress-track"></div>\'></div>',
]);

it('ignores tracks owned by a nested Progress root', function () {
    $html = '<div data-slot="progress"><div data-slot="progress-track"></div></div>';

    expect(ProgressTracks::declaresTrack($html))->toBeFalse();
});

it('returns false when no Progress track is present', function () {
    expect(ProgressTracks::declaresTrack('<div>Content</div>'))->toBeFalse();
});
