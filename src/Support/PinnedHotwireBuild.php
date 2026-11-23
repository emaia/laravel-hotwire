<?php

namespace Emaia\LaravelHotwire\Support;

/**
 * Recognize only build references published by Laravel Hotwire, leaving app-owned versions alone.
 */
enum PinnedHotwireBuild: string
{
    case Turbo = '@hotwired/turbo';
    case Stimulus = '@hotwired/stimulus';

    /** Return the release tag for this package's own build, or null for any other reference. */
    public function tag(string $reference): ?string
    {
        $kind = strtolower($this->name);
        $pattern = '#^https://github\.com/emaia/laravel-hotwire/releases/download/('.$kind.'-[0-9a-f]{12}(?:-r[1-9][0-9]*)?)/hotwired-\1\.tgz\z#';

        return preg_match($pattern, $reference, $match) === 1 ? $match[1] : null;
    }

    /** Determine whether the app still uses a build Laravel Hotwire no longer installs. */
    public function isSuperseded(string $current, string $recommended): bool
    {
        return $current !== $recommended && $this->tag($current) !== null;
    }

    /** Build the command to switch references without moving the dependency to another section. */
    public function switchCommand(string $manager, string $recommended, bool $dev): string
    {
        $spec = $this->tag($recommended) !== null ? $recommended : $this->value.'@'.$recommended;
        $command = match ($manager) {
            'bun' => ['bun add', '-d'],
            'pnpm' => ['pnpm add', '-D'],
            'yarn' => ['yarn add', '-D'],
            default => ['npm install', '-D'],
        };

        return $dev ? "{$command[0]} {$command[1]} {$spec}" : "{$command[0]} {$spec}";
    }
}
