<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldKey
{
    /** Resolve the id override a control should receive from its own and surrounding Field identities. */
    public static function controlId(
        ?string $id,
        ?string $name,
        ?string $fieldId,
        ?string $fieldName,
    ): ?string {
        if ($id !== null && $id !== '') {
            return $id;
        }

        if ($name !== null && $name !== '' && $name !== $fieldName) {
            return self::toId($name);
        }

        return $fieldId !== null && $fieldId !== '' ? $fieldId : null;
    }

    public static function toErrorKey(string $name): string
    {
        $name = (string) preg_replace('/\[\]$/', '', $name);

        return str_replace(['][', '[', ']'], ['.', '.', ''], $name);
    }

    public static function toId(string $name): string
    {
        $name = (string) preg_replace('/\[\]$/', '', $name);

        return str_replace(['[', '.', ']'], ['-', '-', ''], $name);
    }
}
