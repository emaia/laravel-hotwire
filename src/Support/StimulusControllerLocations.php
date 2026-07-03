<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class StimulusControllerLocations
{
    /** @return array<string, string> */
    public static function discoverApp(Filesystem $files, string $basePath, string $controllersPath): array
    {
        if (! $files->isDirectory($controllersPath)) {
            return [];
        }

        $locations = [];

        $finder = Finder::create()
            ->files()
            ->name('*_controller.js')
            ->name('*_controller.ts')
            ->in($controllersPath);

        foreach ($finder as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $identifier = self::identifierFromRelativePath($relative);

            if ($identifier === null) {
                continue;
            }

            $locations[$identifier] = self::projectRelativePath($basePath, $file->getPathname());
        }

        ksort($locations);

        return $locations;
    }

    private static function identifierFromRelativePath(string $relative): ?string
    {
        $name = preg_replace('/_controller\.(js|ts)$/', '', $relative);

        if ($name === null || $name === $relative) {
            return null;
        }

        return str_replace(['/', '_'], ['--', '-'], $name);
    }

    private static function projectRelativePath(string $basePath, string $path): string
    {
        $basePath = rtrim(str_replace('\\', '/', $basePath), '/').'/';
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
    }
}
