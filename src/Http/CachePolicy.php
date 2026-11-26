<?php

namespace Emaia\LaravelHotwire\Http;

use Emaia\LaravelHotwire\Http\Middleware\CacheRoute;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** @internal */
final readonly class CachePolicy
{
    public const string GUARDED_REQUEST = self::class.':guarded';

    public function __construct(private Router $router) {}

    /** Decide whether a shared origin cache may look up an entry for this request. */
    public function mayRead(Request $request): bool
    {
        return $request->attributes->get(self::GUARDED_REQUEST) === true
            && $request->isMethodCacheable()
            && ! $request->hasSession()
            && ! $request->headers->has('Authorization')
            && $request->getUser() === null
            && $this->routePolicy($request) === 'public';
    }

    /** Admit only explicitly public, fresh HTML after the response has passed through the application. */
    public function mayStore(Request $request, Response $response): bool
    {
        if (! $this->mayRead($request) || $response->getStatusCode() !== 200
            || $response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        $headers = $response->headers;
        $mediaType = strtolower(trim(explode(';', $headers->get('Content-Type') ?? '', 2)[0]));
        $lifetime = $headers->getCacheControlDirective('s-maxage') ?? $headers->getCacheControlDirective('max-age');

        return $mediaType === 'text/html'
            && $headers->getCookies() === []
            && ! $headers->has('Content-Encoding')
            && $headers->hasCacheControlDirective('public')
            && ! $headers->hasCacheControlDirective('private')
            && ! $headers->hasCacheControlDirective('no-store')
            && ! $headers->hasCacheControlDirective('no-cache')
            && is_string($lifetime) && ctype_digit($lifetime) && (int) $lifetime > 0
            && array_diff(array_map(strtolower(...), $response->getVary()), ['accept-encoding']) === [];
    }

    /** Prevent shared storage of any managed response that fails the public policy. */
    public function enforce(Request $request, Response $response): void
    {
        if ($this->routePolicy($request) !== null && ! $this->mayStore($request, $response)) {
            $this->preventStorage($response);
        }
    }

    /** Remove origin headers that could take precedence over the private cache policy. */
    public function preventStorage(Response $response): void
    {
        foreach (['CDN-Cache-Control', 'Cloudflare-CDN-Cache-Control', 'Surrogate-Control', 'Expires'] as $header) {
            $response->headers->remove($header);
        }

        $response->headers->set('Cache-Control', 'private, no-store');
    }

    private function routePolicy(Request $request): ?string
    {
        $route = $request->route();

        if (! $route instanceof Route) {
            return null;
        }

        $policy = null;

        // Read the declaration even if authorization or binding short-circuited before the route middleware.
        foreach ($this->router->gatherRouteMiddleware($route) as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if ($middleware === CacheRoute::class) {
                return 'private';
            }

            if (str_starts_with($middleware, CacheRoute::class.':')) {
                if ($middleware !== CacheRoute::class.':public') {
                    return 'private';
                }

                $policy = 'public';
            }
        }

        return $policy;
    }
}
