<?php

it('marks only frame-backed roots as owners of their canonical host', function (string $component, string $frame) {
    $view = $this->blade("<x-hw::{$component} id=\"{$component}-shell\" frame=\"{$frame}\" />");
    $root = (new DOMXPath(dom((string) $view)))->query("//*[@data-slot=\"{$component}\"]")->item(0);

    expect($root->getAttribute('data-hotwire-overlay-host'))->toBe($frame)
        ->and(substr_count((string) $view, 'data-hotwire-overlay-host='))->toBe(1);

    $static = $this->blade("<x-hw::{$component} id=\"static-{$component}\" />");
    expect((string) $static)->not->toContain('data-hotwire-overlay-host');
})->with([
    ['modal', 'modal'],
    ['sheet', 'settings-panel'],
    ['drawer', 'drawer-panel'],
]);
