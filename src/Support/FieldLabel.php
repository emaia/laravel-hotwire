<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldLabel
{
    /** Derive the id a set label carries, from the owner id base or its name. */
    public static function idFor(?string $id, ?string $name): ?string
    {
        $base = $id ?: ($name !== null && $name !== '' ? FieldKey::toId($name) : null);

        return $base === null || $base === '' ? null : $base.'-label';
    }

    /**
     * Reserve the base id or its next available numeric suffix.
     *
     * @param  string[]  $claimedIds
     */
    public static function uniqueId(string $baseId, array $claimedIds): string
    {
        $resolvedId = $baseId;
        $suffix = 2;

        while (in_array($resolvedId, $claimedIds, true)) {
            $resolvedId = $baseId.'-'.$suffix;
            $suffix++;
        }

        return $resolvedId;
    }
}
