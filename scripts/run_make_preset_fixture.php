<?php

declare(strict_types=1);

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

require dirname(__DIR__).'/vendor/autoload.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/run_make_preset_fixture.php <app-base> <name> [source]\n");
    exit(2);
}

$appBase = realpath($argv[1]);

if ($appBase === false || ! is_dir($appBase)) {
    fwrite(STDERR, "Application base does not exist: {$argv[1]}\n");
    exit(2);
}

$testCase = new class('runTest') extends TestCase
{
    public function boot(): Application
    {
        $this->setUp();

        return $this->app;
    }

    public function runTest(): void {}
};
$status = 1;

try {
    $app = $testCase->boot();
    $app->setBasePath($appBase);
    $kernel = $app->make(Kernel::class);
    $arguments = [
        'name' => $argv[2],
        '--force' => true,
        '--no-interaction' => true,
    ];

    if (isset($argv[3])) {
        $arguments['--from'] = $argv[3];
    }

    $status = $kernel->call('hotwire:make-preset', $arguments);

    fwrite($status === 0 ? STDOUT : STDERR, $kernel->output());
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
}

exit($status);
