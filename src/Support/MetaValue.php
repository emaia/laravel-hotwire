<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class MetaValue
{
    /**
     * Normalize a meta content value against its allowlist.
     *
     * @param  string[]  $allowed
     */
    public static function enum(string $value, array $allowed, string $component): string
    {
        $value = strtolower(trim($value));

        if (! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported {$component} value. Supported values: ".implode(', ', $allowed).'.'
            );
        }

        return $value;
    }

    /** Normalize a meta flag, accepting the bare attribute, a bound bool and the string spelling alike. */
    public static function boolean(bool|string $value, string $component): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return self::enum($value, ['true', 'false'], $component);
    }
}
