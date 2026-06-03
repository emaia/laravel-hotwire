<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PackageInstaller
{
    public function detect(Filesystem $files): string
    {
        $lockFiles = [
            'bun.lock' => 'bun',
            'pnpm-lock.yaml' => 'pnpm',
            'yarn.lock' => 'yarn',
            'package-lock.json' => 'npm',
        ];

        foreach ($lockFiles as $file => $manager) {
            if ($files->exists(base_path($file))) {
                return $manager;
            }
        }

        return 'npm';
    }

    /**
     * Merge packages into the app package.json devDependencies, writing the file
     * only when something changes. Returns the entries actually written.
     *
     * With $updateExisting (default), a present package is bumped when its version
     * differs; with it false, an already-present package is left untouched.
     *
     * @param  array<string, string>  $packages  name => version
     * @return array<string, string> entries added or updated
     *
     * @throws FileNotFoundException
     */
    public function addDevDependencies(Filesystem $files, array $packages, bool $updateExisting = true): array
    {
        $path = base_path('package.json');

        if (! $files->exists($path)) {
            return [];
        }

        $json = json_decode($files->get($path), true) ?: [];
        $devDeps = $json['devDependencies'] ?? [];
        $changed = [];

        foreach ($packages as $name => $version) {
            $present = array_key_exists($name, $devDeps);

            if ($present && (! $updateExisting || $devDeps[$name] === $version)) {
                continue;
            }

            $devDeps[$name] = $version;
            $changed[$name] = $version;
        }

        if ($changed !== []) {
            $json['devDependencies'] = $devDeps;
            $files->put($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        }

        return $changed;
    }

    /** @return string[] */
    public function command(string $manager): array
    {
        return [$manager, 'install'];
    }

    public function install(string $manager, Command $command): int
    {
        $process = new Process($this->command($manager), base_path());
        $process->setTimeout(null);
        $exitCode = $process->run();

        if ($process->getOutput() !== '') {
            $command->getOutput()->write($process->getOutput());
        }

        if ($process->getErrorOutput() !== '') {
            $command->getOutput()->write($process->getErrorOutput());
        }

        return $exitCode;
    }
}
