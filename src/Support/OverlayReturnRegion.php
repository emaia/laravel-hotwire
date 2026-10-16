<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class OverlayReturnRegion
{
    /** Validate a region name shared by an opener and the parent region it updates. */
    public static function name(string $name): string
    {
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.:-]{0,99}\z/D', $name) !== 1) {
            throw new InvalidArgumentException('An overlay return name must be a non-empty identifier without whitespace.');
        }

        return $name;
    }
}
