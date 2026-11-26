<?php

use Emaia\LaravelHotwire\Http\Middleware\EnforceCachePolicy;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\Events\CacheMissedEvent;
use Spatie\ResponseCache\Events\ResponseCacheHitEvent;
use Spatie\ResponseCache\Hasher\RequestHasher;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Replacers\Replacer;
use Spatie\ResponseCache\ResponseCache;
use Spatie\ResponseCache\ResponseCacheServiceProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class SpatieLateCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->setCookie(new Cookie('late', 'cookie'));

        return $response;
    }
}

#[FlexibleCache(lifetime: 600, grace: 60)]
final class SpatieFlexiblePage
{
    public function __invoke(): Response
    {
        return response('Flexible')
            ->header('Cache-Control', 'public, max-age=60');
    }
}

beforeEach(function () {
    if (! class_exists(CacheResponse::class)) {
        $this->markTestSkipped('Install Spatie ResponseCache 8.7 to run the optional integration tests.');
    }

    app()->register(ResponseCacheServiceProvider::class);
    app(Kernel::class)->prependMiddleware(EnforceCachePolicy::class);
    config()->set([
        'responsecache.cache.store' => 'array',
        'responsecache.cache.lifetime_in_seconds' => 600,
        'responsecache.debug.enabled' => true,
    ]);
});

it('serves canonical HTML to prefetch and Drive without running configured replacers', function () {
    $replacer = new class implements Replacer
    {
        public function prepareResponseToCache(Response $response): void
        {
            $response->setContent('Replaced on store');
        }

        public function replaceInCachedResponse(Response $response): void
        {
            $response->setContent('Replaced on hit');
        }
    };
    app()->instance($replacer::class, $replacer);
    config()->set('responsecache.replacers', [$replacer::class]);
    $renders = 0;
    Route::get('/cache-public', function () use (&$renders) {
        return response('Render '.++$renders)
            ->header('Cache-Control', 'public, s-maxage=300');
    })->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-public', ['X-Sec-Purpose' => 'prefetch'])
        ->assertContent('Render 1')
        ->assertHeader('X-Cache-Status', 'MISS');

    $this->get('/cache-public')
        ->assertContent('Render 1')
        ->assertHeader('X-Cache-Status', 'HIT')
        ->assertHeaderMissing('X-Hotwire-Cache-Policy');

    $this->get('/cache-public', ['Authorization' => 'Bearer test-token'])
        ->assertContent('Render 2')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeaderMissing('X-Cache-Status');

    $this->get('/cache-public')->assertContent('Render 1');
});

it('does not let the Spatie lifetime extend the HTML freshness window', function () {
    $renders = 0;
    Route::get('/cache-ttl', function () use (&$renders) {
        return response('Render '.++$renders)
            ->header('Cache-Control', 'public, max-age=0, s-maxage=2');
    })->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-ttl')->assertContent('Render 1');
    $this->get('/cache-ttl')->assertHeader('X-Cache-Status', 'HIT');
    $this->travel(3)->seconds();
    Event::fake([ResponseCacheHitEvent::class, CacheMissedEvent::class]);
    $this->get('/cache-ttl')->assertContent('Render 2');

    Event::assertNotDispatched(ResponseCacheHitEvent::class);
    Event::assertDispatchedTimes(CacheMissedEvent::class, 1);
});

it('does not use entries written without the current negotiation guard', function (?string $version) {
    $legacy = Request::create('http://localhost/cache-legacy');
    $response = response('Legacy HTML')
        ->header('Content-Type', 'text/html')
        ->header('Cache-Control', 'public, s-maxage=300');
    if ($version !== null) {
        $response->header('X-Hotwire-Cache-Policy', $version);
    }
    app(ResponseCache::class)->cacheResponse($legacy, $response);

    Route::get('/cache-legacy', fn () => response('Current HTML')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    Event::fake([ResponseCacheHitEvent::class, CacheMissedEvent::class]);
    $this->get('/cache-legacy')->assertContent('Current HTML')->assertHeader('X-Cache-Status', 'MISS');
    Event::assertNotDispatched(ResponseCacheHitEvent::class);
    Event::assertDispatchedTimes(CacheMissedEvent::class, 1);

    $this->get('/cache-legacy')->assertContent('Current HTML')->assertHeader('X-Cache-Status', 'HIT');
    Event::assertDispatchedTimes(ResponseCacheHitEvent::class, 1);
})->with(['unguarded' => null, 'before negotiation detection' => 'public-html-v1']);

it('never warms the origin cache with either branch of negotiated HTML', function () {
    Route::get('/cache-variants', fn (Request $request) => response($request->wasFromTurboFrame() ? 'Frame' : 'Page')
        ->header('Cache-Control', 'public, s-maxage=300'))->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    foreach ([[], ['Turbo-Frame' => 'edit'], []] as $headers) {
        $this->get('/cache-variants', $headers)
            ->assertContent($headers === [] ? 'Page' : 'Frame')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Cache-Status', 'MISS');
    }

    expect(app(ResponseCache::class)->hasBeenCached(Request::create('http://localhost/cache-variants')))->toBeFalse();
});

it('revalidates a cached public representation without replacing its body', function () {
    Route::get('/cache-revalidate', fn () => response('Current HTML')->setEtag('current')
        ->header('Cache-Control', 'public, s-maxage=300'))->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-revalidate')->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/cache-revalidate', ['If-None-Match' => '"current"'])
        ->assertStatus(304)->assertHeader('Cache-Control', 'public, s-maxage=300')->assertHeader('X-Cache-Status', 'HIT');
    $this->get('/cache-revalidate')->assertContent('Current HTML')->assertHeader('X-Cache-Status', 'HIT');
});

it('does not read a public entry ahead of StartSession even if the adapter is listed first', function () {
    config()->set([
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'session.driver' => 'array',
    ]);
    Route::get('/cache-session-order', fn () => response('Canonical HTML')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-session-order')->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/cache-session-order')->assertHeader('X-Cache-Status', 'HIT');

    Route::get('/cache-session-order', fn () => response('Session HTML')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware(['hotwire.cache.spatie', 'hotwire.cache:public', 'web']);

    $this->get('/cache-session-order')
        ->assertContent('Session HTML')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeaderMissing('X-Cache-Status');
});

it('does not store a response that receives a cookie after the spatie middleware returns', function () {
    app(Kernel::class)->pushMiddleware(SpatieLateCookie::class);
    $renders = 0;
    Route::get('/cache-cookie-late', function () use (&$renders) {
        return response('Render '.++$renders)->header('Cache-Control', 'public, s-maxage=300');
    })->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-cookie-late')->assertContent('Render 1')->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/cache-cookie-late')->assertContent('Render 2')->assertHeader('Cache-Control', 'no-store, private');

    expect(app(ResponseCache::class)->hasBeenCached(Request::create('http://localhost/cache-cookie-late')))->toBeFalse();
});

it('does not route flexible cache attributes through the unguarded spatie middleware', function () {
    Route::get('/cache-flexible', SpatieFlexiblePage::class)
        ->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-flexible')->assertOk()->assertHeaderMissing('X-Cache-Status');

    $key = app(RequestHasher::class)->getHashFor(Request::create('http://localhost/cache-flexible'));
    expect(cache()->store('array')->has($key))->toBeFalse();
});

it('bypasses a warm private URL and renders flash, errors, old input and CSRF for each session', function () {
    config()->set([
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'session.driver' => 'array',
    ]);

    Route::get('/cache-state', fn () => response('Warm public body')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware(['hotwire.cache:public', 'hotwire.cache.spatie']);

    $this->get('/cache-state')->assertHeader('X-Cache-Status', 'MISS');
    $this->get('/cache-state')->assertHeader('X-Cache-Status', 'HIT');

    Route::get('/cache-state', fn () => response(Blade::render(<<<'BLADE'
        <hw:meta.csrf />
        <hw:toaster />
        <hw:form action="/submit" method="post">
            <hw:input type="text" name="display" />
            <hw:field.error name="display" />
        </hw:form>
        BLADE))->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware(['web', 'hotwire.cache:private', 'hotwire.cache.spatie']);

    Route::get('/cache-state-seed/{visitor}', function (Request $request, string $visitor) {
        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();
        $session->flash('toast', ['message' => 'Flash '.$visitor]);
        $session->flash('errors', (new ViewErrorBag)->put('default', new MessageBag(['display' => 'Error '.$visitor])));
        $session->flashInput(['display' => 'Old '.$visitor]);

        return $session->token();
    })->middleware('web');

    Event::fake([ResponseCacheHitEvent::class]);
    $cookieName = config('session.cookie');
    $first = $this->get('/cache-state-seed/first')->assertOk();
    $second = $this->get('/cache-state-seed/second')->assertOk();
    $firstId = $first->getCookie($cookieName)->getValue();
    $secondId = $second->getCookie($cookieName)->getValue();
    expect($firstId)->not->toBe($secondId);

    foreach ([[$first, $firstId, 'first', 'second'], [$second, $secondId, 'second', 'first']] as [$seed, $sessionId, $visitor, $other]) {
        app('session')->driver()->flush();
        app()->forgetScopedInstances();

        $this->withCookie($cookieName, $sessionId)->get('/cache-state')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeaderMissing('X-Cache-Status')
            ->assertDontSee('Warm public body')
            ->assertSee('Flash '.$visitor)
            ->assertSee('Error '.$visitor)
            ->assertSee('Old '.$visitor)
            ->assertSee('name="_token" value="'.$seed->getContent().'"', false)
            ->assertDontSee('Flash '.$other)
            ->assertDontSee('Error '.$other)
            ->assertDontSee('Old '.$other);
    }

    Event::assertNotDispatched(ResponseCacheHitEvent::class);
});
