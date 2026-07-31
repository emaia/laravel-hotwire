<?php

namespace Emaia\LaravelHotwire\Support;

use InvalidArgumentException;

final class PolymorphicTag
{
    /**
     * Normalize a component root tag against its semantic allowlist.
     *
     * @param  string[]  $allowed
     */
    public static function normalize(string $tag, array $allowed, string $component): string
    {
        $tag = strtolower(trim($tag));

        if (! in_array($tag, $allowed, true)) {
            throw new InvalidArgumentException(
                "Unsupported {$component} tag. Supported values: ".implode(', ', $allowed).'.'
            );
        }

        return $tag;
    }

    /** Normalize a native button type. */
    public static function buttonType(string $type): string
    {
        $type = strtolower(trim($type));

        if (! in_array($type, ['button', 'submit', 'reset'], true)) {
            throw new InvalidArgumentException('Unsupported button type. Supported values: button, submit, reset.');
        }

        return $type;
    }
}
