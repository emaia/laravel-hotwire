<?php

namespace Emaia\LaravelHotwire\Support;

final class OptionPairs
{
    /**
     * Normalize a flat options list into value-label pairs.
     *
     * @param  array<int|string, string>  $options
     * @return array<int|string, string>
     */
    public static function normalize(array $options): array
    {
        if (! array_is_list($options)) {
            return $options;
        }

        return array_combine($options, $options);
    }
}
