<?php

use Emaia\LaravelHotwire\Registry\HotwireRegistry;

it('renders a disclosure navigation landmark with linked triggers and a shared viewport', function (string $prefix) {
    $markup = <<<'BLADE'
        <TAGnavigation-menu id="primary" aria-label="Primary" orientation="vertical"
            :hover="false" :close-delay="240" data-controller="analytics"
            data-navigation-menu-hover-value="true">
            <TAGnavigation-menu.list>
                <TAGnavigation-menu.item>
                    <TAGnavigation-menu.trigger value="products" data-action="analytics#track">Products</TAGnavigation-menu.trigger>
                </TAGnavigation-menu.item>
                <TAGnavigation-menu.item>
                    <TAGnavigation-menu.link href="/about" current frame="main">About</TAGnavigation-menu.link>
                </TAGnavigation-menu.item>
                <TAGnavigation-menu.item>
                    <TAGnavigation-menu.trigger value="company" :chevron="false" disabled>Company</TAGnavigation-menu.trigger>
                </TAGnavigation-menu.item>
            </TAGnavigation-menu.list>
            <TAGnavigation-menu.viewport>
                <TAGnavigation-menu.content value="products">Featured products</TAGnavigation-menu.content>
                <TAGnavigation-menu.content value="company">About us</TAGnavigation-menu.content>
            </TAGnavigation-menu.viewport>
        </TAGnavigation-menu>
        BLADE;

    $html = (string) $this->blade(str_replace('TAG', $prefix, $markup));
    $document = new DOMDocument;
    $document->loadHTML($html, LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    expect($xpath->evaluate('count(//nav[@id="primary"][@aria-label="Primary"][@data-orientation="vertical"][@data-anchor="trigger"])'))->toBe(1.0)
        ->and($xpath->evaluate('string(//nav/@data-controller)'))->toBe('navigation-menu analytics')
        ->and($xpath->evaluate('string(//nav/@data-navigation-menu-hover-value)'))->toBe('false')
        ->and($xpath->evaluate('string(//nav/@data-navigation-menu-close-delay-value)'))->toBe('240')
        ->and($xpath->evaluate('count(//nav/ul/li)'))->toBe(3.0)
        ->and($xpath->evaluate('count(//li/button[@id="primary-trigger-products"][@type="button"][@aria-expanded="false"][@aria-controls="primary-content-products"][@data-state="closed"])'))->toBe(1.0)
        ->and($xpath->evaluate('string(//button[@id="primary-trigger-products"]/@data-action)'))->toContain('analytics#track')
        ->and($xpath->evaluate('count(//*[@data-slot="navigation-menu-trigger-icon"][@aria-hidden="true"])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//li/button[@disabled])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//nav/ul/li/a[@href="/about"][@aria-current="page"][@data-turbo-frame="main"])'))->toBe(1.0)
        ->and($xpath->evaluate('count(//nav/div[@data-slot="navigation-menu-viewport"][@hidden]/div[@id="primary-content-products"][@aria-labelledby="primary-trigger-products"][@hidden][@inert])'))->toBe(1.0)
        ->and($html)->not->toContain('role="menu', 'role="tab');
})->with(['hw:', 'x-hw::']);

it('renders the selected anchor and falls back to trigger anchoring for unknown values', function () {
    $this->blade('<hw:navigation-menu anchor="menu" data-anchor="trigger" />')
        ->assertSee('data-anchor="menu"', false);

    $this->blade('<hw:navigation-menu anchor="unknown" />')
        ->assertSee('data-anchor="trigger"', false);
});

it('renders lazy frame placeholders without starting a request on the server markup', function () {
    $view = $this->blade(<<<'BLADE'
        <hw:navigation-menu id="primary">
            <hw:navigation-menu.viewport>
                <hw:navigation-menu.content value="news" frame="nav-news" src="/nav/news">
                    <p>Loading news</p>
                </hw:navigation-menu.content>
                <hw:navigation-menu.content value="guides" src="/nav/guides" />
            </hw:navigation-menu.viewport>
        </hw:navigation-menu>
        BLADE);

    $view->assertSee('id="nav-news"', false)
        ->assertSee('id="primary-content-guides-frame"', false)
        ->assertSee('data-navigation-menu-src="/nav/news"', false)
        ->assertSee('target="_top"', false)
        ->assertSeeText('Loading news');

    expect((string) $view)->not->toMatch('/\ssrc=/');
});

it('registers navigation menu parts and the controller for package discovery', function () {
    $registry = HotwireRegistry::make();

    foreach (['list', 'item', 'trigger', 'link', 'content', 'viewport'] as $part) {
        expect($registry->component("navigation-menu.{$part}"))->not->toBeNull();
    }

    expect($registry->component('navigation-menu')?->controllers)->toBe(['navigation-menu'])
        ->and($registry->controller('navigation-menu')?->npm)->toHaveKey('@floating-ui/dom');
});
