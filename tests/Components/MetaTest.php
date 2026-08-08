<?php

use Emaia\LaravelHotwire\Components\Meta\Cache;
use Emaia\LaravelHotwire\Components\Meta\Prefetch;
use Emaia\LaravelHotwire\Components\Meta\Refresh;
use Emaia\LaravelHotwire\Components\Meta\ViewTransition;
use Emaia\LaravelHotwire\Components\Meta\VisitControl;

// --- Granular components render without props ---

it('renders every granular meta with its default', function (string $tag, string $expected) {
    expect((string) $this->blade("<x-hw::{$tag} />"))->toContain($expected);
})->with([
    'prefetch' => ['meta.prefetch', '<meta name="turbo-prefetch" content="true">'],
    'cache' => ['meta.cache', '<meta name="turbo-cache-control" content="no-preview">'],
    'visit control' => ['meta.visit-control', '<meta name="turbo-visit-control" content="reload">'],
    'root' => ['meta.root', '<meta name="turbo-root" content="/">'],
    'view transition' => ['meta.view-transition', '<meta name="view-transition" content="same-origin">'],
    'color scheme' => ['meta.color-scheme', '<meta name="color-scheme" content="light dark">'],
]);

it('renders both refresh metas from one component', function () {
    expect((string) $this->blade('<x-hw::meta.refresh />'))
        ->toContain('<meta name="turbo-refresh-method" content="morph">')
        ->toContain('<meta name="turbo-refresh-scroll" content="preserve">');
});

it('renders the csrf token from the session', function () {
    expect((string) $this->blade('<x-hw::meta.csrf />'))
        ->toContain('<meta name="csrf-token" content="'.csrf_token().'">');
});

// --- Overriding a granular ---

it('overrides a granular value', function (string $blade, string $expected) {
    expect((string) $this->blade($blade))->toContain($expected);
})->with([
    'prefetch off' => ['<x-hw::meta.prefetch enabled="false" />', 'content="false"'],
    'prefetch bound off' => ['<x-hw::meta.prefetch :enabled="false" />', 'content="false"'],
    'cache no-cache' => ['<x-hw::meta.cache control="no-cache" />', 'content="no-cache"'],
    'refresh replace' => ['<x-hw::meta.refresh method="replace" />', 'turbo-refresh-method" content="replace"'],
    'scroll reset' => ['<x-hw::meta.refresh scroll="reset" />', 'turbo-refresh-scroll" content="reset"'],
    'root path' => ['<x-hw::meta.root path="app" />', 'content="/app"'],
    'color scheme dark' => ['<x-hw::meta.color-scheme schemes="dark" />', 'content="dark"'],
]);

it('rejects a value outside the allowlist', function (Closure $make, string $message) {
    expect($make)->toThrow(InvalidArgumentException::class, $message);
})->with([
    'refresh method' => [fn () => new Refresh(method: 'morf'), 'Supported values: replace, morph.'],
    'refresh scroll' => [fn () => new Refresh(scroll: 'keep'), 'Supported values: reset, preserve.'],
    'cache' => [fn () => new Cache(control: 'never'), 'Supported values: no-cache, no-preview.'],
    'visit control' => [fn () => new VisitControl(control: 'restart'), 'Supported values: reload.'],
    'view transition' => [fn () => new ViewTransition(scope: 'cross-origin'), 'Supported values: same-origin.'],
    'prefetch' => [fn () => new Prefetch(enabled: 'maybe'), 'Supported values: true, false.'],
]);

// --- Umbrella ---

it('renders nothing when no prop asks for a tag', function () {
    expect(trim((string) $this->blade('<x-hw::meta />')))->toBe('');
});

it('renders only the metas the props ask for', function () {
    $html = (string) $this->blade('<x-hw::meta csrf prefetch />');

    expect($html)
        ->toContain('<meta name="turbo-prefetch" content="true">')
        ->toContain('<meta name="csrf-token"')
        ->not->toContain('turbo-cache-control')
        ->not->toContain('turbo-refresh-method')
        ->not->toContain('color-scheme');
});

it('takes the granular default from a bare attribute and the value from a written one', function () {
    expect((string) $this->blade('<x-hw::meta cache />'))->toContain('content="no-preview"')
        ->and((string) $this->blade('<x-hw::meta cache="no-cache" />'))->toContain('content="no-cache"')
        ->and((string) $this->blade('<x-hw::meta root="/app" />'))->toContain('content="/app"')
        ->and((string) $this->blade('<x-hw::meta view-transition />'))->toContain('content="same-origin"');
});

it('states both refresh metas from either half', function () {
    expect((string) $this->blade('<x-hw::meta refresh />'))
        ->toContain('turbo-refresh-method" content="morph"')
        ->toContain('turbo-refresh-scroll" content="preserve"')
        ->and((string) $this->blade('<x-hw::meta scroll="reset" />'))
        ->toContain('turbo-refresh-method" content="morph"')
        ->toContain('turbo-refresh-scroll" content="reset"');
});

it('ignores the false half when the other refresh meta prop asks for the pair', function () {
    expect((string) $this->blade('<x-hw::meta refresh="replace" scroll="false" />'))
        ->toContain('turbo-refresh-method" content="replace"')
        ->toContain('turbo-refresh-scroll" content="preserve"')
        ->and((string) $this->blade('<x-hw::meta refresh="false" scroll="reset" />'))
        ->toContain('turbo-refresh-method" content="morph"')
        ->toContain('turbo-refresh-scroll" content="reset"');
});

it('leaves an enum meta out when its prop is false', function () {
    expect((string) $this->blade('<x-hw::meta :cache="false" csrf />'))
        ->not->toContain('turbo-cache-control')
        ->toContain('csrf-token');
});

it('states the disabling value when prefetch is false, since that is what the meta is for', function () {
    expect((string) $this->blade('<x-hw::meta prefetch="false" />'))
        ->toContain('<meta name="turbo-prefetch" content="false">')
        ->and((string) $this->blade('<x-hw::meta :prefetch="false" />'))
        ->toContain('<meta name="turbo-prefetch" content="false">');
});

it('resolves a typical head in one tag', function () {
    $html = (string) $this->blade('<x-hw::meta csrf color-scheme prefetch refresh />');

    expect($html)
        ->toContain('turbo-prefetch')
        ->toContain('turbo-refresh-method')
        ->toContain('turbo-refresh-scroll')
        ->toContain('csrf-token')
        ->toContain('<meta name="color-scheme" content="light dark">')
        ->toContain('const storageKey = "hotwire.colorScheme"')
        ->toContain('document.documentElement.setAttribute(attribute, scheme)');
});

it('leaves the color scheme script out when the color-scheme prop is absent', function () {
    expect((string) $this->blade('<x-hw::meta csrf />'))
        ->not->toContain('hotwire.colorScheme')
        ->not->toContain('document.documentElement.setAttribute(attribute, scheme)');
});
