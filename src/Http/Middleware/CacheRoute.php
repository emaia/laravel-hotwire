<?php

namespace Emaia\LaravelHotwire\Http\Middleware;

use Closure;
use Emaia\LaravelHotwire\Http\CachePolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CacheRoute
{
    public function __construct(private CachePolicy $policy) {}

    /** Mark a route for the cache guard and fail closed when the guard is missing. */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->attributes->get(CachePolicy::GUARDED_REQUEST) !== true) {
            $this->policy->preventStorage($response);
        }

        return $response;
    }
}
