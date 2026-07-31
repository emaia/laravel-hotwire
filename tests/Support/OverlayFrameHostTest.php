<?php

use Emaia\LaravelHotwire\Support\OverlayFrameHost;

it('counts only matching turbo frame hosts owned by the overlay', function () {
    $html = <<<'HTML'
        <div data-modal-frame-owner="modal-shell"></div>
        <turbo-frame data-modal-frame-owner="modal-shell" id="modal"></turbo-frame>
        <turbo-frame id="other" data-modal-frame-owner="modal-shell"></turbo-frame>
        HTML;

    expect(OverlayFrameHost::count($html, 'modal', 'data-modal-frame-owner', 'modal-shell', 'modal.content'))->toBe(1);
});

it('handles quoted tag delimiters and ignores inert frame-like markup', function () {
    $html = <<<'HTML'
        <!-- <turbo-frame id="modal" data-modal-frame-owner="modal-shell"></turbo-frame> -->
        <template><turbo-frame id="modal" data-modal-frame-owner="modal-shell"></turbo-frame></template>
        <script>const example = '</scripture><turbo-frame id="modal" data-modal-frame-owner="modal-shell">';</script>
        <turbo-frame data-note="a > b" id="modal" data-modal-frame-owner="modal-shell"></turbo-frame>
        HTML;

    expect(OverlayFrameHost::count($html, 'modal', 'data-modal-frame-owner', 'modal-shell', 'modal.content'))->toBe(1);
});

it('rejects matching turbo frame ids that are not owned by the overlay', function () {
    OverlayFrameHost::count(
        '<turbo-frame id="modal"></turbo-frame>',
        'modal',
        'data-modal-frame-owner',
        'modal-shell',
        'modal.content',
    );
})->throws(InvalidArgumentException::class, 'A modal with a frame prop must render exactly one modal.content host.');
