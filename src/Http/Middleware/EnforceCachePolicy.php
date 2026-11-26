<?php

namespace Emaia\LaravelHotwire\Http\Middleware;

use Closure;
use Emaia\LaravelHotwire\Http\CachePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceCachePolicy
{
    public function __construct(private CachePolicy $policy) {}

    /** Check the cache declaration after inner middleware has finished modifying the response. */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(CachePolicy::GUARDED_REQUEST, true);
        $response = $next($request);

        $this->policy->enforce($request, $response);

        return $response;
    }
}
