<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Filesystem\Filesystem;

/**
 * Recognises the `@hotwire-package` marker that the package ships on the first
 * non-empty line of every controller and shared dependency it publishes
 *
 * (`// @hotwire-package` for JS/TS, `/* @hotwire-package *​/` for CSS).
 *
 * Used by publish and check commands to refuse overwriting a destination file
 * that doesn't carry the marker — meaning it was written by the user (or had
 * the marker stripped intentionally) and shouldn't be replaced silently.
 */
class PackageMarker
{
    public const string TAG = '@hotwire-package';

    public function __construct(private readonly Filesystem $files) {}

    /**
     * True when the path doesn't exist (publish is safe) or its first non-empty
     * line carries the marker tag. False means the file is treated as user-owned.
     *
     * Empty (zero-byte) files are also treated as user-owned: an empty file is
     * usually transient (interrupted edit, partial write) and silently overwriting
     * it would mask the real cause. The "fail closed" cost is negligible — a
     * 0-byte file deleted and republished costs the user nothing.
     */
    public function isPackageOwned(string $path): bool
    {
        if (! $this->files->exists($path)) {
            return true;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                return str_contains($trimmed, self::TAG);
            }
        } finally {
            fclose($handle);
        }

        return false;
    }
}
