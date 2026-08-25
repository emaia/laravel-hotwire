<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class StimulusIdentifier
{
    /** `\z` rather than `$` so a trailing newline can't sneak past the anchor. */
    private const string PATTERN = '/^[a-z0-9][a-z0-9_-]*(?:--[a-z0-9][a-z0-9_-]*)*\z/';

    /**
     * Reject a `controller` prop that is not a valid Stimulus identifier.
     *
     * @param  string  $component  Blade tag name, used to name the offender in the message.
     * @return string The identifier, so callers can assign it inline.
     */
    public static function guard(string $identifier, string $component): string
    {
        if (! preg_match(self::PATTERN, $identifier)) {
            throw new InvalidArgumentException("Invalid {$component} controller identifier.");
        }

        return $identifier;
    }
}
