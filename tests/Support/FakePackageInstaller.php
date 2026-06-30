<?php

namespace Emaia\LaravelHotwire\Tests\Support;

use Emaia\LaravelHotwire\Support\PackageInstaller;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class FakePackageInstaller extends PackageInstaller
{
    /** @var string[] */
    public array $installed = [];

    public function __construct(
        public string $manager = 'bun',
        public int $exitCode = 0,
    ) {}

    public function detect(Filesystem $files): string
    {
        return $this->manager;
    }

    public function install(string $manager, Command $command): int
    {
        $this->installed[] = $manager;

        return $this->exitCode;
    }
}
