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

            $locations[$identifier] = self::withClassDeclarationLine(
                self::projectRelativePath($basePath, $file->getPathname()),
                $file->getPathname(),
            );
        }

        ksort($locations);

        return $locations;
    }

    /** Append the exported controller class line when the source can be inspected. */
    public static function withClassDeclarationLine(string $location, string $sourcePath): string
    {
        $source = @fopen($sourcePath, 'r');

        if ($source === false) {
            return $location;
        }

        try {
            $lineNumber = 0;

            while (($line = fgets($source)) !== false) {
                $lineNumber++;

                if (preg_match('/^\s*export\s+default\s+class\b/', $line) === 1) {
                    return $location.':'.$lineNumber;
                }
            }
        } finally {
            fclose($source);
        }

        return $location;
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
