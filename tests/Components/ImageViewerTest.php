<?php

use Emaia\LaravelHotwire\Components\ImageViewer;
use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('enhances existing image links without replacing their progressive fallback', function () {
    $view = $this->blade('<x-hw::image-viewer id="photos" label="Product photos"><a href="/original.jpg"><img src="/thumb.jpg" data-original="/original.jpg" alt="Detail"></a></x-hw::image-viewer>');

    $view->assertSee('id="photos"', false)
        ->assertSee('data-controller="image-viewer"', false)
        ->assertSee('data-image-viewer-target="source"', false)
        ->assertSee('href="/original.jpg"', false)
        ->assertSee('src="/thumb.jpg"', false)
        ->assertSee('aria-label="Product photos"', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('data-image-viewer-target="viewport"', false)
        ->assertDontSee('data-image-viewer-target="download"', false);

    expect((new DOMXPath(dom((string) $view)))->query('//*[@data-image-viewer-target="overlay" and @hidden and @inert]')->count())->toBe(1);
});

it('exposes opt-in download and loop while protecting internal controller configuration', function () {
    $this->blade('<x-hw::image-viewer download loop data-controller="analytics" data-image-viewer-loop-value="false" data-analytics-name="photos" />')
        ->assertSee('image-viewer analytics', false)
        ->assertSee('data-image-viewer-loop-value="true"', false)
        ->assertSee('data-analytics-name="photos"', false)
        ->assertSee('data-image-viewer-target="download"', false)
        ->assertSee('data-turbo="false"', false);
});

it('escapes viewer labels and supports translated controls', function () {
    $this->blade('<x-hw::image-viewer :label="$label" :labels="$labels" />', [
        'label' => '<script>unsafe</script>',
        'labels' => ['close' => 'Fechar', 'error' => 'Falha ao carregar'],
    ])->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)
        ->assertSee('aria-label="Fechar"', false)
        ->assertSee('Falha ao carregar')
        ->assertDontSee('<script>unsafe</script>', false);
});

it('registers the viewer and its slots without adding an external viewer dependency', function () {
    $registry = HotwireRegistry::make();
    expect($registry->component('image-viewer')?->class)->toBe(ImageViewer::class);
    expect($registry->controller('image-viewer')?->npm)->toBe([]);
});

it('separates the leading counter from the trailing toolbar controls', function () {
    $view = $this->blade('<x-hw::image-viewer download />');
    $xpath = new DOMXPath(dom((string) $view));

    expect($xpath->query('//*[@data-slot="image-viewer-toolbar"]/*[1][@data-image-viewer-target="counter"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-actions"]/button')->count())->toBe(6)
        ->and($xpath->query('//*[@data-slot="image-viewer-actions"]//*[@data-image-viewer-target="zoomSelect"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-actions"]/*[@data-image-viewer-target="download"]')->count())->toBe(1);
});

it('wraps native zoom selection with an unfocusable decorative chevron', function () {
    $view = $this->blade('<x-hw::image-viewer motion="none" />');
    $xpath = new DOMXPath(dom((string) $view));

    expect($xpath->query('//*[@data-slot="image-viewer-zoom-control"]/select')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-zoom-control"]/svg[@aria-hidden="true" and @focusable="false"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-image-viewer-target="overlay" and @data-motion="none"]')->count())->toBe(1);
});

it('offers fit and standard zoom percentages including natural size', function () {
    $view = $this->blade('<x-hw::image-viewer :labels="$labels" />', ['labels' => ['zoom' => 'Nível de zoom']]);
    $xpath = new DOMXPath(dom((string) $view));
    $select = $xpath->query('//*[@data-slot="image-viewer-zoom" and @aria-label="Nível de zoom"]');

    expect($select->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-zoom"]/option[@value="fit"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-zoom"]/option[@value="1" and text()="100%"]')->count())->toBe(1)
        ->and($xpath->query('//*[@data-slot="image-viewer-zoom"]/option[@value="4" and text()="400%"]')->count())->toBe(1);
});
