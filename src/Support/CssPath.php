<?php

namespace Emaia\LaravelHotwire\Support;

/** @internal */
final class CssPath
{
    /** Normalize a CSS filesystem path lexically without accessing the filesystem. */
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $prefix = '';

        if (preg_match('/^([A-Za-z]:)(?:\/(.*))?$/', $path, $matches) === 1) {
            $prefix = strtoupper($matches[1]).'/';
            $path = $matches[2] ?? '';
        } elseif (str_starts_with($path, '//')) {
            $prefix = '//';
            $path = ltrim($path, '/');
        } elseif (str_starts_with($path, '/')) {
            $prefix = '/';
            $path = ltrim($path, '/');
        }

        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix.implode('/', $segments);
    }

    /** Compare drive and UNC paths case-insensitively on every host. */
    public static function comparable(string $path): string
    {
        $path = self::normalize($path);

        return preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '//')
            ? strtolower($path)
            : $path;
    }

    /** Determine whether a path is the root or one of its descendants. */
    public static function contains(string $root, string $path): bool
    {
        $path = self::comparable($path);
        $root = rtrim(self::comparable($root), '/');

        return $path === $root || str_starts_with($path, $root.'/');
    }
}
