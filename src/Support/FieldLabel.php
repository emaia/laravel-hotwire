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
}
