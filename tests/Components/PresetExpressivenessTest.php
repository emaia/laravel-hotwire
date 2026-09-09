<?php

use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;

it('renders contrast personalities over the same semantic component tree', function () {
    View::addNamespace('preset-expressiveness', __DIR__.'/../Fixtures/views/preset-expressiveness');
    view()->share('errors', new ViewErrorBag);

    $personalities = ['vega', 'mira', 'sera', 'luma'];
    $html = view('preset-expressiveness::show', compact('personalities'))->render();
    $xpath = new DOMXPath(dom($html));
    $previews = $xpath->query('//*[@data-preset-fixture]');

    expect($previews)->toHaveCount(count($personalities));

    $reference = null;

    foreach ($previews as $index => $preview) {
        expect($preview->getAttribute('data-preset-fixture'))->toBe($personalities[$index]);
        expect($xpath->query('.//*[@data-slot="button"][@disabled]', $preview))->toHaveCount(1)
            ->and($xpath->query('.//*[@data-slot="button"][@aria-invalid="true"]', $preview))->toHaveCount(1)
            ->and($xpath->query('.//*[@data-slot="modal-trigger"]', $preview))->toHaveCount(1)
            ->and($xpath->query('.//template[@data-toaster-target="template"]/*[@data-toaster-card and @data-slot="toast"]', $preview))->toHaveCount(1);

        $signature = [];

        foreach ($xpath->query('.//*[@data-slot]', $preview) as $element) {
            $signature[] = implode(':', array_filter([
                $element->tagName,
                $element->getAttribute('data-slot'),
                $element->getAttribute('data-variant'),
                $element->getAttribute('data-size'),
                $element->getAttribute('data-state'),
            ], static fn (string $value): bool => $value !== ''));
        }

        $reference ??= $signature;

        expect($signature)->toBe($reference);
    }

    expect($reference)
        ->toContain('button:button:default:default')
        ->toContain('div:card:sm')
        ->toContain('input:input')
        ->toContain('span:select-wrapper')
        ->toContain('div:alert:destructive')
        ->toContain('div:alert-title')
        ->toContain('div:alert-description')
        ->toContain('div:alert-action')
        ->toContain('div:modal-overlay:closed')
        ->toContain('div:modal-positioner:md')
        ->toContain('div:toast')
        ->toContain('div:toast-content')
        ->toContain('div:toast-body')
        ->toContain('div:toast-title')
        ->toContain('div:toast-description')
        ->toContain('button:toast-close');
});
