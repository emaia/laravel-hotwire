<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

final class FrameTarget
{
    /** Resolve an optional Turbo Frame target from a string, model or explicit false value. */
    public static function normalize(string|object|bool|null $frame): ?string
    {
        if ($frame === null || $frame === false) {
            return null;
        }

        if ($frame === true) {
            throw new InvalidArgumentException('The frame prop must be a non-empty string or an object resolvable via dom_id().');
        }

        $resolved = trim(is_object($frame) ? dom_id($frame) : $frame);

        return $resolved === '' ? null : $resolved;
    }

    /** Let an explicit native attribute override or suppress the frame prop. */
    public static function resolve(
        string|object|bool|null $frame,
        ComponentAttributeBag $attributes,
    ): ?string {
        if ($attributes->has('data-turbo-frame')) {
            return self::normalize($attributes->get('data-turbo-frame'));
        }

        return self::normalize($frame);
    }
}
