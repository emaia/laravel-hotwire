<?php

namespace Emaia\LaravelHotwire\Support;

final class FieldKey
{
    /** Resolve one id from an explicit identity and its surrounding owner identity. */
    public static function resolveId(
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

        if ($fieldId !== null && $fieldId !== '') {
            return $fieldId;
        }

        $resolvedName = $name !== null && $name !== '' ? $name : $fieldName;

        return $resolvedName !== null && $resolvedName !== '' ? self::toId($resolvedName) : null;
    }

    /** Resolve one validation key from an explicit identity and its surrounding owner identity. */
    public static function resolveErrorKey(
        ?string $errorKey,
        ?string $name,
        ?string $fieldErrorKey,
        ?string $fieldName,
    ): ?string {
        if ($errorKey !== null && $errorKey !== '') {
            return $errorKey;
        }

        if ($name !== null && $name !== '' && $name !== $fieldName) {
            return self::toErrorKey($name);
        }

        if ($fieldErrorKey !== null && $fieldErrorKey !== '') {
            return $fieldErrorKey;
        }

        $resolvedName = $name !== null && $name !== '' ? $name : $fieldName;

        return $resolvedName !== null && $resolvedName !== '' ? self::toErrorKey($resolvedName) : null;
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
