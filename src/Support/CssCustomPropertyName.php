<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final class CssCustomPropertyName
{
    private const string PATTERN = '~^--(?:[-_a-zA-Z0-9\x{0080}-\x{10FFFF}]|\\\\(?:[0-9a-fA-F]{1,6}[ \t\r\n\f]?|[^\r\n\f0-9a-fA-F]))+$~u';

    /** Determine whether a source token is a valid CSS custom-property name. */
    public static function isValid(mixed $name): bool
    {
        return is_string($name) && preg_match(self::PATTERN, $name) === 1;
    }
}
