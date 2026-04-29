<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\Console\Command;
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
