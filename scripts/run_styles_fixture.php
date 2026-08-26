<?php

declare(strict_types=1);

use Emaia\LaravelHotwire\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

require dirname(__DIR__).'/vendor/autoload.php';

if ($argc < 5) {
    fwrite(STDERR, "Usage: php scripts/run_styles_fixture.php <app-base> <preset> <output> <components> [includes]\n");
    exit(2);
}

$appBase = realpath($argv[1]);

if ($appBase === false || ! is_dir($appBase)) {
    fwrite(STDERR, "Application base does not exist: {$argv[1]}\n");
    exit(2);
}

$values = static fn (string $value): array => array_values(array_filter(array_map('trim', explode(',', $value))));
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
    $status = $kernel->call('hotwire:styles', [
        '--preset' => $argv[2],
        '--output' => $argv[3],
        '--components' => $values($argv[4]),
        '--include' => $values($argv[5] ?? ''),
        '--no-interaction' => true,
    ]);

    fwrite($status === 0 ? STDOUT : STDERR, $kernel->output());
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
}

exit($status);
