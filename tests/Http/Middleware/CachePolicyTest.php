<?php

use Emaia\LaravelHotwire\Http\Middleware\EnforceCachePolicy;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class AddLateResponseCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->setCookie(new Cookie('late', 'cookie'));

        return $response;
    }
}

final class RejectPrivateRequest
{
    public function handle(): Response
    {
        return redirect('/login')->header('Cache-Control', 'public, s-maxage=300');
    }
}

beforeEach(function () {
    app(Kernel::class)->prependMiddleware(EnforceCachePolicy::class);
});

it('preserves the applications public TTL on a stateless page and prefetch', function () {
    Route::get('/cache-page', fn () => response('<main>Public page</main>')
        ->header('Cache-Control', 'public, max-age=0, s-maxage=300'))
        ->middleware('hotwire.cache:public');

    foreach ([[], ['X-Sec-Purpose' => 'prefetch']] as $headers) {
        $this->get('/cache-page', $headers)
            ->assertOk()
            ->assertSee('Public page')
            ->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300')
            ->assertHeaderMissing('Set-Cookie');
    }

    $this->call('HEAD', '/cache-page')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300')
        ->assertContent('');
});

it('serves the same cacheable canonical frame with or without Turbo-Frame', function () {
    Route::get('/cache-frame', fn () => response(Blade::render(
        '<hw:frame id="stock">Available</hw:frame>',
    ))->header('Cache-Control', 'public, max-age=60'))->middleware('hotwire.cache:public');

    $first = $this->get('/cache-frame')->assertOk()->assertSee('id="stock"', false);

    $this->get('/cache-frame', ['Turbo-Frame' => 'stock'])
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=60, public')
        ->assertContent($first->getContent());
});

it('does not change responses without a route policy', function () {
    Route::get('/cache-unmanaged', fn () => response('Outside')->header('Cache-Control', 'public, max-age=60'));

    $this->get('/cache-unmanaged')->assertHeader('Cache-Control', 'max-age=60, public');
});

it('keeps a negotiated response private for both page and frame requests', function () {
    Route::get('/cache-edit', fn () => response(Blade::render(
        '<hw:frame-or-page frame="edit" layout="cache-test-layout">Edit form</hw:frame-or-page>',
    ))->header('Cache-Control', 'public, s-maxage=300'))->middleware('hotwire.cache:private');

    view()->addLocation(__DIR__.'/../../Fixtures/views');

    $this->get('/cache-edit')
        ->assertOk()
        ->assertSee('Page layout')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->get('/cache-edit', ['Turbo-Frame' => 'edit'])
        ->assertOk()
        ->assertSee('id="edit"', false)
        ->assertDontSee('Page layout')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('protects private redirects even when a preceding middleware short-circuits', function () {
    Route::middleware([RejectPrivateRequest::class, 'hotwire.cache:private'])->get('/cache-redirect', fn () => 'Not reached');

    $this->get('/cache-redirect', ['Turbo-Frame' => 'edit'])
        ->assertStatus(303)
        ->assertRedirect('/login')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('does not share a response if any declaration is private', function () {
    Route::middleware('hotwire.cache:private')->group(function () {
        Route::get('/cache-nested', fn () => response('Private')->header('Cache-Control', 'public, s-maxage=300'))
            ->middleware('hotwire.cache:public');
    });

    $this->get('/cache-nested')->assertHeader('Cache-Control', 'no-store, private');
});

it('does not share a public route response with a cookie attached outside the router', function () {
    app(Kernel::class)->pushMiddleware(AddLateResponseCookie::class);

    Route::get('/cache-cookie', fn () => response('Cookie')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware('hotwire.cache:public');

    $this->get('/cache-cookie')
        ->assertPlainCookie('late', 'cookie')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('does not share a response when the route has a Laravel session', function () {
    config()->set('session.driver', 'array');
    config()->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

    Route::get('/cache-session', function (Request $request) {
        $request->session()->flash('success', 'Saved');

        return response('With session')->header('Cache-Control', 'public, s-maxage=300');
    })->middleware(['web', 'hotwire.cache:public']);

    $this->get('/cache-session')
        ->assertOk()
        ->assertSessionHas('success', 'Saved')
        ->assertHeader('Cache-Control', 'no-store, private');
});

it('does not share eligible HTML on authenticated or mutating requests', function () {
    Route::match(['GET', 'POST'], '/cache-request', fn () => response('Result')
        ->header('Cache-Control', 'public, s-maxage=300'))->middleware('hotwire.cache:public');

    $this->get('/cache-request', ['Authorization' => 'Bearer session-token'])
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->post('/cache-request')->assertHeader('Cache-Control', 'no-store, private');
});

it('downgrades responses outside the public HTML contract', function (int $status, array $headers) {
    Route::get('/cache-ineligible', fn () => response('Result', $status, $headers))
        ->middleware('hotwire.cache:public');

    $this->get('/cache-ineligible')
        ->assertStatus($status)
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeaderMissing('CDN-Cache-Control')
        ->assertHeaderMissing('Cloudflare-CDN-Cache-Control')
        ->assertHeaderMissing('Surrogate-Control');
})->with([
    'no opt-in on the response' => [200, []],
    'no freshness lifetime' => [200, ['Cache-Control' => 'public']],
    'private' => [200, ['Cache-Control' => 'private, max-age=60']],
    'no-store' => [200, ['Cache-Control' => 'public, no-store, s-maxage=300']],
    'no-cache' => [200, ['Cache-Control' => 'public, no-cache, s-maxage=300']],
    'zero shared TTL' => [200, ['Cache-Control' => 'public, max-age=60, s-maxage=0']],
    'Turbo Stream' => [200, ['Content-Type' => 'text/vnd.turbo-stream.html', 'Cache-Control' => 'public, s-maxage=300']],
    'JSON' => [200, ['Content-Type' => 'application/json', 'Cache-Control' => 'public, s-maxage=300']],
    'redirect' => [302, ['Location' => '/elsewhere', 'Cache-Control' => 'public, s-maxage=300']],
    'server error with CDN overrides' => [500, [
        'Cache-Control' => 'public, s-maxage=300',
        'CDN-Cache-Control' => 'public, max-age=300',
        'Cloudflare-CDN-Cache-Control' => 'public, max-age=300',
        'Surrogate-Control' => 'max-age=300',
    ]],
    'varies by Frame' => [200, ['Vary' => 'Turbo-Frame', 'Cache-Control' => 'public, s-maxage=300']],
    'varies by any header' => [200, ['Vary' => '*', 'Cache-Control' => 'public, s-maxage=300']],
]);

it('does not publish a policy-marked response without the final response guard', function () {
    app(Kernel::class)->setGlobalMiddleware([]);

    Route::get('/cache-no-guard', fn () => response('Not guarded')->header('Cache-Control', 'public, s-maxage=300'))
        ->middleware('hotwire.cache:public');

    $this->get('/cache-no-guard')
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});
