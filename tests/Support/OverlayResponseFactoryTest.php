<?php

use Emaia\LaravelHotwire\LaravelHotwireServiceProvider;
use Emaia\LaravelHotwireTurbo\TurboStreamBuilder;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ViewErrorBag;

afterEach(function () {
    request()->headers->remove('Turbo-Frame');
    request()->headers->remove('X-Hotwire-Overlay-Host');
    request()->headers->remove('X-Hotwire-Overlay-Instance');
    request()->headers->remove('X-Hotwire-Overlay-Origin');
});

it('exposes a family-specific entry that returns a composable Stream response', function (string $family) {
    $builder = response()->{$family}()->open('<p>Review required</p>');

    expect($builder)->toBeInstanceOf(TurboStreamBuilder::class);
    $html = $builder->toast('info', 'Review this record')->toHtml();
    expect($html)
        ->toContain('action="hotwire-overlay-open" target="'.$family.'"')
        ->toContain('data-overlay-family="'.$family.'"')
        ->toContain('<p>Review required</p>')
        ->toContain('data-toast-message-value="Review this record"')
        ->not->toContain('data-overlay-origin=');
})->with(['modal', 'sheet', 'drawer']);

it('uses the shared presentation contract and explicit host/origin', function () {
    $origin = 'drawer--'.str_repeat('f', 32);
    $html = response()->sheet()->open(
        '<h2>Details</h2>', side: 'left', size: '28rem', host: 'settings-sheet', origin: $origin,
    )->toHtml();

    expect($html)
        ->toContain('target="settings-sheet"')
        ->toContain('data-overlay-family="sheet"')
        ->toContain('data-overlay-side="left"')
        ->toContain('data-overlay-size="28rem"')
        ->toContain('data-overlay-origin="'.$origin.'"');

    expect(response()->drawer()->open('Review', side: 'top')->toHtml())
        ->toContain('data-overlay-side="up"');
});

it('uses the request origin only when it unambiguously matches its overlay instance', function () {
    $token = str_repeat('c', 32);
    $origin = 'drawer--'.$token;
    request()->headers->set('Turbo-Frame', $origin);
    request()->headers->set('X-Hotwire-Overlay-Host', 'drawer');
    request()->headers->set('X-Hotwire-Overlay-Instance', $token);
    request()->headers->set('X-Hotwire-Overlay-Origin', $origin);

    expect(response()->modal()->open('<p>Resolve conflict</p>')->toHtml())
        ->toContain('data-overlay-origin="'.$origin.'"');

    request()->headers->remove('X-Hotwire-Overlay-Origin');
    expect(response()->modal()->open('<p>Resolve conflict</p>')->toHtml())
        ->toContain('data-overlay-origin="'.$origin.'"');

    request()->headers->set('X-Hotwire-Overlay-Origin', 'sheet--'.$token);
    expect(fn () => response()->modal()->open('<p>Resolve conflict</p>'))
        ->toThrow(InvalidArgumentException::class, 'Invalid overlay origin context');
});

it('requires a source URL for a streamed form and embeds it for validation redirects', function () {
    expect(fn () => response()->modal()->open('<form action="/customers"><button>Save</button></form>'))
        ->toThrow(InvalidArgumentException::class, 'source');

    expect(response()->modal()->open('<form action="/customers"><button>Save</button></form>', source: '/customers/create')->toHtml())
        ->toContain('data-overlay-source="/customers/create"');
});

it('accepts Blade views and returns a real Turbo Stream response', function () {
    $view = view()->file(__DIR__.'/../Fixtures/views/overlay-content.blade.php', ['title' => 'Review <item>']);
    $response = response()->modal()->open($view)->toResponse(request());

    expect($response->headers->get('Content-Type'))->toContain('text/vnd.turbo-stream.html')
        ->and($response->getContent())->toContain('Review &lt;item&gt;')
        ->not->toContain('Review <item>');

    expect(response()->sheet()->open(new HtmlString('<strong>Sheet</strong>'))->toHtml())
        ->toContain('<strong>Sheet</strong>');
});

it('isolates generated form and field ids across independently streamed openings', function () {
    view()->share('errors', new ViewErrorBag);
    $render = function (): string {
        app()->forgetScopedInstances();

        return response()->modal()->open(
            view()->file(__DIR__.'/../Fixtures/views/overlay-form.blade.php'),
            source: '/customers/create',
        )->toHtml();
    };
    $first = $render();
    $second = $render();
    preg_match_all('/\bid="([^"]+)"/', $first, $firstIds);
    preg_match_all('/\bid="([^"]+)"/', $second, $secondIds);

    expect($firstIds[1])->not->toBeEmpty()
        ->and(array_intersect($firstIds[1], $secondIds[1]))->toBe([]);
    preg_match('/<label[^>]*\bfor="([^"]+)"/', $first, $label);
    expect($first)->toContain('id="'.$label[1].'"');
});

it('rejects cross-origin, protocol-relative and fragmented source URLs', function (string $source) {
    expect(fn () => response()->modal()->open('<form></form>', source: $source))
        ->toThrow(InvalidArgumentException::class, 'same-origin URL');
})->with(['https://other.test/create', '//other.test/create', '/create#fragment', 'http://[']);

it('compares absolute source URLs using their effective protocol port', function () {
    $request = Request::create('http://localhost:8000/');
    app()->instance('request', $request);

    expect(fn () => response()->modal()->open('Form', source: 'http://localhost/create'))
        ->toThrow(InvalidArgumentException::class, 'same-origin URL');
    expect(response()->modal()->open('Form', source: 'http://localhost:8000/create')->toHtml())
        ->toContain('data-overlay-source="http://localhost:8000/create"');

    app()->instance('request', Request::create('https://localhost/'));
    expect(response()->modal()->open('Form', source: 'https://localhost:443/create')->toHtml())
        ->toContain('data-overlay-source="https://localhost:443/create"');
});

it('rejects incompatible presentation and invalid targets instead of silently choosing a default', function () {
    expect(fn () => response()->modal()->open('Review', side: 'left'))
        ->toThrow(InvalidArgumentException::class, 'Modal does not support overlay-side');
    expect(fn () => response()->sheet()->open('Review', side: 'rigth'))
        ->toThrow(InvalidArgumentException::class, 'Sheet overlay-side');
    expect(fn () => response()->drawer()->open('Review', host: ''))
        ->toThrow(InvalidArgumentException::class, 'host');
    expect(fn () => response()->modal()->open('  '))
        ->toThrow(InvalidArgumentException::class, 'content');
});

it('does not replace a modal entry registered by the application', function () {
    ResponseFactory::macro('modal', fn () => 'application modal');
    try {
        (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();
        expect(response()->modal())->toBe('application modal');
    } finally {
        ResponseFactory::flushMacros();
        (new LaravelHotwireServiceProvider($this->app))->bootBladeIntegration();
    }
});
