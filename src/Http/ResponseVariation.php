<?php

namespace Emaia\LaravelHotwire\Http;

use Emaia\LaravelHotwireTurbo\Http\TurboRequestVariation;
use Illuminate\Http\Request;

/** @internal */
final class ResponseVariation
{
    private const string ATTRIBUTE = self::class;

    /** Mark that the response depends on visitor state the cache key does not include, such as a cookie. */
    public static function mark(Request $request): void
    {
        $request->attributes->set(self::ATTRIBUTE, true);
    }

    /** Check whether the response consulted visitor state or Turbo negotiation. */
    public static function isMarked(Request $request): bool
    {
        return $request->attributes->get(self::ATTRIBUTE) === true || TurboRequestVariation::isMarked($request);
    }
}
