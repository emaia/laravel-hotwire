<?php

namespace Emaia\LaravelHotwire\Support;

final class AvatarFallbackText
{
    public static function resolve(?string $name = null, ?string $initials = null, ?string $fallback = null): ?string
    {
        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        if ($initials !== null && $initials !== '') {
            return $initials;
        }

        if ($name === null || trim($name) === '') {
            return null;
        }

        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part): bool => $part !== ''));

        if (count($parts) === 0) {
            return null;
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }

        return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[count($parts) - 1], 0, 1));
    }
}
