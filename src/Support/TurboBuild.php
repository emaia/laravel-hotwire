<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Turbo builds the package publishes as release assets while an upstream fix awaits an official release.
 *
 * Only these references are the package's to supersede; any other reference is the application's choice.
 */
final class TurboBuild
{
    public const string PACKAGE = '@hotwired/turbo';

    private const string RELEASE_URL = '#^https://github\.com/emaia/laravel-hotwire/releases/download/(turbo-([0-9a-f]{12}))/hotwired-turbo-\2\.tgz$#';

    /** Return the release tag of a package-published Turbo build, or null for any other reference. */
    public static function tag(string $reference): ?string
    {
        return preg_match(self::RELEASE_URL, $reference, $match) === 1 ? $match[1] : null;
    }

    /** Determine whether the app still uses a package build the package no longer installs. */
    public static function isSuperseded(string $current, string $recommended): bool
    {
        return self::tag($current) !== null && $current !== $recommended;
    }

    /**
     * Build the command that replaces the app's Turbo reference with the recommended one.
     *
     * Keep $dev matching the section the app declares Turbo in: package managers move the entry to whichever section the
     * command names, and deploys that omit dev dependencies would then stop installing it.
     */
    public static function switchCommand(string $manager, string $recommended, bool $dev): string
    {
        $spec = self::tag($recommended) !== null ? $recommended : self::PACKAGE.'@'.$recommended;
        $command = match ($manager) {
            'bun' => ['bun add', '-d'],
            'pnpm' => ['pnpm add', '-D'],
            'yarn' => ['yarn add', '-D'],
            default => ['npm install', '-D'],
        };

        return $dev ? "{$command[0]} {$command[1]} {$spec}" : "{$command[0]} {$spec}";
    }
}
