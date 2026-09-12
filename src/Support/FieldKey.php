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

    /** Resolve the id scope opened by an enclosing Form, when present. */
    public static function scope(): ?string
    {
        $scope = app('view')->getConsumableComponentData('fieldScope');

        return is_string($scope) && $scope !== '' ? $scope : null;
    }

    /** Derive a name id within a form scope, reserving a unique base id. */
    public static function scopedToId(?string $scope, string $name): string
    {
        $base = self::toId($name);

        if ($scope === null || $scope === '') {
            return $base;
        }

        return app(ComponentId::class)->claim($scope, $scope.'-'.$base);
    }

    /** Derive a name error id within a form scope, reserving a unique id. */
    public static function scopedToErrorId(?string $scope, string $name): string
    {
        $base = self::toId($name).'-error';

        if ($scope === null || $scope === '') {
            return $base;
        }

        return app(ComponentId::class)->claim($scope, $scope.'-'.$base);
    }

    /** Derive the id a same-named control will claim in a form scope, without reserving it. */
    public static function scopedIdFor(?string $scope, string $name): string
    {
        $base = self::toId($name);

        return $scope !== null && $scope !== '' ? $scope.'-'.$base : $base;
    }
}
