<?php

namespace Emaia\LaravelHotwire\Http\Middleware;

use Closure;
use Emaia\LaravelHotwire\Http\CachePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\ResponseCache\Attributes\FlexibleCache;
use Spatie\ResponseCache\CacheProfiles\CacheProfile;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\ResponseCache;
use Symfony\Component\HttpFoundation\Response;

if (! class_exists(CacheResponse::class)) {
    return;
}

/** @internal */
final class CachePublicResponse extends CacheResponse
{
    private const string ADMISSION_HEADER = 'X-Hotwire-Cache-Policy';

    private const string ADMISSION_VERSION = 'public-html-v1';

    protected string $pendingCacheAttribute = self::class.':pending';

    public function __construct(
        ResponseCache $responseCache,
        private readonly CachePolicy $policy,
        private readonly CacheProfile $profile,
    ) {
        parent::__construct($responseCache);
    }

    /** Bypass the origin cache before lookup unless the request is eligible for ordinary response caching. */
    public function handle(Request $request, Closure $next, ...$args): Response
    {
        if (! $request->isMethod('GET') || ! $this->policy->mayRead($request)
            || $this->getAttributeFromRequest($request) instanceof FlexibleCache) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$args);
    }

    /** Apply the final HTTP policy before Spatie strips cookies from its stored copy. */
    public function terminate(Request $request, Response $response): void
    {
        if ($request->isMethod('GET') && $this->policy->mayStore($request, $response)) {
            parent::terminate($request, $response);
        }
    }

    protected function getCachedResponse(Request $request, array $tags): ?Response
    {
        $response = parent::getCachedResponse($request, $tags);

        if ($response === null || $response->headers->get(self::ADMISSION_HEADER) !== self::ADMISSION_VERSION
            || ! $this->policy->mayStore($request, $response)) {
            return null;
        }

        $age = $response->getAge();

        if ($age >= ($response->getMaxAge() ?? 0)) {
            return null;
        }

        $response->headers->remove(self::ADMISSION_HEADER);
        $response->headers->set('Age', (string) $age);

        return $response;
    }

    protected function cacheResponse(Request $request, Response $response, ?int $lifetimeInSeconds, array $tags): void
    {
        // A pre-aged response needs an HTTP cache that can account for upstream residence time.
        if ($response->headers->has('Age')) {
            return;
        }

        $ttl = min(
            $lifetimeInSeconds ?? $this->profile->cacheLifetimeInSeconds($request),
            ($response->getMaxAge() ?? 0) - $response->getAge(),
        );

        if ($ttl <= 0) {
            return;
        }

        // Reject entries written by the ordinary Spatie middleware, even if they share a key.
        $candidate = clone $response;
        $candidate->headers->set(self::ADMISSION_HEADER, self::ADMISSION_VERSION);

        parent::cacheResponse($request, $candidate, $ttl, $tags);
    }

    /** Keep session and other request-specific replacements out of canonical cached HTML. */
    protected function getReplacers(): Collection
    {
        return new Collection;
    }
}
